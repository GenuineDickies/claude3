<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Flat Tire Change', 'default_price' => 75.00, 'sort_order' => 1],
            ['name' => 'Tow',              'default_price' => 125.00, 'sort_order' => 2],
            ['name' => 'Lockout Service',   'default_price' => 65.00, 'sort_order' => 3],
            ['name' => 'Jump Start',        'default_price' => 55.00, 'sort_order' => 4],
            ['name' => 'Fuel Delivery',     'default_price' => 60.00, 'sort_order' => 5],
            ['name' => 'Winch Out',         'default_price' => 150.00, 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            ServiceType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
