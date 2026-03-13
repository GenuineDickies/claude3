<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $event
 * @property array<array-key, mixed>|null $details
 * @property int|null $logged_by
 * @property \Illuminate\Support\Carbon $logged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereLoggedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereLoggedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'technician_location_sent',
        'work_order_created',
        'work_order_updated',
        'work_order_completed',
        'work_order_cancelled',
        'estimate_revised',
        'estimate_approval_requested',
        'estimate_approved',
        'estimate_declined',
        'invoice_revised',
        'warranty_added',
        'warranty_updated',
        'warranty_deleted',
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
        'technician_location_sent' => 'Technician Location Sent',
        'work_order_created'    => 'Work Order Created',
        'work_order_updated'    => 'Work Order Updated',
        'work_order_completed'  => 'Work Order Completed',
        'work_order_cancelled'  => 'Work Order Cancelled',
        'estimate_revised'      => 'Estimate Revised',
        'estimate_approval_requested' => 'Estimate Approval Requested',
        'estimate_approved'    => 'Estimate Approved',
        'estimate_declined'    => 'Estimate Declined',
        'invoice_revised'      => 'Invoice Revised',
        'warranty_added'       => 'Warranty Added',
        'warranty_updated'     => 'Warranty Updated',
        'warranty_deleted'     => 'Warranty Deleted',
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
