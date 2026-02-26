<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Labor',
                'type' => 'service',
                'description' => 'Roadside service labor charges',
                'sort_order' => 1,
                'items' => [
                    ['name' => 'Flat Tire Change',  'unit_price' => 75.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Jump Start',        'unit_price' => 55.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 2],
                    ['name' => 'Lockout Service',   'unit_price' => 65.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 3],
                    ['name' => 'Winch Out',         'unit_price' => 150.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 4],
                    ['name' => 'Fuel Delivery',     'unit_price' => 60.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Towing',
                'type' => 'service',
                'description' => 'Towing services and mileage',
                'sort_order' => 2,
                'items' => [
                    ['name' => 'Base Tow Fee',   'unit_price' => 85.00, 'pricing_type' => 'fixed',    'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Per-Mile Tow',   'unit_price' => 3.50,  'pricing_type' => 'variable', 'unit' => 'mile', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Parts',
                'type' => 'part',
                'description' => 'Replacement parts and supplies',
                'sort_order' => 3,
                'items' => [
                    ['name' => 'Spare Tire', 'unit_price' => 45.00, 'pricing_type' => 'fixed',    'unit' => 'each',   'sort_order' => 1],
                    ['name' => 'Battery',    'unit_price' => 120.00,'pricing_type' => 'fixed',    'unit' => 'each',   'sort_order' => 2],
                    ['name' => 'Fuel',       'unit_price' => 15.00, 'pricing_type' => 'variable', 'unit' => 'gallon', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Fees',
                'type' => 'service',
                'description' => 'Additional surcharges and fees',
                'sort_order' => 4,
                'items' => [
                    ['name' => 'After-Hours Surcharge',   'unit_price' => 35.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Emergency Dispatch Fee',  'unit_price' => 25.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $items = $categoryData['items'];
            unset($categoryData['items']);

            $category = CatalogCategory::updateOrCreate(
                ['name' => $categoryData['name']],
                $categoryData,
            );

            foreach ($items as $item) {
                CatalogItem::updateOrCreate(
                    ['name' => $item['name'], 'catalog_category_id' => $category->id],
                    $item,
                );
            }
        }
    }
}
