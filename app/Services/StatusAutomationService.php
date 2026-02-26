<?php

namespace App\Services;

use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusLog;

class StatusAutomationService
{
    /**
     * Automation rules: event => target status.
     *
     * When an evidence event occurs, the service request will be
     * auto-advanced to the target status if the transition is valid.
     */
    private const RULES = [
        'photo_uploaded'    => 'on_scene',
        'signature_captured' => 'completed',
        'payment_collected' => 'completed',
    ];

    /**
     * Attempt to auto-advance the service request status based on an evidence event.
     *
     * Returns the new status if a transition was made, or null if no change.
     */
    public function handle(ServiceRequest $serviceRequest, string $event): ?string
    {
        $targetStatus = self::RULES[$event] ?? null;

        if (! $targetStatus) {
            return null;
        }

        // Only advance if the current status is before the target
        if (! $this->shouldAdvance($serviceRequest, $targetStatus)) {
            return null;
        }

        $oldStatus = $serviceRequest->status;

        $serviceRequest->update(['status' => $targetStatus]);

        ServiceRequestStatusLog::create([
            'service_request_id' => $serviceRequest->id,
            'old_status'         => $oldStatus,
            'new_status'         => $targetStatus,
            'changed_by'         => null,
            'notes'              => "Auto-advanced by {$event}",
        ]);

        ServiceLog::log($serviceRequest, 'status_change', [
            'old_status' => $oldStatus,
            'new_status' => $targetStatus,
            'automated'  => true,
            'trigger'    => $event,
        ]);

        return $targetStatus;
    }

    /**
     * Determine if the service request should advance to the target status.
     *
     * Only advances forward (never backwards), and only for non-terminal statuses.
     */
    private function shouldAdvance(ServiceRequest $serviceRequest, string $targetStatus): bool
    {
        $statuses = ServiceRequest::STATUSES;
        $currentIndex = array_search($serviceRequest->status, $statuses, true);
        $targetIndex = array_search($targetStatus, $statuses, true);

        if ($currentIndex === false || $targetIndex === false) {
            return false;
        }

        // Don't advance if already at or past the target
        if ($currentIndex >= $targetIndex) {
            return false;
        }

        // Don't advance from terminal statuses
        if (in_array($serviceRequest->status, ServiceRequest::TERMINAL_STATUSES, true)) {
            return false;
        }

        return true;
    }
}
