<?php

use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ApiMonitorController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorDocumentController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ChangeOrderController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentInboxController;
use App\Http\Controllers\EstimateApprovalController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocationShareController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RapidDispatchController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ServiceLogController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\StateTaxRateController;
use App\Http\Controllers\TechnicianProfileController;
use App\Http\Controllers\TransactionImportController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\WorkOrderController;
use App\Models\Customer;
use App\Models\ApiMonitorEndpoint;
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
Route::get('/branding/logo', [BrandingController::class, 'logo'])->name('branding.logo');
Route::get('/locate.php', [LocationShareController::class, 'legacyShow'])->name('locate.legacy');
Route::get('/locate/{token}', [LocationShareController::class, 'show'])->name('locate.show');

// Public signature capture page (customer facing — token-based, no auth)
Route::get('/sign/{token}', [SignatureController::class, 'show'])->name('signature.show');
Route::post('/sign/{token}', [SignatureController::class, 'store'])->name('signature.store');

// Public change order approval page (customer facing — token-based, no auth)
Route::get('/change-orders/{token}', [ChangeOrderController::class, 'show'])->name('change-orders.show');
Route::post('/change-orders/{token}', [ChangeOrderController::class, 'approve'])->name('change-orders.approve');

// Public estimate approval page (customer facing — token-based, no auth)
Route::get('/estimates/approve/{token}', [EstimateApprovalController::class, 'show'])->name('estimate-approval.show');
Route::post('/estimates/approve/{token}', [EstimateApprovalController::class, 'store'])->name('estimate-approval.store');

// ── Authenticated routes ──────────────────────────────────────
Route::middleware(['auth', 'active-user'])->group(function () {
    Route::get('/access-denied', fn () => view('errors.access-denied'))->name('access.denied');
});

