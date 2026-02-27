<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $category = CatalogCategory::firstOrCreate(
            ['name' => 'Roadside Services'],
            ['description' => 'Standard roadside-assistance service types', 'sort_order' => 0]
        );

        $items = [
            ['name' => 'Flat Tire Change', 'unit_price' => 75.00, 'sort_order' => 1],
            ['name' => 'Tow',              'unit_price' => 125.00, 'sort_order' => 2],
            ['name' => 'Lockout Service',   'unit_price' => 65.00, 'sort_order' => 3],
            ['name' => 'Jump Start',        'unit_price' => 55.00, 'sort_order' => 4],
            ['name' => 'Fuel Delivery',     'unit_price' => 60.00, 'sort_order' => 5],
            ['name' => 'Winch Out',         'unit_price' => 150.00, 'sort_order' => 6],
        ];

        foreach ($items as $item) {
            CatalogItem::updateOrCreate(
                ['name' => $item['name'], 'catalog_category_id' => $category->id],
                $item
            );
        }
    }
}
