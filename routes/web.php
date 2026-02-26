<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\LocationShareController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ServiceLogController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\StateTaxRateController;
use App\Http\Controllers\WarrantyController;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Services\SmsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public routes (no auth) ───────────────────────────────────
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Public location share page (customer facing — no auth)
Route::get('/locate/{token}', [LocationShareController::class, 'show'])->name('locate.show');

// Public signature capture page (customer facing — token-based, no auth)
Route::get('/sign/{token}', [SignatureController::class, 'show'])->name('signature.show');
Route::post('/sign/{token}', [SignatureController::class, 'store'])->name('signature.store');

// ── Authenticated routes ──────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard (replaces old welcome page)
    Route::get('/dashboard', function () {
        $open      = ServiceRequest::where('status', 'new')->count();
        $today     = ServiceRequest::whereDate('created_at', today())->count();
        $customers = Customer::where('is_active', true)->count();
        $recent    = ServiceRequest::with('customer', 'serviceType')
                        ->latest()
                        ->take(5)
                        ->get();

        return view('welcome', compact('open', 'today', 'customers', 'recent'));
    })->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Service Requests
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    // Service Types
    Route::get('/service-types', [ServiceTypeController::class, 'index'])->name('service-types.index');
    Route::post('/service-types', [ServiceTypeController::class, 'store'])->name('service-types.store');
    Route::put('/service-types/{serviceType}', [ServiceTypeController::class, 'update'])->name('service-types.update');
    Route::patch('/service-types/{serviceType}/toggle', [ServiceTypeController::class, 'toggle'])->name('service-types.toggle');
    Route::post('/service-types/reorder', [ServiceTypeController::class, 'reorder'])->name('service-types.reorder');

    Route::get('/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');
    Route::post('/service-requests', [ServiceRequestController::class, 'store'])->name('service-requests.store');
    Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::patch('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('service-requests.update');
    Route::post('/service-requests/{serviceRequest}/request-location', [LocationShareController::class, 'request'])->name('service-requests.request-location');
    Route::post('/service-requests/{serviceRequest}/messages', [MessageController::class, 'store'])->name('service-requests.messages.store');

    // Message Templates
    Route::resource('message-templates', MessageTemplateController::class);
    Route::post('/message-templates/preview', [MessageTemplateController::class, 'preview'])->name('message-templates.preview');

    // Catalog
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/categories/create', [CatalogController::class, 'createCategory'])->name('catalog.categories.create');
    Route::post('/catalog/categories', [CatalogController::class, 'storeCategory'])->name('catalog.categories.store');
    Route::get('/catalog/categories/{category}', [CatalogController::class, 'showCategory'])->name('catalog.categories.show');
    Route::get('/catalog/categories/{category}/edit', [CatalogController::class, 'editCategory'])->name('catalog.categories.edit');
    Route::put('/catalog/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalog.categories.update');
    Route::delete('/catalog/categories/{category}', [CatalogController::class, 'destroyCategory'])->name('catalog.categories.destroy');
    Route::get('/catalog/categories/{category}/items/create', [CatalogController::class, 'createItem'])->name('catalog.items.create');
    Route::post('/catalog/categories/{category}/items', [CatalogController::class, 'storeItem'])->name('catalog.items.store');
    Route::get('/catalog/categories/{category}/items/{item}/edit', [CatalogController::class, 'editItem'])->name('catalog.items.edit');
    Route::put('/catalog/categories/{category}/items/{item}', [CatalogController::class, 'updateItem'])->name('catalog.items.update');
    Route::delete('/catalog/categories/{category}/items/{item}', [CatalogController::class, 'destroyItem'])->name('catalog.items.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // State Tax Rates (must be before /settings/{key} to avoid catch-all match)
    Route::get('/settings/tax-rates', [StateTaxRateController::class, 'index'])->name('settings.tax-rates');
    Route::put('/settings/tax-rates', [StateTaxRateController::class, 'update'])->name('settings.tax-rates.update');

    Route::put('/settings/{key}', [SettingsController::class, 'updateSingle'])->name('settings.update-single');

    // Estimates
    Route::get('/service-requests/{serviceRequest}/estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
    Route::post('/service-requests/{serviceRequest}/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::get('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
    Route::get('/service-requests/{serviceRequest}/estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
    Route::put('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');

    // Photos
    Route::post('/service-requests/{serviceRequest}/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::get('/service-requests/{serviceRequest}/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::delete('/service-requests/{serviceRequest}/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');

    // Signatures
    Route::post('/service-requests/{serviceRequest}/signatures/request', [SignatureController::class, 'request'])->name('signatures.request');

    // Payment Records
    Route::post('/service-requests/{serviceRequest}/payments', [PaymentRecordController::class, 'store'])->name('payments.store');
    Route::delete('/service-requests/{serviceRequest}/payments/{payment}', [PaymentRecordController::class, 'destroy'])->name('payments.destroy');

    // Service Logs
    Route::post('/service-requests/{serviceRequest}/logs', [ServiceLogController::class, 'store'])->name('service-logs.store');

    // Evidence Package
    Route::get('/service-requests/{serviceRequest}/evidence', [ServiceRequestController::class, 'evidence'])->name('service-requests.evidence');

    // Warranties
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/service-requests/{serviceRequest}/warranties/create', [WarrantyController::class, 'create'])->name('warranties.create');
    Route::post('/service-requests/{serviceRequest}/warranties', [WarrantyController::class, 'store'])->name('warranties.store');
    Route::get('/service-requests/{serviceRequest}/warranties/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::get('/service-requests/{serviceRequest}/warranties/{warranty}/edit', [WarrantyController::class, 'edit'])->name('warranties.edit');
    Route::put('/service-requests/{serviceRequest}/warranties/{warranty}', [WarrantyController::class, 'update'])->name('warranties.update');
    Route::delete('/service-requests/{serviceRequest}/warranties/{warranty}', [WarrantyController::class, 'destroy'])->name('warranties.destroy');

    // Documents (polymorphic — currently attached to warranties)
    Route::post('/warranties/{warranty}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Receipts
    Route::get('/service-requests/{serviceRequest}/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('/service-requests/{serviceRequest}/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('/service-requests/{serviceRequest}/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/service-requests/{serviceRequest}/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');

    // AJAX endpoints (same-origin, session-auth)
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    Route::get('/api/service-types', function () {
        return \App\Models\ServiceType::where('is_active', true)->orderBy('sort_order')->get();
    });
    Route::get('/api/state-tax-rate/{stateCode}', [EstimateController::class, 'taxRateForState'])->name('api.state-tax-rate');
    Route::get('/api/message-templates', function () {
        return \App\Models\MessageTemplate::active()
            ->whereNotIn('category', ['compliance'])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'category', 'body']);
    });
    Route::post('/api/message-templates/render', function (Request $request) {
        $request->validate([
            'template_id'       => 'required|integer|exists:message_templates,id',
            'service_request_id' => 'required|integer|exists:service_requests,id',
        ]);
        $template = \App\Models\MessageTemplate::findOrFail($request->input('template_id'));
        $sr = \App\Models\ServiceRequest::with(['customer', 'serviceType'])->findOrFail($request->input('service_request_id'));
        return response()->json(['rendered' => $template->renderWith($sr->customer, $sr)]);
    })->name('api.message-templates.render');

    // ── Test Location Request (dev only) ──────────────────────
    if (app()->environment('local', 'testing')) {
        Route::get('/test-location', fn () => view('test-location'));
        Route::post('/test-location', function (Request $request) {
            $request->validate([
                'first_name' => 'required|string|max:100',
                'phone'      => 'required|string|max:20',
            ]);

            $phone = preg_replace('/\D/', '', $request->input('phone'));
            $firstName = $request->input('first_name');

            $customer = Customer::firstOrCreate(
                ['phone' => $phone, 'is_active' => true],
                ['first_name' => $firstName, 'last_name' => 'Test'],
            );
            $customer->update(['first_name' => $firstName]);

            if ($request->boolean('force_consent') && ! $customer->hasSmsConsent()) {
                $customer->grantSmsConsent();
            }

            $serviceRequest = $customer->serviceRequests()->where('status', 'new')->latest()->first();
            if (! $serviceRequest) {
                $serviceRequest = ServiceRequest::create([
                    'customer_id' => $customer->id,
                    'status'      => 'new',
                ]);
            }

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
                return back()->with('warning', 'Customer has not opted in. An opt-in message was sent to ' . $phone . '. Reply START to that text, then try again.');
            }

            $serviceRequest->generateLocationToken();

            $template = MessageTemplate::where('slug', 'location-request')->first();

            if ($template) {
                $result = $sms->sendTemplate(
                    template: $template,
                    to: $customer->phone,
                    customer: $customer,
                    serviceRequest: $serviceRequest,
                    overrides: ['location_link' => $serviceRequest->locationShareUrl()],
                );
            } else {
                $link = $serviceRequest->locationShareUrl();
                $companyName = \App\Models\Setting::getValue('company_name', config('app.name'));
                $result = $sms->sendRaw(
                    $customer->phone,
                    $companyName . ': Hi ' . $firstName . ', tap to share your location: ' . $link . ' Reply STOP to opt out.',
                );
            }

            $detailUrl = route('service-requests.show', $serviceRequest);
            $info = 'Location link: <a href="' . e($serviceRequest->locationShareUrl()) . '" target="_blank" class="underline font-mono">' . e($serviceRequest->locationShareUrl()) . '</a>'
                . '<br>View result: <a href="' . e($detailUrl) . '" target="_blank" class="underline font-mono">' . e($detailUrl) . '</a>';

            if ($result['success'] ?? false) {
                return back()->with('success', 'Location request SMS sent to ' . $phone . '.')->with('info', $info);
            }

            return back()->with('error', 'SMS failed: ' . ($result['error'] ?? 'unknown'))->with('info', $info);
        });
    }
});

require __DIR__.'/auth.php';
