<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ShorthandParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapidDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function seedCatalog(): CatalogItem
    {
        $category = CatalogCategory::create([
            'name' => 'Labor',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Jump Start',
            'base_cost' => 85.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function seedFullCatalog(): void
    {
        $category = CatalogCategory::create([
            'name' => 'Labor',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $items = [
            ['name' => 'Flat Tire Change', 'base_cost' => 85.00],
            ['name' => 'Jump Start', 'base_cost' => 85.00],
            ['name' => 'Lockout Service', 'base_cost' => 95.00],
            ['name' => 'Fuel Delivery', 'base_cost' => 75.00],
            ['name' => 'Tow', 'base_cost' => 125.00],
            ['name' => 'Winch Out', 'base_cost' => 150.00],
        ];

        foreach ($items as $i => $item) {
            CatalogItem::create([
                'catalog_category_id' => $category->id,
                'name' => $item['name'],
                'base_cost' => $item['base_cost'],
                'unit' => 'each',
                'pricing_type' => 'fixed',
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  Auth
    // ══════════════════════════════════════════════════════════

    public function test_rapid_dispatch_requires_auth(): void
    {
        $this->get(route('rapid-dispatch.create'))->assertRedirect(route('login'));
    }

    public function test_rapid_dispatch_store_requires_auth(): void
    {
        $this->post(route('rapid-dispatch.store'))->assertRedirect(route('login'));
    }

    public function test_rapid_dispatch_parse_requires_auth(): void
    {
        $this->get(route('rapid-dispatch.parse'))->assertRedirect(route('login'));
    }

    // ══════════════════════════════════════════════════════════
    //  Page loads
    // ══════════════════════════════════════════════════════════

    public function test_rapid_dispatch_page_loads(): void
    {
        $this->seedCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('rapid-dispatch.create'))
            ->assertOk()
            ->assertSee('Rapid Dispatch')
            ->assertSee('Jump Start');
    }

    // ══════════════════════════════════════════════════════════
    //  Shorthand parser service
    // ══════════════════════════════════════════════════════════

    public function test_shorthand_parser_matches_jump_start(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('jump start');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Jump Start', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_matches_flat_tire(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('flat tire');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Flat Tire Change', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_matches_lockout(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('lockout');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Lockout Service', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_matches_tow(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('tow');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Tow', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_matches_fuel(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('out of gas');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Fuel Delivery', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_matches_winch(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('winch out');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Winch Out', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_is_case_insensitive(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('JUMP START');
        $this->assertTrue($result['matched']);
        $this->assertEquals('Jump Start', $result['catalog_item']->name);
    }

    public function test_shorthand_parser_returns_no_match_for_unknown(): void
    {
        $this->seedFullCatalog();
        $parser = new ShorthandParserService();

        $result = $parser->parse('spaceship repair');
        $this->assertFalse($result['matched']);
        $this->assertNull($result['catalog_item']);
    }

    public function test_shorthand_parser_handles_empty_input(): void
    {
        $parser = new ShorthandParserService();

        $result = $parser->parse('');
        $this->assertFalse($result['matched']);
    }

    // ══════════════════════════════════════════════════════════
    //  Parse API endpoint
    // ══════════════════════════════════════════════════════════

    public function test_parse_endpoint_returns_match(): void
    {
        $this->seedFullCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson(route('rapid-dispatch.parse', ['q' => 'jump start']))
            ->assertOk()
            ->assertJson([
                'matched' => true,
                'name' => 'Jump Start',
                'unit_price' => 85.0,
            ]);
    }

    public function test_parse_endpoint_returns_no_match(): void
    {
        $this->seedFullCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson(route('rapid-dispatch.parse', ['q' => 'xyz']))
            ->assertOk()
            ->assertJson(['matched' => false]);
    }

    // ══════════════════════════════════════════════════════════
    //  Store — creates service request
    // ══════════════════════════════════════════════════════════

    public function test_store_creates_service_request_and_customer(): void
    {
        $item = $this->seedCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('rapid-dispatch.store'), [
                'phone' => '5551234567',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'catalog_item_id' => $item->id,
                'quoted_price' => 85.00,
                'location' => '123 Main St',
                'notes' => 'Blue sedan',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
        ]);

        $this->assertDatabaseHas('service_requests', [
            'catalog_item_id' => $item->id,
            'quoted_price' => 85.00,
            'location' => '123 Main St',
            'notes' => 'Blue sedan',
            'status' => 'new',
        ]);
    }

    public function test_store_uses_existing_customer(): void
    {
        $item = $this->seedCatalog();
        $user = $this->createUser();

        $existing = Customer::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'phone' => '5559876543',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('rapid-dispatch.store'), [
                'phone' => '(555) 987-6543',
                'first_name' => 'New',
                'last_name' => 'Name',
                'catalog_item_id' => $item->id,
                'quoted_price' => 85.00,
            ])
            ->assertRedirect();

        // Name updated on existing customer
        $existing->refresh();
        $this->assertEquals('New', $existing->first_name);

        // Only one customer with this phone
        $this->assertEquals(1, Customer::where('phone', '5559876543')->count());
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('rapid-dispatch.store'), [])
            ->assertSessionHasErrors(['phone', 'first_name', 'last_name', 'catalog_item_id', 'quoted_price']);
    }

    public function test_store_validates_catalog_item_exists(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('rapid-dispatch.store'), [
                'phone' => '5551234567',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'catalog_item_id' => 9999,
                'quoted_price' => 85.00,
            ])
            ->assertSessionHasErrors(['catalog_item_id']);
    }

    public function test_store_redirects_to_service_request_show(): void
    {
        $item = $this->seedCatalog();
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->post(route('rapid-dispatch.store'), [
                'phone' => '5551234567',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'catalog_item_id' => $item->id,
                'quoted_price' => 85.00,
            ]);

        $sr = ServiceRequest::latest()->first();
        $response->assertRedirect(route('service-requests.show', $sr));
        $response->assertSessionHas('success');
    }

    // ══════════════════════════════════════════════════════════
    //  Toggle links exist
    // ══════════════════════════════════════════════════════════

    public function test_index_page_has_rapid_dispatch_link(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('service-requests.index'))
            ->assertOk()
            ->assertSee('Rapid');
    }

    public function test_create_page_has_rapid_mode_link(): void
    {
        $this->seedCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('service-requests.create'))
            ->assertOk()
            ->assertSee('Rapid Mode');
    }

    public function test_rapid_page_has_full_form_link(): void
    {
        $this->seedCatalog();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('rapid-dispatch.create'))
            ->assertOk()
            ->assertSee('Full Form');
    }
}
