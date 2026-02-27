<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\Correspondence;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\StateTaxRate;
use App\Services\SmsServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EstimateController extends Controller
{
    /**
     * GET /service-requests/{serviceRequest}/estimates/create
     */
    public function create(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['customer', 'catalogItem']);

        $categories = CatalogCategory::active()
            ->with(['items' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        // Try to determine state from the service request location
        $stateCode = $this->detectState($serviceRequest);
        $taxRate = $stateCode ? StateTaxRate::rateForState($stateCode) : null;
        $stateAutoDetected = $stateCode !== null;

        return view('estimates.create', compact(
            'serviceRequest',
            'categories',
            'stateCode',
            'taxRate',
            'stateAutoDetected',
        ));
    }

    /**
     * POST /service-requests/{serviceRequest}/estimates
     */
    public function store(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'state_code' => 'nullable|string|size:2',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.catalog_item_id' => 'nullable|integer|exists:catalog_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|in:each,mile,hour,gallon',
        ]);

        $estimate = DB::transaction(function () use ($validated, $serviceRequest) {
            $estimate = Estimate::create([
                'service_request_id' => $serviceRequest->id,
                'estimate_number' => Estimate::generateEstimateNumber(),
                'state_code' => $validated['state_code'] ? strtoupper($validated['state_code']) : null,
                'tax_rate' => $validated['tax_rate'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                EstimateItem::create([
                    'estimate_id' => $estimate->id,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'sort_order' => $index,
                ]);
            }

            $estimate->recalculate();

            return $estimate;
        });

        return redirect()
            ->route('estimates.show', [$serviceRequest, $estimate])
            ->with('success', 'Estimate created — $' . number_format($estimate->total, 2));
    }

    /**
     * GET /service-requests/{serviceRequest}/estimates/{estimate}
     */
    public function show(ServiceRequest $serviceRequest, Estimate $estimate)
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);

        $estimate->load('items');
        $serviceRequest->load(['customer', 'catalogItem']);
        $versions = $estimate->allVersions();

        return view('estimates.show', compact('serviceRequest', 'estimate', 'versions'));
    }

    /**
     * GET /service-requests/{serviceRequest}/estimates/{estimate}/edit
     */
    public function edit(ServiceRequest $serviceRequest, Estimate $estimate)
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);
        abort_if($estimate->is_locked, 403, 'This estimate version is locked.');

        $estimate->load('items');
        $serviceRequest->load(['customer', 'catalogItem']);

        $categories = CatalogCategory::active()
            ->with(['items' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('estimates.edit', compact(
            'serviceRequest',
            'estimate',
            'categories',
        ));
    }

    /**
     * PUT /service-requests/{serviceRequest}/estimates/{estimate}
     */
    public function update(Request $request, ServiceRequest $serviceRequest, Estimate $estimate): RedirectResponse
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);
        abort_if($estimate->is_locked, 403, 'This estimate version is locked.');

        $validated = $request->validate([
            'state_code' => 'nullable|string|size:2',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|string|in:draft,sent,pending_approval,accepted,declined',
            'items' => 'required|array|min:1',
            'items.*.catalog_item_id' => 'nullable|integer|exists:catalog_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|in:each,mile,hour,gallon',
        ]);

        DB::transaction(function () use ($validated, $estimate) {
            $estimate->update([
                'state_code' => $validated['state_code'] ? strtoupper($validated['state_code']) : null,
                'tax_rate' => $validated['tax_rate'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? $estimate->status,
            ]);

            // Replace all items
            $estimate->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                EstimateItem::create([
                    'estimate_id' => $estimate->id,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'sort_order' => $index,
                ]);
            }

            $estimate->recalculate();
        });

        return redirect()
            ->route('estimates.show', [$serviceRequest, $estimate])
            ->with('success', 'Estimate updated.');
    }

    /**
     * DELETE /service-requests/{serviceRequest}/estimates/{estimate}
     */
    public function destroy(ServiceRequest $serviceRequest, Estimate $estimate): RedirectResponse
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);
        abort_if($estimate->is_locked, 403, 'Locked estimate versions cannot be deleted.');

        $estimate->delete();

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with('success', 'Estimate deleted.');
    }

    /**
     * POST /service-requests/{serviceRequest}/estimates/{estimate}/revise
     * Create a new draft version from a sent estimate.
     */
    public function revise(ServiceRequest $serviceRequest, Estimate $estimate): RedirectResponse
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);
        abort_if($estimate->is_locked, 403, 'This version is already locked.');
        abort_unless(in_array($estimate->status, Estimate::REVISABLE_STATUSES, true), 403, 'This estimate cannot be revised in its current status.');

        $newVersion = DB::transaction(fn () => $estimate->createNewVersion());

        ServiceLog::log($serviceRequest, 'estimate_revised', [
            'old_version'  => $estimate->version,
            'new_version'  => $newVersion->version,
            'estimate_id'  => $newVersion->id,
            'estimate_number' => $newVersion->estimate_number,
        ], Auth::id());

        return redirect()
            ->route('estimates.edit', [$serviceRequest, $newVersion])
            ->with('success', "Revision V{$newVersion->version} created from locked V{$estimate->version}.");
    }

    /**
     * POST /service-requests/{serviceRequest}/estimates/{estimate}/request-approval
     * Generate approval token, SMS the customer, and set status to pending_approval.
     */
    public function requestApproval(ServiceRequest $serviceRequest, Estimate $estimate): RedirectResponse
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);
        abort_unless(in_array($estimate->status, ['sent', 'pending_approval']), 403, 'Only sent estimates can be sent for approval.');

        $serviceRequest->loadMissing('customer');
        $token = $estimate->generateApprovalToken();

        ServiceLog::log($serviceRequest, 'estimate_approval_requested', [
            'estimate_id'     => $estimate->id,
            'estimate_number' => $estimate->displayNumber(),
            'total'           => $estimate->total,
        ], Auth::id());

        // Send SMS if customer has a phone number
        $customer = $serviceRequest->customer;
        if ($customer && $customer->phone) {
            $companyName = Setting::getValue('company_name', config('app.name'));
            $approvalUrl = route('estimate-approval.show', $token);
            $text = "{$companyName}: Please review and approve your estimate for \${$estimate->total}. {$approvalUrl}";

            try {
                app(SmsServiceInterface::class)->sendRaw($customer->phone, $text);

                Correspondence::create([
                    'customer_id'        => $customer->id,
                    'service_request_id' => $serviceRequest->id,
                    'channel'            => Correspondence::CHANNEL_SMS,
                    'direction'          => Correspondence::DIRECTION_OUTBOUND,
                    'subject'            => 'Estimate approval request',
                    'body'               => $text,
                    'logged_by'          => Auth::id(),
                    'logged_at'          => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to send estimate approval SMS', ['error' => $e->getMessage()]);
            }
        }

        return redirect()
            ->route('estimates.show', [$serviceRequest, $estimate])
            ->with('success', 'Approval request sent. Customer will receive an SMS with a link to review and sign.');
    }

    /**
     * GET /api/state-tax-rate/{stateCode} — AJAX endpoint
     */
    public function taxRateForState(string $stateCode)
    {
        $stateCode = strtoupper(preg_replace('/[^a-zA-Z]/', '', $stateCode));

        if (strlen($stateCode) !== 2) {
            return response()->json(['rate' => null]);
        }

        $rate = StateTaxRate::rateForState($stateCode);

        return response()->json(['rate' => $rate]);
    }

    /**
     * Try to detect state code from the service request location address,
     * falling back to reverse geocoding from lat/lng if needed.
     */
    private function detectState(ServiceRequest $serviceRequest): ?string
    {
        $location = $serviceRequest->location;
        $states = StateTaxRate::stateList();

        if ($location) {
            // Try matching 2-letter state code near the end of the address (e.g. ", TX 75001")
            if (preg_match('/\b([A-Z]{2})\s+\d{5}\b/', strtoupper($location), $m)) {
                $code = $m[1];
                if (array_key_exists($code, $states)) {
                    return $code;
                }
            }

            // Try matching ", State," or ", State " pattern
            foreach ($states as $code => $name) {
                if (stripos($location, $name) !== false) {
                    return $code;
                }
            }
        }

        // Fallback: reverse geocode from lat/lng to extract state
        if ($serviceRequest->latitude && $serviceRequest->longitude) {
            return $this->reverseGeocodeState($serviceRequest, $states);
        }

        return null;
    }

    /**
     * Reverse geocode lat/lng via Google Maps API to extract the state code.
     * Also backfills the service request's location field as a side effect.
     */
    private function reverseGeocodeState(ServiceRequest $serviceRequest, array $states): ?string
    {
        $apiKey = Setting::getValue(
            'google_maps_api_key',
            (string) config('services.google_maps.api_key', ''),
        );

        if ($apiKey === '') {
            return null;
        }

        try {
            $url = sprintf(
                'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=%s',
                $serviceRequest->latitude,
                $serviceRequest->longitude,
                urlencode($apiKey),
            );

            $response = Http::timeout(5)->get($url);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                return null;
            }

            // Backfill the location text if missing
            $address = $data['results'][0]['formatted_address'] ?? null;
            if ($address && ! $serviceRequest->location) {
                $serviceRequest->update(['location' => $address]);
            }

            // Extract state from address_components
            foreach ($data['results'][0]['address_components'] ?? [] as $component) {
                if (in_array('administrative_area_level_1', $component['types'], true)) {
                    $shortName = strtoupper($component['short_name']);
                    if (array_key_exists($shortName, $states)) {
                        return $shortName;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('EstimateController: reverse geocode failed', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
