<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Document;
use App\Models\DocumentTransactionImport;
use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * After a spreadsheet finishes AI analysis, this job sends the extracted
 * text back to the AI to parse individual transactions into draft rows.
 */
class ImportDocumentTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function __construct(
        public Document $document,
        public string $spreadsheetText,
    ) {}

    public function handle(): void
    {
        $apiKey = (string) config('services.document_ai.api_key', '');
        $model = (string) config('services.document_ai.model', 'gpt-4o-mini');

        if ($apiKey === '') {
            Log::warning('ImportDocumentTransactionsJob: No API key configured', [
                'document_id' => $this->document->id,
            ]);

            return;
        }

        // Build the chart of accounts context (import scope only)
        $accounts = Account::import()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'type'])
            ->map(fn ($a) => "{$a->code} — {$a->name} ({$a->type})")
            ->implode("\n");

        $expenseCategories = implode(', ', array_keys(Expense::CATEGORIES));
        $paymentMethods = implode(', ', array_keys(Expense::PAYMENT_METHODS));

        $systemPrompt = <<<PROMPT
You are a bookkeeping assistant for a roadside-assistance business. You will receive raw text extracted from a financial spreadsheet. Parse each individual transaction row and return a JSON object with exactly one key:

"transactions" — an array of objects, each with:
  - "transaction_date": ISO date string (YYYY-MM-DD) or null if not determinable
  - "description": Brief description of the transaction
  - "amount": Positive decimal number (absolute value)
  - "type": One of: expense, income, transfer
  - "category": One of: {$expenseCategories}. Choose the best match for the business purpose. Use "other" only as a last resort.
  - "vendor": Vendor or payee name, or null
  - "payment_method": One of: {$paymentMethods}. Or null if not identifiable.
  - "reference": Check number, transaction ID, or other reference. Or null.
  - "account_code": The best matching account code from the chart of accounts below. This is critical for proper accounting — pick the most specific match.

Chart of Accounts:
{$accounts}

Categorisation guidance for this roadside-assistance business:
- Gas station purchases → category "fuel", account 6150
- Auto parts, batteries, tires, jump starters → category "parts", account 5100
- Tow truck repairs, oil changes → category "vehicle_repair", account 6200
- Deposits or payments received from customers, Honk, Urgently → type "income"
- Square deposits, card settlements → type "income", account 4500
- Honk or Urgently payouts/revenue → type "income", account 4400
- Bank fees, processing fees, Stripe/Square fees → category "other", account 6850
- Transfers between own accounts → type "transfer", account 1110 or 1120
- Subcontractor or provider payments → category "other", account 5200
- Owner draws, personal transfers → type "transfer", account 3100
- Insurance premiums → category "insurance", account 6300 or 6250

Rules:
- Parse EVERY identifiable transaction row. Do not skip or combine rows.
- For amounts: always use positive numbers. Use "type" to indicate direction (expense = money out, income = money in).
- If a row appears to be a header, subtotal, or summary line, skip it.
- Dates may appear in various formats. Normalize to YYYY-MM-DD.
- If a column appears to contain payment methods but uses non-standard terms, map them to the closest standard method.
- Return ONLY valid JSON. No markdown, no explanations outside the JSON.
PROMPT;

        $userContent = "Parse the following spreadsheet data into individual transactions:\n\n"
            . mb_substr($this->spreadsheetText, 0, 14000);

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $model,
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userContent],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.1,
                'max_tokens'      => 4096,
            ]);

        if ($response->failed()) {
            if ($response->status() === 429) {
                $delay = 30 * pow(2, $this->attempts());
                Log::warning('ImportDocumentTransactionsJob: Rate limited', [
                    'document_id' => $this->document->id,
                    'delay'       => $delay,
                ]);
                $this->release($delay);

                return;
            }

            Log::error('ImportDocumentTransactionsJob: API request failed', [
                'document_id' => $this->document->id,
                'status'      => $response->status(),
                'body'        => mb_substr($response->body(), 0, 500),
            ]);

            throw new \RuntimeException(
                'Transaction parsing API request failed with status ' . $response->status()
            );
        }

        $content = $response->json('choices.0.message.content', '');
        $parsed = json_decode($content, true);

        if (! is_array($parsed) || ! isset($parsed['transactions'])) {
            Log::error('ImportDocumentTransactionsJob: Invalid JSON response', [
                'document_id' => $this->document->id,
                'content'     => mb_substr($content, 0, 500),
            ]);

            return;
        }

        $created = 0;
        $validCategories = array_keys(Expense::CATEGORIES);
        $validMethods = array_keys(Expense::PAYMENT_METHODS);
        $validTypes = DocumentTransactionImport::TYPES;

        foreach ($parsed['transactions'] as $tx) {
            if (empty($tx['description']) && empty($tx['amount'])) {
                continue;
            }

            $type = in_array($tx['type'] ?? '', $validTypes, true)
                ? $tx['type']
                : 'expense';

            $category = in_array($tx['category'] ?? '', $validCategories, true)
                ? $tx['category']
                : 'other';

            $method = in_array($tx['payment_method'] ?? '', $validMethods, true)
                ? $tx['payment_method']
                : null;

            DocumentTransactionImport::create([
                'document_id'      => $this->document->id,
                'transaction_date' => $this->parseDate($tx['transaction_date'] ?? null),
                'description'      => mb_substr((string) ($tx['description'] ?? 'Unknown'), 0, 255),
                'amount'           => max(0, abs((float) ($tx['amount'] ?? 0))),
                'type'             => $type,
                'category'         => $category,
                'vendor'           => isset($tx['vendor']) ? mb_substr((string) $tx['vendor'], 0, 255) : null,
                'payment_method'   => $method,
                'reference'        => isset($tx['reference']) ? mb_substr((string) $tx['reference'], 0, 255) : null,
                'account_code'     => isset($tx['account_code']) ? mb_substr((string) $tx['account_code'], 0, 10) : null,
                'raw_data'         => $tx,
                'status'           => 'draft',
            ]);

            $created++;
        }

        Log::info('ImportDocumentTransactionsJob: Parsed transactions', [
            'document_id' => $this->document->id,
            'count'       => $created,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportDocumentTransactionsJob permanently failed', [
            'document_id' => $this->document->id,
            'error'       => $exception->getMessage(),
        ]);
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
