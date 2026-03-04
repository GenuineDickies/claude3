<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\Estimate;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    /**
     * GET /service-requests/{serviceRequest}/work-orders/create
     */
    public function create(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['customer', 'catalogItem', 'estimates.items']);

        $categories = CatalogCategory::active()
            ->with(['items' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        // Pre-load items from the latest accepted estimate if available
        $estimate = $serviceRequest->estimates
            ->where('status', 'accepted')
            ->sortByDesc('created_at')
            ->first();

        $estimateItems = $estimate
            ? $estimate->items->map(fn ($i) => [
                'catalog_item_id' => $i->catalog_item_id,
                'name'            => $i->name,
                'description'     => $i->description ?? '',
                'quantity'        => (float) $i->quantity,
                'unit'            => $i->unit ?? 'each',
                'unit_price'      => (float) $i->unit_price,
            ])->values()->all()
            : [];

        $stateCode = null;
        $taxRate = 0;
        $approvalRequired = false;
        if ($estimate) {
            $stateCode = $estimate->state_code;
            $approvalRequired = $estimate->requiresApproval() && ! $estimate->isApproved();
            $taxRate = (float) $estimate->tax_rate;
        }

        return view('work-orders.create', [
            'serviceRequest'   => $serviceRequest,
            'estimate'         => $estimate,
            'estimateItems'    => $estimateItems,
            'categories'       => $categories,
            'stateCode'        => $stateCode,
            'taxRate'          => $taxRate,
            'approvalRequired' => $approvalRequired,
        ]);
    }

    /**
     * POST /service-requests/{serviceRequest}/work-orders
     */
    public function store(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'estimate_id'      => 'nullable|integer|exists:estimates,id',
            'priority'         => 'required|string|in:' . implode(',', WorkOrder::PRIORITIES),
            'description'      => 'nullable|string|max:2000',
            'notes'            => 'nullable|string|max:2000',
            'assigned_to'      => 'nullable|string|max:200',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'items'            => 'required|array|min:1',
            'items.*.catalog_item_id' => 'nullable|integer|exists:catalog_items,id',
            'items.*.name'     => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit'     => 'required|string|in:each,mile,hour,gallon',
        ]);

        $workOrder = DB::transaction(function () use ($validated, $serviceRequest) {
            // Gate: if estimate requires approval, it must be approved before WO creation
            if (! empty($validated['estimate_id'])) {
                $estimate = Estimate::find($validated['estimate_id']);
                if ($estimate && $estimate->requiresApproval() && ! $estimate->isApproved()) {
                    abort(403, 'This estimate requires customer approval before a work order can be created.');
                }
            }

            $workOrder = WorkOrder::create([
                'service_request_id' => $serviceRequest->id,
                'estimate_id'        => $validated['estimate_id'] ?? null,
                'work_order_number'  => WorkOrder::generateWorkOrderNumber(),
                'status'             => WorkOrder::STATUS_PENDING,
                'priority'           => $validated['priority'],
                'description'        => $validated['description'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'assigned_to'        => $validated['assigned_to'] ?? null,
                'tax_rate'           => $validated['tax_rate'],
                'created_by'         => Auth::id(),
            ]);

            foreach ($validated['items'] as $index => $item) {
                WorkOrderItem::create([
                    'work_order_id'   => $workOrder->id,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'name'            => $item['name'],
                    'description'     => $item['description'] ?? null,
                    'unit_price'      => $item['unit_price'],
                    'quantity'        => $item['quantity'],
                    'unit'            => $item['unit'],
                    'sort_order'      => $index,
                ]);
            }

            $workOrder->recalculate();

            ServiceLog::log($serviceRequest, 'work_order_created', [
                'work_order_id'     => $workOrder->id,
                'work_order_number' => $workOrder->work_order_number,
            ], Auth::id());

            return $workOrder;
        });

        return redirect()
            ->route('work-orders.show', [$serviceRequest, $workOrder])
            ->with('success', 'Work Order ' . $workOrder->work_order_number . ' created.');
    }

    /**
     * GET /service-requests/{serviceRequest}/work-orders/{workOrder}
     */
    public function show(ServiceRequest $serviceRequest, WorkOrder $workOrder)
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        $workOrder->load(['items', 'changeOrders']);
        $serviceRequest->load(['customer', 'catalogItem']);

        return view('work-orders.show', [
            'serviceRequest' => $serviceRequest,
            'workOrder'      => $workOrder->load('documents.uploader'),
        ]);
    }

    /**
     * GET /service-requests/{serviceRequest}/work-orders/{workOrder}/edit
     */
    public function edit(ServiceRequest $serviceRequest, WorkOrder $workOrder)
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        if (in_array($workOrder->status, [WorkOrder::STATUS_COMPLETED, WorkOrder::STATUS_CANCELLED])) {
            return redirect()
                ->route('work-orders.show', [$serviceRequest, $workOrder])
                ->with('error', 'Completed or cancelled work orders cannot be edited.');
        }

        $workOrder->load('items');
        $serviceRequest->load(['customer', 'catalogItem']);

        $categories = CatalogCategory::active()
            ->with(['items' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('work-orders.edit', [
            'serviceRequest' => $serviceRequest,
            'workOrder'      => $workOrder,
            'categories'     => $categories,
        ]);
    }

    /**
     * PUT /service-requests/{serviceRequest}/work-orders/{workOrder}
     */
    public function update(Request $request, ServiceRequest $serviceRequest, WorkOrder $workOrder): RedirectResponse
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        if (in_array($workOrder->status, [WorkOrder::STATUS_COMPLETED, WorkOrder::STATUS_CANCELLED])) {
            return redirect()
                ->route('work-orders.show', [$serviceRequest, $workOrder])
                ->with('error', 'Completed or cancelled work orders cannot be edited.');
        }

        $validated = $request->validate([
            'priority'         => 'required|string|in:' . implode(',', WorkOrder::PRIORITIES),
            'description'      => 'nullable|string|max:2000',
            'notes'            => 'nullable|string|max:2000',
            'technician_notes' => 'nullable|string|max:2000',
            'assigned_to'      => 'nullable|string|max:200',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'items'            => 'required|array|min:1',
            'items.*.catalog_item_id' => 'nullable|integer|exists:catalog_items,id',
            'items.*.name'     => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit'     => 'required|string|in:each,mile,hour,gallon',
        ]);

        DB::transaction(function () use ($validated, $workOrder, $serviceRequest) {
            $workOrder->update([
                'priority'         => $validated['priority'],
                'description'      => $validated['description'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'technician_notes' => $validated['technician_notes'] ?? null,
                'assigned_to'      => $validated['assigned_to'] ?? null,
                'tax_rate'         => $validated['tax_rate'],
            ]);

            $workOrder->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                WorkOrderItem::create([
                    'work_order_id'   => $workOrder->id,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'name'            => $item['name'],
                    'description'     => $item['description'] ?? null,
                    'unit_price'      => $item['unit_price'],
                    'quantity'        => $item['quantity'],
                    'unit'            => $item['unit'],
                    'sort_order'      => $index,
                ]);
            }

            $workOrder->recalculate();

            ServiceLog::log($serviceRequest, 'work_order_updated', [
                'work_order_id'     => $workOrder->id,
                'work_order_number' => $workOrder->work_order_number,
            ], Auth::id());
        });

        return redirect()
            ->route('work-orders.show', [$serviceRequest, $workOrder])
            ->with('success', 'Work Order updated.');
    }

    /**
     * PATCH /service-requests/{serviceRequest}/work-orders/{workOrder}/status
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest, WorkOrder $workOrder): RedirectResponse
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', WorkOrder::STATUSES),
        ]);

        $oldStatus = $workOrder->status;
        $newStatus = $validated['status'];

        $updates = ['status' => $newStatus];

        if ($newStatus === WorkOrder::STATUS_IN_PROGRESS && ! $workOrder->started_at) {
            $updates['started_at'] = now();
        }

        if ($newStatus === WorkOrder::STATUS_COMPLETED) {
            $updates['completed_at'] = now();
        }

        $workOrder->update($updates);

        $event = match ($newStatus) {
            WorkOrder::STATUS_COMPLETED => 'work_order_completed',
            WorkOrder::STATUS_CANCELLED => 'work_order_cancelled',
            default => 'work_order_updated',
        };

        ServiceLog::log($serviceRequest, $event, [
            'work_order_id'     => $workOrder->id,
            'work_order_number' => $workOrder->work_order_number,
            'old_status'        => $oldStatus,
            'new_status'        => $newStatus,
        ], Auth::id());

        return redirect()
            ->route('work-orders.show', [$serviceRequest, $workOrder])
            ->with('success', 'Work order status updated to ' . WorkOrder::STATUS_LABELS[$newStatus] . '.');
    }

    /**
     * GET /service-requests/{serviceRequest}/work-orders/{workOrder}/pdf
     */
    public function pdf(ServiceRequest $serviceRequest, WorkOrder $workOrder)
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        $workOrder->load('items');
        $serviceRequest->load(['customer', 'catalogItem']);

        $company = [
            'name'    => Setting::getValue('company_name', config('app.name')),
            'address' => Setting::getValue('company_address', ''),
            'phone'   => Setting::getValue('company_phone', ''),
            'email'   => Setting::getValue('company_email', ''),
        ];

        $pdf = Pdf::loadView('work-orders.pdf', [
            'workOrder'      => $workOrder,
            'serviceRequest' => $serviceRequest,
            'company'        => $company,
        ]);

        $pdf->setPaper('letter');

        return $pdf->download($workOrder->work_order_number . '.pdf');
    }
}