Route::middleware(['auth', 'active-user', 'page-access'])->group(function () {

    // Dashboard (replaces old welcome page)
    Route::get('/dashboard', function () {
        $open      = ServiceRequest::where('status', 'new')->count();
        $today     = ServiceRequest::whereDate('created_at', today())->count();
        $customers = Customer::where('is_active', true)->count();
        $recent    = ServiceRequest::with('customer', 'catalogItem')
                        ->latest()
                        ->take(5)
                        ->get();

        $apiHealth = ApiMonitorEndpoint::query()
            ->where('is_active', true)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when last_status = 'healthy' then 1 else 0 end) as healthy")
            ->selectRaw("sum(case when last_status = 'degraded' then 1 else 0 end) as degraded")
            ->selectRaw("sum(case when last_status = 'down' then 1 else 0 end) as down")
            ->first();

        // Compliance widget (only when feature enabled)
        $complianceEnabled = (bool) \App\Models\Setting::getValue('compliance_tracking_enabled');
        $compliance = null;
        if ($complianceEnabled) {
            $compliance = (object) [
                'expired'  => \App\Models\TechnicianProfile::expired()->count(),
                'expiring' => \App\Models\TechnicianProfile::expiring()->count(),
                'total'    => \App\Models\TechnicianProfile::count(),
            ];
        }

        return view('welcome', compact('open', 'today', 'customers', 'recent', 'apiHealth', 'complianceEnabled', 'compliance'));
    })->name('dashboard');

    // Reports
    Route::get('/reports', [ReportsController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/reports/financial', [ReportsController::class, 'financial'])->name('reports.financial');

    // Access Administration
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');

    Route::get('/admin/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::post('/admin/roles', [AdminRoleController::class, 'store'])->name('admin.roles.store');
    Route::put('/admin/roles/{role}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::delete('/admin/roles/{role}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');

    Route::get('/admin/pages', [AdminPageController::class, 'index'])->name('admin.pages.index');
    Route::post('/admin/pages', [AdminPageController::class, 'store'])->name('admin.pages.store');
    Route::put('/admin/pages/{page}', [AdminPageController::class, 'update'])->name('admin.pages.update');
    Route::delete('/admin/pages/{page}', [AdminPageController::class, 'destroy'])->name('admin.pages.destroy');
    Route::post('/admin/pages/sync', [AdminPageController::class, 'sync'])->name('admin.pages.sync');
    Route::get('/admin/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');

    Route::get('/admin/access/roles/{role}', [RoleAccessController::class, 'edit'])->name('admin.access.edit');
    Route::put('/admin/access/roles/{role}', [RoleAccessController::class, 'update'])->name('admin.access.update');

    // Accounting
    Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('accounting.chart-of-accounts');
    Route::get('/accounting/import-accounts', [AccountingController::class, 'importAccounts'])->name('accounting.import-accounts');
    Route::get('/accounting/journal', [AccountingController::class, 'journal'])->name('accounting.journal');
    Route::get('/accounting/trial-balance', [AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', [AccountingController::class, 'profitAndLoss'])->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    Route::get('/accounting/general-ledger/{account}', [AccountingController::class, 'generalLedger'])->name('accounting.general-ledger');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Service Requests
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Service Types (removed — consolidated into Service Catalog)

    Route::get('/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');
    Route::post('/service-requests', [ServiceRequestController::class, 'store'])->name('service-requests.store');

    // Rapid Dispatch
    Route::get('/rapid-dispatch', [RapidDispatchController::class, 'create'])->name('rapid-dispatch.create');
    Route::post('/rapid-dispatch', [RapidDispatchController::class, 'store'])->name('rapid-dispatch.store');
    Route::get('/rapid-dispatch/parse', [RapidDispatchController::class, 'parse'])->name('rapid-dispatch.parse');
    Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::patch('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('service-requests.update');
    Route::patch('/service-requests/{serviceRequest}/assign-technician', [ServiceRequestController::class, 'assignTechnician'])->name('service-requests.assign-technician');
    Route::patch('/service-requests/{serviceRequest}/vehicle', [ServiceRequestController::class, 'syncVehicleRecord'])->name('service-requests.sync-vehicle');
    Route::post('/service-requests/{serviceRequest}/send-technician-location', [ServiceRequestController::class, 'sendLocationToTechnician'])->name('service-requests.send-technician-location');
    Route::post('/service-requests/{serviceRequest}/request-location', [LocationShareController::class, 'request'])->name('service-requests.request-location');
    Route::post('/service-requests/{serviceRequest}/messages', [MessageController::class, 'store'])->name('service-requests.messages.store');
    Route::post('/service-requests/{serviceRequest}/correspondence', [CorrespondenceController::class, 'store'])->name('service-requests.correspondence.store');

    // Message Templates
    Route::resource('message-templates', MessageTemplateController::class);
    Route::post('/message-templates/preview', [MessageTemplateController::class, 'preview'])->name('message-templates.preview');

    // Catalog
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/services/create', [CatalogController::class, 'createItem'])->name('catalog.items.create');
    Route::post('/catalog/services', [CatalogController::class, 'storeItem'])->name('catalog.items.store');
    Route::get('/catalog/services/{item}/edit', [CatalogController::class, 'editItem'])->name('catalog.items.edit');
    Route::put('/catalog/services/{item}', [CatalogController::class, 'updateItem'])->name('catalog.items.update');
    Route::delete('/catalog/services/{item}', [CatalogController::class, 'destroyItem'])->name('catalog.items.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // State Tax Rates (must be before /settings/{key} to avoid catch-all match)
    Route::get('/settings/tax-rates', [StateTaxRateController::class, 'index'])->name('settings.tax-rates');
    Route::put('/settings/tax-rates', [StateTaxRateController::class, 'update'])->name('settings.tax-rates.update');
    Route::put('/settings/approval-mode', [SettingsController::class, 'updateApprovalMode'])->name('settings.update-approval-mode');

    // API Monitoring settings
    Route::get('/settings/api-monitor', [ApiMonitorController::class, 'index'])->name('settings.api-monitor.index');
    Route::post('/settings/api-monitor', [ApiMonitorController::class, 'store'])->name('settings.api-monitor.store');
    Route::put('/settings/api-monitor/{endpoint}', [ApiMonitorController::class, 'update'])->name('settings.api-monitor.update');
    Route::post('/settings/api-monitor/{endpoint}/run', [ApiMonitorController::class, 'run'])->name('settings.api-monitor.run');

    Route::put('/settings/{key}', [SettingsController::class, 'updateSingle'])->name('settings.update-single');

    // Estimates
    Route::get('/service-requests/{serviceRequest}/estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
    Route::post('/service-requests/{serviceRequest}/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::get('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
    Route::get('/service-requests/{serviceRequest}/estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
    Route::put('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('/service-requests/{serviceRequest}/estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');
    Route::post('/service-requests/{serviceRequest}/estimates/{estimate}/revise', [EstimateController::class, 'revise'])->name('estimates.revise');
    Route::post('/service-requests/{serviceRequest}/estimates/{estimate}/request-approval', [EstimateController::class, 'requestApproval'])->name('estimates.request-approval');

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

    // Generic polymorphic document upload (service-request, customer, invoice, etc.)
    Route::post('/documents/{type}/{id}', [DocumentController::class, 'storeGeneric'])->name('documents.store-generic')->where('id', '[0-9]+');
    Route::get('/documents/{document}/detail', [DocumentController::class, 'detail'])->name('documents.detail');
    Route::post('/documents/{document}/reanalyze', [DocumentController::class, 'reanalyze'])->name('documents.reanalyze');
    Route::post('/documents/{document}/accept-category', [DocumentController::class, 'acceptCategory'])->name('documents.accept-category');

    // Document Line Items — per-item categorization & chart-of-accounts recording
    Route::post('/line-items/{lineItem}/accept', [\App\Http\Controllers\DocumentLineItemController::class, 'accept'])->name('line-items.accept');
    Route::post('/line-items/{lineItem}/reject', [\App\Http\Controllers\DocumentLineItemController::class, 'reject'])->name('line-items.reject');
    Route::post('/documents/{document}/line-items/bulk-accept', [\App\Http\Controllers\DocumentLineItemController::class, 'bulkAccept'])->name('line-items.bulk-accept');
    Route::post('/documents/{document}/line-items/bulk-reject', [\App\Http\Controllers\DocumentLineItemController::class, 'bulkReject'])->name('line-items.bulk-reject');

    // Document Inbox — bulk upload & AI-powered matching
    Route::get('/inbox', [DocumentInboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/upload', [DocumentInboxController::class, 'upload'])->name('inbox.upload');
    Route::post('/inbox/{document}/link', [DocumentInboxController::class, 'link'])->name('inbox.link');
    Route::post('/inbox/{document}/accept-match', [DocumentInboxController::class, 'acceptMatch'])->name('inbox.accept-match');
    Route::post('/inbox/bulk-accept', [DocumentInboxController::class, 'bulkAccept'])->name('inbox.bulk-accept');
    Route::post('/inbox/{document}/skip', [DocumentInboxController::class, 'skip'])->name('inbox.skip');
    Route::post('/inbox/{document}/rematch', [DocumentInboxController::class, 'rematch'])->name('inbox.rematch');
    Route::get('/inbox/search', [DocumentInboxController::class, 'search'])->name('inbox.search');

    // Transaction Imports — AI-parsed spreadsheet transactions
    Route::get('/transaction-imports', [TransactionImportController::class, 'index'])->name('transaction-imports.index');
    Route::get('/transaction-imports/{document}', [TransactionImportController::class, 'show'])->name('transaction-imports.show');
    Route::post('/transaction-imports/{import}/accept', [TransactionImportController::class, 'accept'])->name('transaction-imports.accept');
    Route::post('/transaction-imports/{import}/reject', [TransactionImportController::class, 'reject'])->name('transaction-imports.reject');
    Route::post('/transaction-imports/{document}/bulk-accept', [TransactionImportController::class, 'bulkAccept'])->name('transaction-imports.bulk-accept');
    Route::post('/transaction-imports/{document}/bulk-reject', [TransactionImportController::class, 'bulkReject'])->name('transaction-imports.bulk-reject');

    // Receipts (issued from an invoice)
    Route::get('/service-requests/{serviceRequest}/invoices/{invoice}/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('/service-requests/{serviceRequest}/invoices/{invoice}/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('/service-requests/{serviceRequest}/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/service-requests/{serviceRequest}/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');

    // Invoices (created from a completed work order)
    Route::get('/service-requests/{serviceRequest}/work-orders/{workOrder}/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/service-requests/{serviceRequest}/work-orders/{workOrder}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/service-requests/{serviceRequest}/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/service-requests/{serviceRequest}/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/service-requests/{serviceRequest}/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/service-requests/{serviceRequest}/invoices/{invoice}/revise', [InvoiceController::class, 'revise'])->name('invoices.revise');
    Route::patch('/service-requests/{serviceRequest}/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::get('/service-requests/{serviceRequest}/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Work Orders
    Route::get('/service-requests/{serviceRequest}/work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create');
    Route::post('/service-requests/{serviceRequest}/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/service-requests/{serviceRequest}/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::get('/service-requests/{serviceRequest}/work-orders/{workOrder}/edit', [WorkOrderController::class, 'edit'])->name('work-orders.edit');
    Route::put('/service-requests/{serviceRequest}/work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update');
    Route::patch('/service-requests/{serviceRequest}/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.update-status');
    Route::get('/service-requests/{serviceRequest}/work-orders/{workOrder}/pdf', [WorkOrderController::class, 'pdf'])->name('work-orders.pdf');
    Route::post('/service-requests/{serviceRequest}/work-orders/{workOrder}/change-orders', [ChangeOrderController::class, 'store'])->name('change-orders.store');
    Route::post('/service-requests/{serviceRequest}/work-orders/{workOrder}/change-orders/{changeOrder}/cancel', [ChangeOrderController::class, 'cancel'])->name('change-orders.cancel');

    // Technician Compliance Profiles (optional feature)
    Route::get('/technician-profiles', [TechnicianProfileController::class, 'index'])->name('technician-profiles.index');
    Route::get('/technician-profiles/{user}', [TechnicianProfileController::class, 'show'])->name('technician-profiles.show');
    Route::get('/technician-profiles/{user}/edit', [TechnicianProfileController::class, 'edit'])->name('technician-profiles.edit');
    Route::put('/technician-profiles/{user}', [TechnicianProfileController::class, 'update'])->name('technician-profiles.update');

    // Expenses (standalone — not nested under service requests)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

    // Vendors
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    Route::post('/vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])->name('vendors.toggle-active');

    // Vendor Documents (receipts & invoices from suppliers)
    Route::get('/vendor-documents', [VendorDocumentController::class, 'index'])->name('vendor-documents.index');
    Route::get('/vendor-documents/create', [VendorDocumentController::class, 'create'])->name('vendor-documents.create');
    Route::post('/vendor-documents', [VendorDocumentController::class, 'store'])->name('vendor-documents.store');
    Route::get('/vendor-documents/{vendorDocument}', [VendorDocumentController::class, 'show'])->name('vendor-documents.show');
    Route::get('/vendor-documents/{vendorDocument}/edit', [VendorDocumentController::class, 'edit'])->name('vendor-documents.edit');
    Route::put('/vendor-documents/{vendorDocument}', [VendorDocumentController::class, 'update'])->name('vendor-documents.update');
    Route::post('/vendor-documents/{vendorDocument}/post', [VendorDocumentController::class, 'post'])->name('vendor-documents.post');
    Route::post('/vendor-documents/{vendorDocument}/void', [VendorDocumentController::class, 'void'])->name('vendor-documents.void');
    Route::post('/vendor-documents/{vendorDocument}/pay', [VendorDocumentController::class, 'pay'])->name('vendor-documents.pay');
    Route::delete('/vendor-documents/{vendorDocument}/attachments/{attachment}', [VendorDocumentController::class, 'deleteAttachment'])->name('vendor-documents.delete-attachment');
    Route::get('/vendor-documents/{vendorDocument}/attachments/{attachment}/download', [VendorDocumentController::class, 'downloadAttachment'])->name('vendor-documents.download-attachment');

    // AJAX endpoints (same-origin, session-auth)
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    Route::get('/api/service-types', function () {
        return \App\Models\CatalogItem::whereHas('category', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'base_cost as default_price', 'is_active', 'sort_order']);
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
        $sr = \App\Models\ServiceRequest::with(['customer', 'catalogItem'])->findOrFail($request->input('service_request_id'));
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
