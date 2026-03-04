<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Services\ShorthandParserService;
use App\Services\SmsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RapidDispatchController extends Controller
{
    public function create()
    {
        $serviceCategories = CatalogCategory::where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('service-requests.rapid', compact('serviceCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone'           => 'required|string|max:20',
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'quoted_price'    => 'required|numeric|min:0',
            'location'        => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:1000',
            'send_location_request' => 'nullable|boolean',
        ]);

        $phone = preg_replace('/\D/', '', $validated['phone']);

        $serviceRequest = DB::transaction(function () use ($validated, $phone) {
            // Find or create customer
            $customer = Customer::where('phone', $phone)
                ->where('is_active', true)
                ->first();

            if ($customer) {
                $customer->update([
                    'first_name' => $validated['first_name'],
                    'last_name'  => $validated['last_name'],
                ]);
            } else {
                $customer = Customer::create([
                    'first_name' => $validated['first_name'],
                    'last_name'  => $validated['last_name'],
                    'phone'      => $phone,
                    'is_active'  => true,
                ]);
            }

            return ServiceRequest::create([
                'customer_id'    => $customer->id,
                'catalog_item_id' => $validated['catalog_item_id'],
                'quoted_price'   => $validated['quoted_price'],
                'location'       => $validated['location'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'new',
            ]);
        });

        // Send location request SMS if requested
        if ($request->boolean('send_location_request')) {
            $this->sendLocationRequest($serviceRequest);
        }

        $name = $validated['first_name'] . ' ' . $validated['last_name'];

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', "Rapid dispatch #{$serviceRequest->id} created for {$name}.");
    }

    /**
     * AJAX: parse shorthand text and return matching catalog item.
     */
    public function parse(Request $request, ShorthandParserService $parser)
    {
        $result = $parser->parse($request->input('q', ''));

        if (! $result['matched']) {
            return response()->json(['matched' => false]);
        }

        $item = $result['catalog_item'];

        return response()->json([
            'matched'         => true,
            'catalog_item_id' => $item->id,
            'name'            => $item->name,
            'unit_price'      => (float) $item->base_cost,
            'keyword'         => $result['keyword'],
        ]);
    }

    private function sendLocationRequest(ServiceRequest $serviceRequest): void
    {
        $customer = $serviceRequest->customer;
        $sms = app(SmsServiceInterface::class);

        if (! $customer->hasSmsConsent()) {
            $optInTemplate = MessageTemplate::where('slug', 'welcome-message')->first();
            if ($optInTemplate) {
                $sms->sendTemplate(
                    template: $optInTemplate,
                    to: $customer->phone,
                    customer: $customer,
                    serviceRequest: $serviceRequest,
                );
            }
            return;
        }

        $serviceRequest->generateLocationToken();

        $template = MessageTemplate::where('slug', 'location-request')->first();
        if ($template) {
            $sms->sendTemplate(
                template: $template,
                to: $customer->phone,
                customer: $customer,
                serviceRequest: $serviceRequest,
                overrides: ['location_link' => $serviceRequest->locationShareUrl()],
            );
        } else {
            $companyName = Setting::getValue('company_name', config('app.name'));
            $rawText = $companyName . ': Hi ' . $customer->first_name
                . ', please tap this link so we can locate you: '
                . $serviceRequest->locationShareUrl()
                . ' Reply STOP to opt out.';
            $sms->sendRawWithLog(
                to: $customer->phone,
                text: $rawText,
                customer: $customer,
                serviceRequest: $serviceRequest,
                subject: 'Location request',
                loggedBy: Auth::id(),
            );
        }
    }
}
