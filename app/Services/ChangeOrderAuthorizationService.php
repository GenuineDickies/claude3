<?php

namespace App\Services;

use App\Models\ChangeOrder;
use App\Models\ServiceRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;

class ChangeOrderAuthorizationService
{
    public function approvalThreshold(float $approvedEstimateTotal): float
    {
        return min(200.0, $approvedEstimateTotal * 0.10);
    }

    public function requiresApproval(float $approvedEstimateTotal, float $newTotal): bool
    {
        $delta = abs($newTotal - $approvedEstimateTotal);
        $threshold = $this->approvalThreshold($approvedEstimateTotal);

        return $delta > $threshold;
    }

    public function requiresApprovalForPriceImpact(WorkOrder $workOrder, float $priceImpact): bool
    {
        $estimateTotal = (float) ($workOrder->estimate?->total ?? 0);
        if ($estimateTotal <= 0) {
            return false;
        }

        $newTotal = (float) $workOrder->total + $priceImpact;

        return $this->requiresApproval($estimateTotal, $newTotal);
    }

    public function hasBlockingPendingApproval(ServiceRequest $serviceRequest): bool
    {
        return $this->hasPendingChangeOrders($serviceRequest);
    }

    public function hasPendingChangeOrders(ServiceRequest $serviceRequest): bool
    {
        return ChangeOrder::query()
            ->whereHas('workOrder', fn ($q) => $q->where('service_request_id', $serviceRequest->id))
            ->where('approval_status', ChangeOrder::APPROVAL_PENDING)
            ->exists();
    }

    public function applyApprovedChangeOrder(ChangeOrder $changeOrder): void
    {
        $impact = (float) $changeOrder->price_impact;
        if ($impact == 0.0) {
            return;
        }

        $workOrder = $changeOrder->workOrder;
        $hasItems = $workOrder->items()->exists();
        $nextSort = (int) ($workOrder->items()->max('sort_order') ?? -1) + 1;

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'catalog_item_id' => null,
            'name' => 'Change Order Adjustment',
            'description' => $changeOrder->description,
            'unit_price' => $impact,
            'quantity' => 1,
            'unit' => 'each',
            'sort_order' => $nextSort,
        ]);

        if ($hasItems) {
            $workOrder->recalculate();
            return;
        }

        // Fallback for legacy/manual work orders without item rows.
        $subtotal = (float) $workOrder->subtotal + $impact;
        $taxAmount = round($subtotal * ((float) $workOrder->tax_rate / 100), 2);

        $workOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }
}
