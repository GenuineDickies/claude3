<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $email
 * @property string $stage
 * @property string $source
 * @property string|null $service_needed
 * @property string|null $location
 * @property string|null $notes
 * @property numeric|null $estimated_value
 * @property int|null $assigned_user_id
 * @property \Illuminate\Support\Carbon|null $converted_at
 * @property int|null $converted_customer_id
 * @property int|null $converted_service_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Lead extends Model
{
    public const STAGE_NEW = 'new';
    public const STAGE_INTAKE_VERIFIED = 'intake_verified';
    public const STAGE_DISPATCH_READY = 'dispatch_ready';
    public const STAGE_WAITING_CUSTOMER = 'waiting_customer';
    public const STAGE_CONVERTED = 'converted';
    public const STAGE_CLOSED_NO_SERVICE = 'closed_no_service';

    public const STAGES = [
        self::STAGE_NEW,
        self::STAGE_INTAKE_VERIFIED,
        self::STAGE_DISPATCH_READY,
        self::STAGE_WAITING_CUSTOMER,
        self::STAGE_CONVERTED,
        self::STAGE_CLOSED_NO_SERVICE,
    ];

    /**
     * Legacy stages kept for backward compatibility with any pre-existing rows.
     */
    public const LEGACY_STAGE_MAP = [
        'contacted' => self::STAGE_INTAKE_VERIFIED,
        'qualified' => self::STAGE_DISPATCH_READY,
        'proposal_sent' => self::STAGE_WAITING_CUSTOMER,
        'won' => self::STAGE_CONVERTED,
        'lost' => self::STAGE_CLOSED_NO_SERVICE,
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'stage',
        'source',
        'service_needed',
        'location',
        'notes',
        'estimated_value',
        'assigned_user_id',
        'converted_at',
        'converted_customer_id',
        'converted_service_request_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public static function stageOptions(): array
    {
        return [
            self::STAGE_NEW => 'New Inbound',
            self::STAGE_INTAKE_VERIFIED => 'Intake Verified',
            self::STAGE_DISPATCH_READY => 'Dispatch Ready',
            self::STAGE_WAITING_CUSTOMER => 'Waiting on Customer Info',
            self::STAGE_CONVERTED => 'Converted to Ticket',
            self::STAGE_CLOSED_NO_SERVICE => 'Closed - No Service',
        ];
    }

    public static function normalizeStage(string $stage): string
    {
        return self::LEGACY_STAGE_MAP[$stage] ?? $stage;
    }

    public function stageLabel(): string
    {
        return self::stageOptions()[$this->stage] ?? ucfirst(str_replace('_', ' ', $this->stage));
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function convertedServiceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'converted_service_request_id');
    }
}
