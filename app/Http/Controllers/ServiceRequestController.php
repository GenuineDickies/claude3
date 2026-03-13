<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\Correspondence;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusLog;
use App\Models\Setting;
use App\Models\CatalogCategory;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $query = ServiceRequest::with(['customer', 'catalogItem'])
            ->latest();

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $serviceRequests = $query->paginate(15);

        return view('service-requests.index', [
            'serviceRequests' => $serviceRequests,
            'currentStatus' => $status,
            'currentSearch' => $search,
        ]);
    }

    public function create()
    {
        $serviceCategories = CatalogCategory::where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('service-requests.create', compact('serviceCategories'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        // If a location token is pending and no coordinates yet, try to sync
        // from the standalone capture file on the hosting server.
        if (
            $serviceRequest->location_token
            && is_null($serviceRequest->latitude)
            && is_null($serviceRequest->location_shared_at)
        ) {
            $this->syncLocationFromCapture($serviceRequest);
        }

        $serviceRequest->load(['customer', 'catalogItem', 'messages', 'estimates.items', 'statusLogs.user', 'receipts', 'photos', 'signatures', 'paymentRecords', 'serviceLogs.user', 'workOrders.items', 'correspondences.logger', 'documents.uploader', 'assignedTechnician.technicianProfile', 'invoices']);

        $messageTemplates = MessageTemplate::active()
            ->whereNotIn('category', ['compliance'])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'category', 'body']);

        $technicians = User::whereHas('technicianProfile')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('service-requests.show', compact('serviceRequest', 'messageTemplates', 'technicians'));
    }

    public function store(StoreServiceRequestRequest $request)
    {
        $validated = $request->validated();

        $phone = Customer::normalizePhone($validated['phone']);

        $serviceRequest = DB::transaction(function () use ($validated, $phone) {
            if ($validated['customer_action'] === 'use_existing') {
                $customer = Customer::findActiveByPhone($phone);

                if ($customer) {
                    $customer->update([
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                    ]);
                } else {
                    // Fallback if active customer was deleted between AJAX lookup and form submit
                    $customer = Customer::create([
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'phone' => $phone,
                        'is_active' => true,
                    ]);
                }
            } else {
                // Deactivate existing active customers with this phone
                Customer::query()
                    ->wherePhoneMatches($phone)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $customer = Customer::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'phone' => $phone,
                    'is_active' => true,
                ]);
            }

            return ServiceRequest::create([
                'customer_id' => $customer->id,
                'vehicle_year' => $validated['vehicle_year'],
                'vehicle_make' => $validated['vehicle_make'],
                'vehicle_model' => $validated['vehicle_model'],
                'vehicle_color' => $validated['vehicle_color'] ?? null,
                'catalog_item_id' => $validated['catalog_item_id'],
                'quoted_price' => $validated['quoted_price'],
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'new',
            ]);
        });

        // ── Verbal opt-in: grant consent immediately ──────────────
        if ($request->boolean('verbal_opt_in')) {
            $customer = $serviceRequest->customer;
            if (! $customer->hasSmsConsent()) {
                $customer->grantSmsConsent([
                    'source' => 'verbal_intake',
                    'recorded_by_user_id' => Auth::id(),
                    'service_request_id' => $serviceRequest->id,
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]);
            }
        }

        // ── Optional: send location request SMS ──────────────────────
        if ($request->boolean('send_location_request')) {
            $customer = $serviceRequest->customer;
            $sms = app(SmsServiceInterface::class);

            if (! $customer->hasSmsConsent()) {
                return redirect()->route('service-requests.show', $serviceRequest)
                    ->with('success', 'Service request #' . $serviceRequest->id . ' created.')
                    ->with('warning', 'Customer has not opted in to SMS. Record verbal consent before sending location or status text messages.');
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
                $companyName = \App\Models\Setting::getValue('company_name', config('app.name'));
                $sms->sendRaw(
                    $customer->phone,
                    $companyName . ': Hi ' . $customer->first_name . ', please tap this link so we can locate you: ' . $serviceRequest->locationShareUrl() . ' Reply STOP to opt out.',
                );
            }

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Service request #' . $serviceRequest->id . ' created for ' . $validated['first_name'] . ' ' . $validated['last_name'] . '. Location request SMS sent.');
        }

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Service request #' . $serviceRequest->id . ' created for ' . $validated['first_name'] . ' ' . $validated['last_name'] . '.');
    }

    public function evidence(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load([
            'customer', 'catalogItem', 'photos', 'signatures',
            'paymentRecords', 'serviceLogs.user', 'receipts', 'statusLogs.user',
        ]);

        $companyName = Setting::getValue('company_name', config('app.name'));

        return view('service-requests.evidence', compact('serviceRequest', 'companyName'));
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', ServiceRequest::STATUSES),
            'notes'  => 'nullable|string|max:1000',
        ]);

        $newStatus = $request->input('status');

        if (! $serviceRequest->canTransitionTo($newStatus)) {
            $error = $newStatus === 'dispatched' && $serviceRequest->status === 'new'
                ? ($serviceRequest->dispatchBlockedReason() ?? 'Dispatch requirements are not met.')
                : 'Cannot transition from "' . $serviceRequest->statusLabel() . '" to "' . (ServiceRequest::STATUS_LABELS[$newStatus] ?? $newStatus) . '".';

            return back()->with('error', $error);
        }

        $oldStatus = $serviceRequest->status;

        DB::transaction(function () use ($serviceRequest, $oldStatus, $newStatus, $request) {
            $serviceRequest->update(['status' => $newStatus]);

            ServiceRequestStatusLog::create([
                'service_request_id' => $serviceRequest->id,
                'old_status'         => $oldStatus,
                'new_status'         => $newStatus,
                'changed_by'         => Auth::id(),
                'notes'              => $request->input('notes'),
            ]);

            // Auto-log status change to service log
            ServiceLog::log($serviceRequest, 'status_change', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes'      => $request->input('notes'),
            ], Auth::id());
        });

        // Optional SMS notification
        if ($request->boolean('notify_customer')) {
            $this->sendStatusSms($serviceRequest, $newStatus);
        }

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Status updated to "' . (ServiceRequest::STATUS_LABELS[$newStatus] ?? $newStatus) . '".');
    }

    public function assignTechnician(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'assigned_user_id' => 'required|exists:users,id',
        ]);

        $user = User::whereHas('technicianProfile')
            ->where('status', 'active')
            ->findOrFail($request->input('assigned_user_id'));

        $serviceRequest->update(['assigned_user_id' => $user->id]);

        ServiceLog::log($serviceRequest, 'technician_assigned', [
            'technician_name' => $user->name,
            'technician_id'   => $user->id,
        ], Auth::id());

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Technician assigned: ' . $user->name);
    }

    public function sendLocationToTechnician(ServiceRequest $serviceRequest, SmsServiceInterface $sms)
    {
        $serviceRequest->loadMissing(['customer', 'assignedTechnician.technicianProfile']);

        if (! filled((string) $serviceRequest->location)) {
            return back()->with('error', 'Add a service address before sending the location to a technician.');
        }

        $technician = $serviceRequest->assignedTechnician;

        if (! $technician) {
            return back()->with('error', 'Assign a technician before sending the location by SMS.');
        }

        $smsPhone = $technician->phone;

        if (! filled((string) $smsPhone)) {
            return back()->with('error', 'The assigned technician does not have a mobile phone number yet.');
        }

        if (! $technician->technicianProfile?->hasSmsConsent()) {
            return back()->with('error', 'The assigned technician must grant SMS consent before dispatch texts can be sent.');
        }

        $text = $this->buildTechnicianLocationSms($serviceRequest);
        $result = $sms->sendRaw($smsPhone, $text);

        Correspondence::create([
            'customer_id' => $serviceRequest->customer_id,
            'service_request_id' => $serviceRequest->id,
            'channel' => Correspondence::CHANNEL_SMS,
            'direction' => Correspondence::DIRECTION_OUTBOUND,
            'subject' => 'Technician location SMS',
            'body' => $text,
            'logged_by' => Auth::id(),
            'logged_at' => now(),
            'outcome' => $result['success'] ? 'sent_to_technician' : 'failed_to_technician',
        ]);

        ServiceLog::log($serviceRequest, 'technician_location_sent', [
            'technician_name' => $technician->name,
            'recipient_phone' => $smsPhone,
            'address' => $serviceRequest->location,
            'sms_status' => $result['success'] ? 'sent' : 'failed',
            'sms_error' => $result['error'],
        ], Auth::id());

        if (! $result['success']) {
            return back()->with('error', 'The technician SMS could not be sent: ' . ($result['error'] ?: 'Unknown error.'));
        }

        return back()->with('success', 'Location sent to technician ' . $technician->name . '.');
    }

    /**
     * Send an SMS for a status change if a matching template exists.
     */
    private function sendStatusSms(ServiceRequest $serviceRequest, string $newStatus): void
    {
        $customer = $serviceRequest->customer;
        if (! $customer || ! $customer->hasSmsConsent()) {
            return;
        }

        if (! $customer->wantsNotification('status_updates')) {
            return;
        }

        // Map statuses to existing template slugs
        $slugMap = [
            'dispatched' => 'dispatch-confirmation',
            'en_route'   => 'technician-en-route',
            'on_scene'   => 'technician-arrived',
            'completed'  => 'service-completed',
            'cancelled'  => 'cancellation-confirmation',
        ];

        $slug = $slugMap[$newStatus] ?? null;
        if (! $slug) {
            return;
        }

        $template = MessageTemplate::where('slug', $slug)->first();
        if (! $template) {
            return;
        }

        $serviceRequest->loadMissing(['customer', 'catalogItem']);

        $sms = app(SmsServiceInterface::class);
        $sms->sendTemplate(
            template: $template,
            to: $customer->phone,
            customer: $customer,
            serviceRequest: $serviceRequest,
        );
    }


    private function buildTechnicianLocationSms(ServiceRequest $serviceRequest): string
    {
        $companyName = Setting::getValue('company_name', config('app.name'));
        $customerName = trim(($serviceRequest->customer?->first_name ?? '') . ' ' . ($serviceRequest->customer?->last_name ?? ''));
        $mapsUrl = $this->technicianLocationMapsUrl($serviceRequest);

        return sprintf(
            '%s dispatch: Ticket #%d%s. Service address: %s. Directions: %s Reply STOP to stop dispatch texts or HELP for support.',
            $companyName,
            $serviceRequest->id,
            $customerName !== '' ? ' for ' . $customerName : '',
            trim((string) $serviceRequest->location),
            $mapsUrl,
        );
    }

    private function technicianLocationMapsUrl(ServiceRequest $serviceRequest): string
    {
        if ($serviceRequest->latitude && $serviceRequest->longitude) {
            return 'https://www.google.com/maps/dir/?api=1&destination=' . $serviceRequest->latitude . ',' . $serviceRequest->longitude;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $serviceRequest->location);
    }

    /**
     * Pull GPS data from the remote capture JSON and update the local DB.
     *
     * During local dev the standalone locate.php writes to the hosting DB
     * (not ours), so we bridge the gap by fetching the publicly-accessible
     * capture file.  In production this is a no-op because locate.php
     * already writes to the same database.
     */
    private function syncLocationFromCapture(ServiceRequest $serviceRequest): void
    {
        $base = Setting::getValue('location_base_url', config('services.location.base_url'));

        if (! $base || ! $serviceRequest->location_token) {
            return;
        }

        // Rate-limit sync attempts — don't hammer a failing endpoint on every
        // auto-refresh (the show page refreshes every 10 seconds).
        $cacheKey = 'loc_sync_backoff:' . $serviceRequest->id;
        if (Cache::has($cacheKey)) {
            return;
        }

        // Build capture URL: strip trailing path segment + append captures/{token}.json
        $sanitizedToken = preg_replace('/[^a-zA-Z0-9]/', '', $serviceRequest->location_token);
        $baseDir = preg_replace('/[?#].*$/', '', $base);  // strip query/fragment
        $baseDir = rtrim($baseDir, '/');
        $baseDir = substr($baseDir, 0, strrpos($baseDir, '/'));
        $captureUrl = $baseDir . '/captures/' . $sanitizedToken . '.json';

        try {
            $response = Http::connectTimeout(2)->timeout(3)->get($captureUrl);

            if ($response->failed()) {
                // Back off for 30 seconds before retrying
                Cache::put($cacheKey, true, 30);

                return; // File doesn't exist yet — customer hasn't shared location
            }

            $data = $response->json();

            if (
                ! is_array($data)
                || ! isset($data['latitude'], $data['longitude'])
                || ! is_numeric($data['latitude'])
                || ! is_numeric($data['longitude'])
            ) {
                return;
            }

            $serviceRequest->update([
                'latitude'          => (float) $data['latitude'],
                'longitude'         => (float) $data['longitude'],
                'location_shared_at' => now(),
            ]);

            // Clear the backoff cache since we succeeded
            Cache::forget($cacheKey);

            Log::info('Location synced from remote capture file', [
                'service_request_id' => $serviceRequest->id,
                'lat'                => $data['latitude'],
                'lng'                => $data['longitude'],
                'capture_time'       => $data['time'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Back off for 60 seconds after connection/DNS failures
            Cache::put($cacheKey, true, 60);

            Log::warning('Failed to sync location from capture file', [
                'service_request_id' => $serviceRequest->id,
                'url'                => $captureUrl,
                'error'              => $e->getMessage(),
            ]);
        }
    }
}
