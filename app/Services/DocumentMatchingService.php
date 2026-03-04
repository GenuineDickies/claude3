<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ServiceRequest;
use App\Models\Warranty;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Log;

class DocumentMatchingService
{
    /**
     * Attempt to match a document to existing database records using AI-extracted data.
     * Returns an array of candidates sorted by confidence, and auto-links if top match is strong.
     *
     * @return array{matched: bool, candidates: array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}>}
     */
    public function match(Document $document): array
    {
        $data = $document->ai_extracted_data ?? [];
        $category = $document->ai_suggested_category ?? $document->category;

        if (empty($data)) {
            return ['matched' => false, 'candidates' => []];
        }

        $candidates = [];

        // Run matchers in priority order based on document category
        $matchers = $this->matchersForCategory($category);

        foreach ($matchers as $matcher) {
            $candidates = array_merge($candidates, $this->$matcher($data));
        }

        // Sort by score descending
        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Keep top 5
        $candidates = array_slice($candidates, 0, 5);

        // Auto-link if top candidate has high confidence
        $matched = false;
        if (! empty($candidates) && $candidates[0]['score'] >= 0.8) {
            $top = $candidates[0];
            $entity = $this->resolveEntity($top['type'], $top['id']);

            if ($entity) {
                $document->linkTo($entity, 'matched');
                $matched = true;

                Log::info('DocumentMatchingService: auto-matched document', [
                    'document_id' => $document->id,
                    'matched_to'  => $top['type'] . '#' . $top['id'],
                    'score'       => $top['score'],
                ]);
            }
        }

        // Store candidates for UI review
        $document->update(['match_candidates' => $candidates]);

        return ['matched' => $matched, 'candidates' => $candidates];
    }

    /**
     * Check all unmatched inbox documents against a newly created/updated entity.
     * Call this when new invoices, expenses, etc. are created.
     */
    public function matchAgainstNewEntity(object $entity): int
    {
        $unmatchedDocs = Document::unmatched()
            ->where('ai_status', 'completed')
            ->get();

        $matched = 0;

        foreach ($unmatchedDocs as $doc) {
            $result = $this->match($doc);
            if ($result['matched']) {
                $matched++;
            }
        }

        return $matched;
    }

