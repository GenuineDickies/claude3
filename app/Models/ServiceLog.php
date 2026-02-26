<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    protected $fillable = [
        'service_request_id',
        'event',
        'details',
        'logged_by',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'details'   => 'array',
            'logged_at' => 'datetime',
        ];
    }

    /** Well-known event types. */
    public const EVENTS = [
        'status_change',
        'note_added',
        'photo_uploaded',
        'photo_deleted',
        'signature_requested',
        'signature_captured',
        'payment_collected',
        'payment_deleted',
        'location_shared',
        'sms_sent',
        'work_order_created',
        'work_order_updated',
        'work_order_completed',
        'work_order_cancelled',
    ];

    /** Human-readable labels. */
    public const EVENT_LABELS = [
        'status_change'         => 'Status Changed',
        'note_added'            => 'Note Added',
        'photo_uploaded'        => 'Photo Uploaded',
        'photo_deleted'         => 'Photo Deleted',
        'signature_requested'   => 'Signature Requested',
        'signature_captured'    => 'Signature Captured',
        'payment_collected'     => 'Payment Collected',
        'payment_deleted'       => 'Payment Deleted',
        'location_shared'       => 'Location Shared',
        'sms_sent'              => 'SMS Sent',
        'work_order_created'    => 'Work Order Created',
        'work_order_updated'    => 'Work Order Updated',
        'work_order_completed'  => 'Work Order Completed',
        'work_order_cancelled'  => 'Work Order Cancelled',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function eventLabel(): string
    {
        return self::EVENT_LABELS[$this->event] ?? ucwords(str_replace('_', ' ', $this->event));
    }

    /**
     * Create a log entry for a service request.
     */
    public static function log(
        ServiceRequest $serviceRequest,
        string $event,
        ?array $details = null,
        ?int $userId = null,
    ): self {
        return self::create([
            'service_request_id' => $serviceRequest->id,
            'event'              => $event,
            'details'            => $details,
            'logged_by'          => $userId,
            'logged_at'          => now(),
        ]);
    }
}
