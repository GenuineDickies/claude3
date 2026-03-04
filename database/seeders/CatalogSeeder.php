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
                'description' => 'Roadside service labor charges',
                'sort_order' => 1,
                'items' => [
                    ['name' => 'Flat Tire Change',  'base_cost' => 75.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Jump Start',        'base_cost' => 55.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 2],
                    ['name' => 'Lockout Service',   'base_cost' => 65.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 3],
                    ['name' => 'Winch Out',         'base_cost' => 150.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 4],
                    ['name' => 'Fuel Delivery',     'base_cost' => 60.00,  'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Towing',
                'description' => 'Towing services and mileage',
                'sort_order' => 2,
                'items' => [
                    ['name' => 'Base Tow Fee',   'base_cost' => 85.00, 'pricing_type' => 'fixed',    'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Per-Mile Tow',   'base_cost' => 3.50,  'pricing_type' => 'variable', 'unit' => 'mile', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Parts & Supplies',
                'description' => 'Replacement parts and supplies',
                'sort_order' => 3,
                'items' => [
                    ['name' => 'Spare Tire', 'base_cost' => 45.00, 'pricing_type' => 'fixed',    'unit' => 'each',   'sort_order' => 1],
                    ['name' => 'Battery',    'base_cost' => 120.00,'pricing_type' => 'fixed',    'unit' => 'each',   'sort_order' => 2],
                    ['name' => 'Fuel',       'base_cost' => 15.00, 'pricing_type' => 'variable', 'unit' => 'gallon', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Fees',
                'description' => 'Additional surcharges and fees',
                'sort_order' => 4,
                'items' => [
                    ['name' => 'After-Hours Surcharge',   'base_cost' => 35.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 1],
                    ['name' => 'Emergency Dispatch Fee',  'base_cost' => 25.00, 'pricing_type' => 'fixed', 'unit' => 'each', 'sort_order' => 2],
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