    /** Determine which matchers to run based on document category. */
    private function matchersForCategory(string $category): array
    {
        return match ($category) {
            'invoice'      => ['matchInvoices', 'matchWorkOrders', 'matchEstimates', 'matchExpenses'],
            'receipt'      => ['matchReceipts', 'matchInvoices', 'matchExpenses'],
            'warranty_doc' => ['matchWarranties', 'matchExpenses'],
            'insurance'    => ['matchCustomers', 'matchServiceRequests'],
            'license'      => ['matchCustomers'],
            'contract'     => ['matchCustomers', 'matchWarranties'],
            default        => ['matchInvoices', 'matchExpenses', 'matchReceipts', 'matchWarranties', 'matchWorkOrders'],
        };
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchInvoices(array $data): array
    {
        $candidates = [];

        // Match by invoice number (strongest signal)
        $invoiceNumber = $data['invoice_number'] ?? null;
        if ($invoiceNumber) {
            $invoices = Invoice::where('invoice_number', $invoiceNumber)->limit(3)->get();
            foreach ($invoices as $inv) {
                $candidates[] = $this->candidate('invoice', $inv->id, "Invoice {$inv->invoice_number}", 0.95, ['Invoice number exact match']);
            }
        }

        // Match by amount + date combination
        $amount = $this->extractAmount($data);
        $date = $data['due_date'] ?? $data['date'] ?? null;
        if ($amount && $date) {
            $invoices = Invoice::where('total', $amount)
                ->whereDate('due_date', $date)
                ->limit(3)
                ->get();
            foreach ($invoices as $inv) {
                if (! $this->alreadyCandidate($candidates, 'invoice', $inv->id)) {
                    $candidates[] = $this->candidate('invoice', $inv->id, "Invoice {$inv->invoice_number}", 0.75, ['Amount and date match']);
                }
            }
        } elseif ($amount) {
            $invoices = Invoice::where('total', $amount)->limit(3)->get();
            foreach ($invoices as $inv) {
                if (! $this->alreadyCandidate($candidates, 'invoice', $inv->id)) {
                    $candidates[] = $this->candidate('invoice', $inv->id, "Invoice {$inv->invoice_number}", 0.5, ['Amount match']);
                }
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchExpenses(array $data): array
    {
        $candidates = [];

        // Match by reference number
        $refNumber = $data['reference_number'] ?? $data['receipt_number'] ?? $data['invoice_number'] ?? null;
        if ($refNumber) {
            $expenses = Expense::where('reference_number', $refNumber)->limit(3)->get();
            foreach ($expenses as $exp) {
                $candidates[] = $this->candidate('expense', $exp->id, "Expense {$exp->expense_number}", 0.9, ['Reference number match']);
            }
        }

        // Match by vendor + amount
        $vendor = $data['vendor_name'] ?? null;
        $amount = $this->extractAmount($data);
        if ($vendor && $amount) {
            $expenses = Expense::where('vendor', 'LIKE', "%{$vendor}%")
                ->where('amount', $amount)
                ->limit(3)
                ->get();
            foreach ($expenses as $exp) {
                if (! $this->alreadyCandidate($candidates, 'expense', $exp->id)) {
                    $candidates[] = $this->candidate('expense', $exp->id, "Expense {$exp->expense_number}", 0.8, ['Vendor and amount match']);
                }
            }
        } elseif ($vendor) {
            $expenses = Expense::where('vendor', 'LIKE', "%{$vendor}%")
                ->orderByDesc('date')
                ->limit(3)
                ->get();
            foreach ($expenses as $exp) {
                if (! $this->alreadyCandidate($candidates, 'expense', $exp->id)) {
                    $candidates[] = $this->candidate('expense', $exp->id, "Expense {$exp->expense_number}", 0.4, ['Vendor name match']);
                }
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchReceipts(array $data): array
    {
        $candidates = [];

        $receiptNumber = $data['receipt_number'] ?? null;
        if ($receiptNumber) {
            $receipts = Receipt::where('receipt_number', $receiptNumber)->limit(3)->get();
            foreach ($receipts as $r) {
                $candidates[] = $this->candidate('receipt', $r->id, "Receipt {$r->receipt_number}", 0.95, ['Receipt number exact match']);
            }
        }

        $paymentRef = $data['payment_reference'] ?? null;
        if ($paymentRef) {
            $receipts = Receipt::where('payment_reference', $paymentRef)->limit(3)->get();
            foreach ($receipts as $r) {
                if (! $this->alreadyCandidate($candidates, 'receipt', $r->id)) {
                    $candidates[] = $this->candidate('receipt', $r->id, "Receipt {$r->receipt_number}", 0.85, ['Payment reference match']);
                }
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchWarranties(array $data): array
    {
        $candidates = [];

        $vendorInvoice = $data['vendor_invoice_number'] ?? $data['invoice_number'] ?? null;
        $vendorName = $data['vendor_name'] ?? null;
        $partNumber = $data['part_number'] ?? null;

        if ($vendorInvoice) {
            $warranties = Warranty::where('vendor_invoice_number', $vendorInvoice)->limit(3)->get();
            foreach ($warranties as $w) {
                $candidates[] = $this->candidate('warranty', $w->id, "Warranty: {$w->part_name}", 0.9, ['Vendor invoice number match']);
            }
        }

        if ($partNumber) {
            $warranties = Warranty::where('part_number', $partNumber)->limit(3)->get();
            foreach ($warranties as $w) {
                if (! $this->alreadyCandidate($candidates, 'warranty', $w->id)) {
                    $score = $vendorName && stripos($w->vendor_name ?? '', $vendorName) !== false ? 0.85 : 0.6;
                    $reasons = ['Part number match'];
                    if ($score > 0.6) {
                        $reasons[] = 'Vendor name match';
                    }
                    $candidates[] = $this->candidate('warranty', $w->id, "Warranty: {$w->part_name}", $score, $reasons);
                }
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchWorkOrders(array $data): array
    {
        $candidates = [];

        $woNumber = $data['work_order_number'] ?? null;
        if ($woNumber) {
            $workOrders = WorkOrder::where('work_order_number', $woNumber)->limit(3)->get();
            foreach ($workOrders as $wo) {
                $candidates[] = $this->candidate('work-order', $wo->id, "WO {$wo->work_order_number}", 0.95, ['Work order number exact match']);
            }
        }

        $amount = $this->extractAmount($data);
        if ($amount) {
            $workOrders = WorkOrder::where('total', $amount)->limit(3)->get();
            foreach ($workOrders as $wo) {
                if (! $this->alreadyCandidate($candidates, 'work-order', $wo->id)) {
                    $candidates[] = $this->candidate('work-order', $wo->id, "WO {$wo->work_order_number}", 0.45, ['Amount match']);
                }
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchEstimates(array $data): array
    {
        $candidates = [];

        $estNumber = $data['estimate_number'] ?? null;
        if ($estNumber) {
            $estimates = Estimate::where('estimate_number', $estNumber)->limit(3)->get();
            foreach ($estimates as $est) {
                $candidates[] = $this->candidate('estimate', $est->id, "Estimate {$est->estimate_number}", 0.9, ['Estimate number exact match']);
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchCustomers(array $data): array
    {
        $candidates = [];

        $phone = $data['phone'] ?? null;
        if ($phone) {
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) >= 10) {
                $customers = Customer::where('phone', 'LIKE', '%' . substr($digits, -10))->limit(3)->get();
                foreach ($customers as $c) {
                    $candidates[] = $this->candidate('customer', $c->id, "{$c->first_name} {$c->last_name}", 0.85, ['Phone number match']);
                }
            }
        }

        $name = $data['customer_name'] ?? null;
        if ($name && empty($candidates)) {
            $parts = explode(' ', trim($name), 2);
            $query = Customer::query();
            if (count($parts) === 2) {
                $query->where('first_name', 'LIKE', "%{$parts[0]}%")
                    ->where('last_name', 'LIKE', "%{$parts[1]}%");
            } else {
                $query->where(function ($q) use ($name) {
                    $q->where('first_name', 'LIKE', "%{$name}%")
                        ->orWhere('last_name', 'LIKE', "%{$name}%");
                });
            }
            $customers = $query->limit(3)->get();
            foreach ($customers as $c) {
                $candidates[] = $this->candidate('customer', $c->id, "{$c->first_name} {$c->last_name}", 0.6, ['Customer name match']);
            }
        }

        return $candidates;
    }

    /** @return array<int, array{type: string, id: int, label: string, score: float, reasons: string[]}> */
    private function matchServiceRequests(array $data): array
    {
        $candidates = [];

        // Match by vehicle info
        $vin = $data['vin'] ?? null;
        $make = $data['vehicle_make'] ?? null;
        $model = $data['vehicle_model'] ?? null;

        if ($make && $model) {
            $srs = ServiceRequest::where('vehicle_make', 'LIKE', "%{$make}%")
                ->where('vehicle_model', 'LIKE', "%{$model}%")
                ->orderByDesc('id')
                ->limit(3)
                ->get();
            foreach ($srs as $sr) {
                $candidates[] = $this->candidate(
                    'service-request',
                    $sr->id,
                    "SR #{$sr->id} — {$sr->vehicle_year} {$sr->vehicle_make} {$sr->vehicle_model}",
                    0.5,
                    ['Vehicle make/model match']
                );
            }
        }

        return $candidates;
    }

    /** Resolve a type+id into an Eloquent model. */
    private function resolveEntity(string $type, int $id): ?object
    {
        $map = [
            'invoice'         => Invoice::class,
            'expense'         => Expense::class,
            'receipt'         => Receipt::class,
            'warranty'        => Warranty::class,
            'work-order'      => WorkOrder::class,
            'estimate'        => Estimate::class,
            'customer'        => Customer::class,
            'service-request' => ServiceRequest::class,
        ];

        $class = $map[$type] ?? null;

        return $class ? $class::find($id) : null;
    }

    private function candidate(string $type, int $id, string $label, float $score, array $reasons): array
    {
        return compact('type', 'id', 'label', 'score', 'reasons');
    }

    private function alreadyCandidate(array $candidates, string $type, int $id): bool
    {
        foreach ($candidates as $c) {
            if ($c['type'] === $type && $c['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /** Extract a numeric amount from various possible keys. */
    private function extractAmount(array $data): ?string
    {
        $raw = $data['total_amount'] ?? $data['amount'] ?? $data['total'] ?? null;

        if ($raw === null) {
            return null;
        }

        // Strip currency symbols and commas
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $raw);

        return $cleaned !== '' ? $cleaned : null;
    }
}
