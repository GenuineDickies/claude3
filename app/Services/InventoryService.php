<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Reserve stock when a work order item is added.
     * Returns true if qty_available went negative (warning needed).
     */
    public function reserve(CatalogItem $item, float $qty): bool
    {
        DB::table('catalog_items')
            ->where('id', $item->id)
            ->increment('qty_reserved', $qty);

        $item->refresh();

        return $item->qty_available < 0;
    }

    /**
     * Release a reservation (WO item removed or WO cancelled/deleted).
     * Clamps at zero — never goes negative.
     */
    public function release(CatalogItem $item, float $qty): void
    {
        DB::table('catalog_items')
            ->where('id', $item->id)
            ->update([
                'qty_reserved' => DB::raw("GREATEST(0, qty_reserved - {$qty})"),
            ]);
    }

    /**
     * Fulfill an item on invoice creation or on final invoice save.
     * Decrements on_hand and releases the corresponding reservation.
     */
    public function fulfill(CatalogItem $item, float $qty): void
    {
        DB::table('catalog_items')
            ->where('id', $item->id)
            ->update([
                'qty_on_hand'  => DB::raw("qty_on_hand - {$qty}"),
                'qty_reserved' => DB::raw("GREATEST(0, qty_reserved - {$qty})"),
            ]);
    }

    /**
     * Restore stock when an invoice is cancelled.
     * Only increments on_hand — reservation was already released at fulfillment.
     */
    public function restore(CatalogItem $item, float $qty): void
    {
        DB::table('catalog_items')
            ->where('id', $item->id)
            ->increment('qty_on_hand', $qty);
    }

    /**
     * Release all tracked reservations held by a work order.
     * Call when a WO is cancelled or before re-syncing items on update.
     */
    public function releaseWorkOrder(WorkOrder $workOrder): void
    {
        $workOrder->loadMissing('items.catalogItem');

        foreach ($workOrder->items as $woItem) {
            $catalogItem = $woItem->catalogItem;
            if ($catalogItem && $catalogItem->track_inventory && $woItem->quantity > 0) {
                $this->release($catalogItem, (float) $woItem->quantity);
            }
        }
    }

    /**
     * Release all current WO reservations then re-reserve from the new item list.
     * Returns names of catalog items whose qty_available went negative (for flash warnings).
     *
     * @param  WorkOrder  $workOrder  WO with existing items already loaded
     * @param  array      $newItems   Validated items array from the request
     */
    public function syncWorkOrderItems(WorkOrder $workOrder, array $newItems): array
    {
        $this->releaseWorkOrder($workOrder);

        $warnings = [];

        foreach ($newItems as $item) {
            if (empty($item['catalog_item_id'])) {
                continue;
            }

            $catalogItem = CatalogItem::find($item['catalog_item_id']);

            if (! $catalogItem || ! $catalogItem->track_inventory) {
                continue;
            }

            $wentNegative = $this->reserve($catalogItem, (float) $item['quantity']);

            if ($wentNegative) {
                $warnings[] = $catalogItem->name;
            }
        }

        return $warnings;
    }

    /**
     * Fulfill tracked items from an invoice's line_items JSON array.
     * Returns names of items fulfilled (for logging).
     */
    public function fulfillInvoiceLineItems(array $lineItems): void
    {
        foreach ($lineItems as $line) {
            if (empty($line['catalog_item_id'])) {
                continue;
            }

            $catalogItem = CatalogItem::find($line['catalog_item_id']);

            if (! $catalogItem || ! $catalogItem->track_inventory) {
                continue;
            }

            $this->fulfill($catalogItem, (float) $line['quantity']);
        }
    }

    /**
     * Restore tracked items from an invoice's line_items JSON array (on cancellation).
     */
    public function restoreInvoiceLineItems(array $lineItems): void
    {
        foreach ($lineItems as $line) {
            if (empty($line['catalog_item_id'])) {
                continue;
            }

            $catalogItem = CatalogItem::find($line['catalog_item_id']);

            if (! $catalogItem || ! $catalogItem->track_inventory) {
                continue;
            }

            $this->restore($catalogItem, (float) $line['quantity']);
        }
    }
}
