<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have at least one active staff user to assign records.
        $staffUser = User::query()->firstOrCreate(
            ['email' => 'dispatch.admin@example.com'],
            [
                'name' => 'Dispatch Admin',
                'username' => 'dispatch.admin',
                'status' => 'active',
                'password' => Hash::make((string) env('DEMO_DISPATCH_ADMIN_PASSWORD', Str::random(32))),
            ]
        );

        $customers = [
            ['first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '8135551001'],
            ['first_name' => 'James', 'last_name' => 'Turner', 'phone' => '8135551002'],
            ['first_name' => 'Ava', 'last_name' => 'Patel', 'phone' => '8135551003'],
            ['first_name' => 'Noah', 'last_name' => 'Brooks', 'phone' => '8135551004'],
            ['first_name' => 'Sophia', 'last_name' => 'Nguyen', 'phone' => '8135551005'],
            ['first_name' => 'Ethan', 'last_name' => 'Rivera', 'phone' => '8135551006'],
        ];

        foreach ($customers as $customerData) {
            Customer::query()->firstOrCreate(
                ['phone' => $customerData['phone']],
                [
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'is_active' => true,
                ]
            );
        }

        $customerIds = Customer::query()->orderBy('id')->pluck('id')->all();

        $serviceRequests = [
            [
                'customer_id' => $customerIds[0] ?? null,
                'status' => 'new',
                'location' => 'I-275 NB near Exit 45, Tampa, FL',
                'notes' => 'Vehicle not starting in shoulder lane.',
                'vehicle_year' => '2018',
                'vehicle_make' => 'Honda',
                'vehicle_model' => 'Civic',
                'vehicle_color' => 'Blue',
                'quoted_price' => 95.00,
            ],
            [
                'customer_id' => $customerIds[1] ?? null,
                'status' => 'dispatched',
                'location' => 'Downtown lot at 802 Franklin St, Tampa, FL',
                'notes' => 'Possible lockout; keys inside vehicle.',
                'vehicle_year' => '2020',
                'vehicle_make' => 'Toyota',
                'vehicle_model' => 'Camry',
                'vehicle_color' => 'Silver',
                'quoted_price' => 85.00,
            ],
            [
                'customer_id' => $customerIds[2] ?? null,
                'status' => 'en_route',
                'location' => 'US-301 S by county line marker 12',
                'notes' => 'Flat tire roadside assist needed.',
                'vehicle_year' => '2019',
                'vehicle_make' => 'Ford',
                'vehicle_model' => 'Escape',
                'vehicle_color' => 'White',
                'quoted_price' => 110.00,
            ],
            [
                'customer_id' => $customerIds[3] ?? null,
                'status' => 'on_scene',
                'location' => 'Gas station at 5001 E Busch Blvd, Tampa, FL',
                'notes' => 'Battery jump requested by customer.',
                'vehicle_year' => '2017',
                'vehicle_make' => 'Nissan',
                'vehicle_model' => 'Altima',
                'vehicle_color' => 'Black',
                'quoted_price' => 75.00,
            ],
            [
                'customer_id' => $customerIds[4] ?? null,
                'status' => 'completed',
                'location' => 'Mall parking deck level 2',
                'notes' => 'Tire replacement complete, customer resumed travel.',
                'vehicle_year' => '2022',
                'vehicle_make' => 'Hyundai',
                'vehicle_model' => 'Elantra',
                'vehicle_color' => 'Red',
                'quoted_price' => 140.00,
            ],
        ];

        foreach ($serviceRequests as $index => $requestData) {
            if (! $requestData['customer_id']) {
                continue;
            }

            ServiceRequest::query()->firstOrCreate(
                [
                    'customer_id' => $requestData['customer_id'],
                    'status' => $requestData['status'],
                    'location' => $requestData['location'],
                ],
                array_merge($requestData, [
                    'assigned_user_id' => $staffUser->id,
                ])
            );
        }

        $leadRows = [
            [
                'first_name' => 'Liam',
                'last_name' => 'Cook',
                'phone' => '8135552001',
                'email' => 'liam.cook@example.com',
                'stage' => Lead::STAGE_NEW,
                'source' => 'inbound_call',
                'service_needed' => 'Jump Start',
                'location' => 'Westshore Blvd near airport',
                'notes' => 'Customer waiting in parking lot.',
                'estimated_value' => 85.00,
            ],
            [
                'first_name' => 'Mia',
                'last_name' => 'Foster',
                'phone' => '8135552002',
                'email' => 'mia.foster@example.com',
                'stage' => Lead::STAGE_INTAKE_VERIFIED,
                'source' => 'website',
                'service_needed' => 'Lockout',
                'location' => 'Ybor City',
                'notes' => 'Vehicle secured, no hazards.',
                'estimated_value' => 95.00,
            ],
            [
                'first_name' => 'Oliver',
                'last_name' => 'Price',
                'phone' => '8135552003',
                'email' => null,
                'stage' => Lead::STAGE_DISPATCH_READY,
                'source' => 'inbound_call',
                'service_needed' => 'Tow',
                'location' => 'US-41 by mile marker 6',
                'notes' => 'Tow destination provided on callback.',
                'estimated_value' => 165.00,
            ],
            [
                'first_name' => 'Amelia',
                'last_name' => 'Ward',
                'phone' => '8135552004',
                'email' => 'amelia.ward@example.com',
                'stage' => Lead::STAGE_WAITING_CUSTOMER,
                'source' => 'google_business',
                'service_needed' => 'Fuel Delivery',
                'location' => 'Brandon Blvd',
                'notes' => 'Customer confirming exact side-street location.',
                'estimated_value' => 70.00,
            ],
            [
                'first_name' => 'Henry',
                'last_name' => 'Perry',
                'phone' => '8135552005',
                'email' => 'henry.perry@example.com',
                'stage' => Lead::STAGE_CLOSED_NO_SERVICE,
                'source' => 'inbound_call',
                'service_needed' => 'Battery Service',
                'location' => 'Temple Terrace',
                'notes' => 'Issue resolved before dispatch.',
                'estimated_value' => 0,
            ],
        ];

        foreach ($leadRows as $leadData) {
            Lead::query()->firstOrCreate(
                ['phone' => $leadData['phone']],
                array_merge($leadData, [
                    'assigned_user_id' => $staffUser->id,
                ])
            );
        }
    }
}
