<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function validExpenseData(array $overrides = []): array
    {
        return array_merge([
            'date'             => '2026-02-26',
            'vendor'           => 'AutoZone',
            'description'      => 'Brake pads for fleet truck',
            'category'         => 'parts',
            'amount'           => '49.99',
            'payment_method'   => 'card',
            'reference_number' => 'TXN-12345',
            'notes'            => 'For truck #3',
        ], $overrides);
    }

    // ── Index ──────────────────────────────────────────────────

    public function test_index_page_loads(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Expenses');
    }

    public function test_index_shows_expenses(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.index'))
            ->assertSee('EXP-20260226-0001')
            ->assertSee('AutoZone');
    }

    public function test_index_filters_by_category(): void
    {
        $user = $this->createUser();
        Expense::create(array_merge($this->validExpenseData(['category' => 'fuel', 'vendor' => 'Shell']), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));
        Expense::create(array_merge($this->validExpenseData(['category' => 'parts', 'vendor' => 'AutoZone']), [
            'expense_number' => 'EXP-20260226-0002',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.index', ['category' => 'fuel']))
            ->assertSee('Shell')
            ->assertDontSee('AutoZone');
    }

    // ── Create ─────────────────────────────────────────────────

    public function test_create_page_loads(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Record Expense');
    }

    // ── Store ──────────────────────────────────────────────────

    public function test_store_creates_expense(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->post(route('expenses.store'), $this->validExpenseData());

        $response->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'vendor'   => 'AutoZone',
            'category' => 'parts',
            'amount'   => '49.99',
        ]);
    }

    public function test_store_generates_expense_number(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('expenses.store'), $this->validExpenseData());

        $expense = Expense::first();
        $this->assertNotNull($expense);
        $this->assertStringStartsWith('EXP-', $expense->expense_number);
    }

    public function test_store_handles_receipt_upload(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('expenses.store'), array_merge($this->validExpenseData(), [
                'receipt' => UploadedFile::fake()->image('receipt.jpg', 800, 600),
            ]));

        $expense = Expense::first();
        $this->assertNotNull($expense->receipt_path);
        Storage::disk('local')->assertExists($expense->receipt_path);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('expenses.store'), [])
            ->assertSessionHasErrors(['date', 'vendor', 'category', 'amount']);
    }

    public function test_store_validates_category_enum(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('expenses.store'), $this->validExpenseData(['category' => 'invalid']))
            ->assertSessionHasErrors('category');
    }

    // ── Show ───────────────────────────────────────────────────

    public function test_show_page_loads(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertSee('EXP-20260226-0001')
            ->assertSee('AutoZone')
            ->assertSee('49.99');
    }

    // ── Edit / Update ──────────────────────────────────────────

    public function test_edit_page_loads(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('Edit Expense');
    }

    public function test_update_modifies_expense(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->put(route('expenses.update', $expense), $this->validExpenseData([
                'vendor' => 'O\'Reilly Auto Parts',
                'amount' => '75.00',
            ]))
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();
        $this->assertEquals('O\'Reilly Auto Parts', $expense->vendor);
        $this->assertEquals('75.00', $expense->amount);
    }

    // ── Destroy ────────────────────────────────────────────────

    public function test_destroy_deletes_expense(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_destroy_cleans_up_receipt_file(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('expenses.store'), array_merge($this->validExpenseData(), [
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ]));

        $expense = Expense::first();
        $receiptPath = $expense->receipt_path;
        Storage::disk('local')->assertExists($receiptPath);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense));

        Storage::disk('local')->assertMissing($receiptPath);
    }

    // ── Auth ───────────────────────────────────────────────────

    public function test_expense_routes_require_authentication(): void
    {
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
        ]));

        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->get(route('expenses.create'))->assertRedirect(route('login'));
        $this->post(route('expenses.store'))->assertRedirect(route('login'));
        $this->get(route('expenses.show', $expense))->assertRedirect(route('login'));
        $this->get(route('expenses.edit', $expense))->assertRedirect(route('login'));
        $this->put(route('expenses.update', $expense))->assertRedirect(route('login'));
        $this->delete(route('expenses.destroy', $expense))->assertRedirect(route('login'));
    }

    // ── Receipt download ───────────────────────────────────────

    public function test_receipt_download_returns_404_when_no_receipt(): void
    {
        $user = $this->createUser();
        $expense = Expense::create(array_merge($this->validExpenseData(), [
            'expense_number' => 'EXP-20260226-0001',
            'created_by'     => $user->id,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.receipt', $expense))
            ->assertNotFound();
    }
}
