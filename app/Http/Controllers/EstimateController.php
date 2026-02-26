<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\StateTaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstimateController extends Controller
{
    /**
     * GET /service-requests/{serviceRequest}/estimates/create
     */
    public function create(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['customer', 'serviceType']);

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
        $serviceRequest->load(['customer', 'serviceType']);

        return view('estimates.show', compact('serviceRequest', 'estimate'));
    }

    /**
     * GET /service-requests/{serviceRequest}/estimates/{estimate}/edit
     */
    public function edit(ServiceRequest $serviceRequest, Estimate $estimate)
    {
        abort_if($estimate->service_request_id !== $serviceRequest->id, 404);

        $estimate->load('items');
        $serviceRequest->load(['customer', 'serviceType']);

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

        $validated = $request->validate([
            'state_code' => 'nullable|string|size:2',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|string|in:draft,sent,accepted,declined',
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

        $estimate->delete();

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with('success', 'Estimate deleted.');
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

            $response = file_get_contents($url);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

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
