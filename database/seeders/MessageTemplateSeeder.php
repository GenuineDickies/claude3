<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── 10DLC Compliance (keyword auto-responses) ─────────
            [
                'slug'      => 'keyword-opt-in',
                'name'      => 'Opt-In Confirmation (START)',
                'category'  => 'compliance',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Thanks for subscribing to roadside assistance updates! Reply HELP for help. Message frequency may vary. Msg&data rates may apply. Consent is not a condition of purchase. Reply STOP to opt out.',
            ],
            [
                'slug'      => 'keyword-opt-out',
                'name'      => 'Opt-Out Confirmation (STOP)',
                'category'  => 'compliance',
                'sort_order' => 2,
                'body'      => '{{ company_name }}: You are unsubscribed and will receive no further messages.',
            ],
            [
                'slug'      => 'keyword-help',
                'name'      => 'Help Response (HELP)',
                'category'  => 'compliance',
                'sort_order' => 3,
                'body'      => '{{ company_name }}: Please reach out to us at {{ company_phone }} for help.',
            ],

            // ── Location ──────────────────────────────────────────
            [
                'slug'      => 'location-request',
                'name'      => 'Location Finder',
                'category'  => 'dispatch',
                'sort_order' => 0,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your roadside assistance team needs your location. Please tap this link to share your GPS position: {{ location_link }} Msg&data rates may apply. Reply STOP to opt out, HELP for help.',
            ],

            // ── Dispatch & Status ─────────────────────────────────
            [
                'slug'      => 'dispatch-confirmation',
                'name'      => 'Dispatch Confirmation',
                'category'  => 'dispatch',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, we received your roadside assistance request (Ticket #{{ service_request_id }}). A technician is being dispatched to {{ location }}. Service: {{ service_type }}.',
            ],
            [
                'slug'      => 'technician-en-route',
                'name'      => 'Technician En Route',
                'category'  => 'dispatch',
                'sort_order' => 2,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your technician is on the way to {{ location }}. Please stay with your {{ vehicle_color }} {{ vehicle_year }} {{ vehicle_make }} {{ vehicle_model }}. Ticket #{{ service_request_id }}.',
            ],
            [
                'slug'      => 'technician-arrived',
                'name'      => 'Technician Arrived',
                'category'  => 'dispatch',
                'sort_order' => 3,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your technician has arrived at {{ location }}. Please look for our service vehicle. Ticket #{{ service_request_id }}.',
            ],
            [
                'slug'      => 'service-in-progress',
                'name'      => 'Service In Progress',
                'category'  => 'dispatch',
                'sort_order' => 4,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, work on your {{ vehicle_year }} {{ vehicle_make }} {{ vehicle_model }} is now underway. Service: {{ service_type }}. We\'ll notify you when done. Ticket #{{ service_request_id }}.',
            ],
            [
                'slug'      => 'service-completed',
                'name'      => 'Service Completed',
                'category'  => 'dispatch',
                'sort_order' => 5,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, the {{ service_type }} service on your {{ vehicle_year }} {{ vehicle_make }} {{ vehicle_model }} is complete. Ticket #{{ service_request_id }}. Thank you!',
            ],

            // ── Confirmations ─────────────────────────────────────
            [
                'slug'      => 'booking-confirmation',
                'name'      => 'Booking Confirmation',
                'category'  => 'confirmation',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your roadside assistance booking is confirmed. Ticket #{{ service_request_id }}. Service: {{ service_type }} at {{ location }}. Quoted price: ${{ quoted_price }}. Questions? Call {{ company_phone }}.',
            ],
            [
                'slug'      => 'cancellation-confirmation',
                'name'      => 'Cancellation Confirmation',
                'category'  => 'confirmation',
                'sort_order' => 2,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your service request (Ticket #{{ service_request_id }}) has been cancelled. If this was a mistake, call us at {{ company_phone }}.',
            ],

            // ── Follow-up ─────────────────────────────────────────
            [
                'slug'      => 'follow-up-satisfaction',
                'name'      => 'Satisfaction Follow-up',
                'category'  => 'follow_up',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, thank you for using our service! We hope your {{ service_type }} went smoothly. Feedback? Reply to this message or call {{ company_phone }}.',
            ],
            [
                'slug'      => 'follow-up-reminder',
                'name'      => 'Service Reminder',
                'category'  => 'follow_up',
                'sort_order' => 2,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, just a friendly reminder that we\'re here whenever you need roadside assistance. Save our number: {{ company_phone }}.',
            ],

            // ── Payment ───────────────────────────────────────────
            [
                'slug'      => 'payment-reminder',
                'name'      => 'Payment Reminder',
                'category'  => 'payment',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, the total for your {{ service_type }} service (Ticket #{{ service_request_id }}) is ${{ quoted_price }}. Please arrange payment at your convenience. Questions? Call {{ company_phone }}.',
            ],
            [
                'slug'      => 'payment-received',
                'name'      => 'Payment Received',
                'category'  => 'payment',
                'sort_order' => 2,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, we\'ve received your payment for Ticket #{{ service_request_id }}. Thank you!',
            ],

            // ── General ───────────────────────────────────────────
            [
                'slug'      => 'welcome-message',
                'name'      => 'Welcome / Opt-In Confirmation',
                'category'  => 'general',
                'sort_order' => 1,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}! You have agreed to receive SMS roadside assistance updates. Msg frequency may vary. Msg&data rates may apply. Reply STOP to opt out, HELP for help.',
            ],
            [
                'slug'      => 'inbound-auto-reply',
                'name'      => 'Inbound Auto-Reply',
                'category'  => 'general',
                'sort_order' => 2,
                'body'      => 'Thank you for your message to {{ company_name }}! We will be with you shortly. Msg frequency may vary. Msg&data rates may apply. Reply STOP to opt out, HELP for help. We will not share or sell your mobile information for marketing/promotional purposes.',
            ],
            [
                'slug'      => 'billing-address-updated',
                'name'      => 'Billing Address Updated',
                'category'  => 'general',
                'sort_order' => 3,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, your billing address has been updated. If you did not make this change, please contact us immediately at {{ company_phone }}.',
            ],
            [
                'slug'      => 'custom-message',
                'name'      => 'Custom / Freeform',
                'category'  => 'general',
                'sort_order' => 99,
                'body'      => '{{ company_name }}: Hi {{ customer_first_name }}, ',
            ],
        ];

        foreach ($templates as $data) {
            // Auto-detect variables from body
            $temp = new MessageTemplate(['body' => $data['body']]);
            $data['variables'] = $temp->extractPlaceholders();

            MessageTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
