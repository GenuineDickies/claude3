<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Services\ChangeOrderAuthorizationService;
use App\Services\SmsServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChangeOrderController extends Controller
{
    public function store(
        Request $request,
        ServiceRequest $serviceRequest,
        WorkOrder $workOrder,
        ChangeOrderAuthorizationService $authorizationService
    ): RedirectResponse {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        $validated = $request->validate([
            'change_type' => 'required|string|in:add_item,remove_item,modify_item,informational',
            'description' => 'required|string|max:2000',
            'price_impact' => 'required|numeric',
            'technician_notes' => 'nullable|string|max:2000',
            'send_sms' => 'nullable|boolean',
        ]);

        $priceImpact = (float) $validated['price_impact'];
        $requiresApproval = $authorizationService->requiresApprovalForPriceImpact($workOrder, $priceImpact);

        // Supersede any existing pending change orders on this work order
        $workOrder->changeOrders()
            ->where('approval_status', ChangeOrder::APPROVAL_PENDING)
            ->update(['approval_status' => ChangeOrder::APPROVAL_CANCELLED]);

        $changeOrder = ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => $validated['change_type'],
            'description' => $validated['description'],
            'price_impact' => $priceImpact,
            'requires_customer_approval' => $requiresApproval,
            'approval_status' => $requiresApproval ? ChangeOrder::APPROVAL_PENDING : ChangeOrder::APPROVAL_NOT_REQUIRED,
            'approval_token' => $requiresApproval ? Str::random(48) : null,
            'approval_token_expires_at' => $requiresApproval ? now()->addDays(7) : null,
            'technician_notes' => $validated['technician_notes'] ?? null,
        ]);

        ServiceLog::log($serviceRequest, 'change_order_created', [
            'change_order_id' => $changeOrder->id,
            'requires_customer_approval' => $requiresApproval,
            'price_impact' => $priceImpact,
        ], Auth::id());

        if (! $requiresApproval) {
            $authorizationService->applyApprovedChangeOrder($changeOrder);

            ServiceLog::log($serviceRequest, 'change_order_applied', [
                'change_order_id' => $changeOrder->id,
                'price_impact' => $priceImpact,
            ], Auth::id());
        }

        if ($requiresApproval && $request->boolean('send_sms') && $serviceRequest->customer?->hasSmsConsent()) {
            $companyName = Setting::getValue('company_name', config('app.name'));
            $link = route('change-orders.show', $changeOrder->approval_token);

            app(SmsServiceInterface::class)->sendRaw(
                $serviceRequest->customer->phone,
                $companyName . ': Change authorization requested for your service. Review and approve: ' . $link . ' Reply STOP to opt out.'
            );
        }

        return back()->with('success', 'Change order created.');
    }

    public function show(string $token)
    {
        $changeOrder = ChangeOrder::query()
            ->where('approval_token', $token)
            ->with(['workOrder.serviceRequest.customer'])
            ->firstOrFail();

        if (! $changeOrder->isApprovalOpen()) {
            return response()->view('change-orders.expired', [
                'changeOrder' => $changeOrder,
            ], 410);
        }

        return view('change-orders.approve', [
            'changeOrder' => $changeOrder,
            'serviceRequest' => $changeOrder->workOrder->serviceRequest,
            'companyName' => Setting::getValue('company_name', config('app.name')),
        ]);
    }

    public function approve(Request $request, string $token)
    {
        $changeOrder = ChangeOrder::query()
            ->where('approval_token', $token)
            ->with('workOrder.serviceRequest')
            ->firstOrFail();

        if (! $changeOrder->isApprovalOpen()) {
            abort(410, 'Approval link is no longer valid.');
        }

        $validated = $request->validate([
            'decision' => 'required|string|in:approved,rejected',
            'approved_by_name' => 'required|string|max:200',
            'signature_data' => 'nullable|string',
        ]);

        $changeOrder->update([
            'approval_status' => $validated['decision'],
            'approved_by_name' => $validated['approved_by_name'],
            'approved_at' => now(),
            'approval_method' => 'sms',
            'signature_data' => $validated['signature_data'] ?? null,
            'approval_device_info' => [
                'ip' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'decision' => $validated['decision'],
            ],
        ]);

        ServiceLog::log($changeOrder->workOrder->serviceRequest, 'change_order_' . $validated['decision'], [
            'change_order_id' => $changeOrder->id,
            'approved_by_name' => $changeOrder->approved_by_name,
        ]);

        if ($validated['decision'] === ChangeOrder::APPROVAL_APPROVED) {
            app(ChangeOrderAuthorizationService::class)->applyApprovedChangeOrder($changeOrder);

            ServiceLog::log($changeOrder->workOrder->serviceRequest, 'change_order_applied', [
                'change_order_id' => $changeOrder->id,
                'price_impact' => $changeOrder->price_impact,
            ]);
        }

        return view('change-orders.thank-you', [
            'decision' => $validated['decision'],
            'companyName' => Setting::getValue('company_name', config('app.name')),
        ]);
    }

    public function cancel(ServiceRequest $serviceRequest, WorkOrder $workOrder, ChangeOrder $changeOrder): RedirectResponse
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);
        abort_if($changeOrder->work_order_id !== $workOrder->id, 404);

        if ($changeOrder->approval_status !== ChangeOrder::APPROVAL_PENDING) {
            return back()->with('error', 'Only pending change orders can be cancelled.');
        }

        $changeOrder->update([
            'approval_status' => ChangeOrder::APPROVAL_CANCELLED,
            'approved_at' => now(),
            'approval_method' => 'internal',
            'approved_by_name' => Auth::user()?->name,
        ]);

        ServiceLog::log($serviceRequest, 'change_order_cancelled', [
            'change_order_id' => $changeOrder->id,
        ], Auth::id());

        return back()->with('success', 'Change order cancelled.');
    }
}
