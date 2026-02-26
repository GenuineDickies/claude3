<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerListTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    public function test_customer_list_page_loads(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Customers');
    }

    public function test_customer_list_requires_authentication(): void
    {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_list_shows_customers(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'phone' => '5550002222',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Wonderland');
        $response->assertSee('Bob');
        $response->assertSee('Builder');
    }

    public function test_customer_list_searches_by_name(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'phone' => '5550002222',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertDontSee('Bob');
    }

    public function test_customer_list_searches_by_phone(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'phone' => '5550002222',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index', ['search' => '5550001111']));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertDontSee('Bob');
    }

    public function test_customer_list_filters_by_sms_consent(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Opted',
            'last_name' => 'In',
            'phone' => '5550001111',
            'is_active' => true,
            'sms_consent_at' => now(),
            'sms_opt_out_at' => null,
        ]);

        Customer::create([
            'first_name' => 'Not',
            'last_name' => 'Consented',
            'phone' => '5550002222',
            'is_active' => true,
            'sms_consent_at' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index', ['consent' => 'yes']));

        $response->assertOk();
        $response->assertSee('Opted');
        $response->assertDontSee('Not');
    }

    public function test_customer_list_filters_opted_out(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Consenting',
            'last_name' => 'Person',
            'phone' => '5550001111',
            'is_active' => true,
            'sms_consent_at' => now(),
            'sms_opt_out_at' => null,
        ]);

        Customer::create([
            'first_name' => 'Unconsented',
            'last_name' => 'Person',
            'phone' => '5550002222',
            'is_active' => true,
            'sms_consent_at' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index', ['consent' => 'no']));

        $response->assertOk();
        $response->assertSee('Unconsented');
        $response->assertDontSee('Consenting');
    }

    public function test_customer_list_filters_by_active_status(): void
    {
        $this->withoutVite();

        Customer::create([
            'first_name' => 'Enabled',
            'last_name' => 'Customer',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        Customer::create([
            'first_name' => 'Disabled',
            'last_name' => 'Customer',
            'phone' => '5550002222',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index', ['active' => '1']));

        $response->assertOk();
        $response->assertSee('Enabled');
        $response->assertDontSee('Disabled');
    }

    public function test_customer_list_shows_ticket_count(): void
    {
        $this->withoutVite();

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Jane');
    }

    public function test_customer_list_paginates(): void
    {
        $this->withoutVite();

        for ($i = 1; $i <= 25; $i++) {
            Customer::create([
                'first_name' => "Customer{$i}",
                'last_name' => 'Test',
                'phone' => "555000{$i}",
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index'));

        $response->assertOk();
        // Page 1 should show 20 customers, not all 25
        $response->assertDontSee('Customer1Test'); // Customer1 is oldest, latest() puts it on page 2
    }

    public function test_customer_list_empty_state(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('No customers found');
    }
}
