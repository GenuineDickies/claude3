<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $drivers_license_number
 * @property Carbon|null $drivers_license_expiry
 * @property string|null $insurance_policy_number
 * @property Carbon|null $insurance_expiry
 * @property Carbon|null $background_check_date
 * @property string|null $background_check_status
 * @property Carbon|null $drug_screen_date
 * @property string|null $drug_screen_status
 * @property array<array-key, mixed>|null $certifications
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $vehicle_year
 * @property string|null $vehicle_make
 * @property string|null $vehicle_model
 * @property string|null $vehicle_plate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static Builder<static>|TechnicianProfile compliant()
 * @method static Builder<static>|TechnicianProfile expired()
 * @method static Builder<static>|TechnicianProfile expiring()
 * @method static Builder<static>|TechnicianProfile newModelQuery()
 * @method static Builder<static>|TechnicianProfile newQuery()
 * @method static Builder<static>|TechnicianProfile query()
 * @method static Builder<static>|TechnicianProfile whereBackgroundCheckDate($value)
 * @method static Builder<static>|TechnicianProfile whereBackgroundCheckStatus($value)
 * @method static Builder<static>|TechnicianProfile whereCertifications($value)
 * @method static Builder<static>|TechnicianProfile whereCreatedAt($value)
 * @method static Builder<static>|TechnicianProfile whereDriversLicenseExpiry($value)
 * @method static Builder<static>|TechnicianProfile whereDriversLicenseNumber($value)
 * @method static Builder<static>|TechnicianProfile whereDrugScreenDate($value)
 * @method static Builder<static>|TechnicianProfile whereDrugScreenStatus($value)
 * @method static Builder<static>|TechnicianProfile whereEmergencyContactName($value)
 * @method static Builder<static>|TechnicianProfile whereEmergencyContactPhone($value)
 * @method static Builder<static>|TechnicianProfile whereId($value)
 * @method static Builder<static>|TechnicianProfile whereInsuranceExpiry($value)
 * @method static Builder<static>|TechnicianProfile whereInsurancePolicyNumber($value)
 * @method static Builder<static>|TechnicianProfile whereUpdatedAt($value)
 * @method static Builder<static>|TechnicianProfile whereUserId($value)
 * @method static Builder<static>|TechnicianProfile whereVehicleMake($value)
 * @method static Builder<static>|TechnicianProfile whereVehicleModel($value)
 * @method static Builder<static>|TechnicianProfile whereVehiclePlate($value)
 * @method static Builder<static>|TechnicianProfile whereVehicleYear($value)
 * @mixin \Eloquent
 */
class TechnicianProfile extends Model
{
    protected $fillable = [
        'user_id',
        'drivers_license_number',
        'drivers_license_expiry',
        'insurance_policy_number',
        'insurance_expiry',
        'background_check_date',
        'background_check_status',
        'drug_screen_date',
        'drug_screen_status',
        'certifications',
        'emergency_contact_name',
        'emergency_contact_phone',
        'vehicle_year',
        'vehicle_make',
        'vehicle_model',
        'vehicle_plate',
    ];

    protected function casts(): array
    {
        return [
            'drivers_license_expiry' => 'date',
            'insurance_expiry'       => 'date',
            'background_check_date'  => 'date',
            'drug_screen_date'       => 'date',
            'certifications'         => 'array',
        ];
    }

    // ── Relationships ───────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Compliance helpers ──────────────────────────────

    private const EXPIRY_SOON_DAYS = 30;

    /**
     * Determine the compliance badge for a date field: expired | expiring | valid | null.
     */
    public static function dateStatus(?Carbon $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date->isPast()) {
            return 'expired';
        }

        if ($date->lte(now()->addDays(self::EXPIRY_SOON_DAYS))) {
            return 'expiring';
        }

        return 'valid';
    }

    public function licenseStatus(): ?string
    {
        return self::dateStatus($this->drivers_license_expiry);
    }

    public function insuranceStatus(): ?string
    {
        return self::dateStatus($this->insurance_expiry);
    }

    /**
     * Overall compliance: true only when nothing is expired or expiring.
     */
    public function isFullyCompliant(): bool
    {
        $statuses = [
            $this->licenseStatus(),
            $this->insuranceStatus(),
            $this->background_check_status,
            $this->drug_screen_status,
        ];

        foreach ($statuses as $s) {
            if ($s === 'expired' || $s === 'failed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Count of issues (expired dates, failed checks, expiring soon).
     */
    public function issueCount(): int
    {
        $count = 0;

        foreach ([$this->licenseStatus(), $this->insuranceStatus()] as $s) {
            if ($s === 'expired' || $s === 'expiring') {
                $count++;
            }
        }

        foreach ([$this->background_check_status, $this->drug_screen_status] as $s) {
            if ($s === 'failed' || $s === 'pending') {
                $count++;
            }
        }

        // Check certification expiry dates
        foreach ($this->certifications ?? [] as $cert) {
            $expiry = isset($cert['expiry_date']) ? Carbon::parse($cert['expiry_date']) : null;
            $status = self::dateStatus($expiry);
            if ($status === 'expired' || $status === 'expiring') {
                $count++;
            }
        }

        return $count;
    }

    // ── Scopes ──────────────────────────────────────────

    /**
     * Profiles with any expired date field.
     */
    public function scopeExpired(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function (Builder $q) use ($today) {
            $q->where('drivers_license_expiry', '<', $today)
              ->orWhere('insurance_expiry', '<', $today)
              ->orWhere('background_check_status', 'failed')
              ->orWhere('drug_screen_status', 'failed');
        });
    }

    /**
     * Profiles with dates expiring within 30 days (but not yet expired).
     */
    public function scopeExpiring(Builder $query): Builder
    {
        $today = now()->toDateString();
        $soon  = now()->addDays(self::EXPIRY_SOON_DAYS)->toDateString();

        return $query->where(function (Builder $q) use ($today, $soon) {
            $q->where(function ($q2) use ($today, $soon) {
                $q2->where('drivers_license_expiry', '>=', $today)
                   ->where('drivers_license_expiry', '<=', $soon);
            })->orWhere(function ($q2) use ($today, $soon) {
                $q2->where('insurance_expiry', '>=', $today)
                   ->where('insurance_expiry', '<=', $soon);
            });
        });
    }

    /**
     * Fully compliant profiles — nothing expired or expiring.
     */
    public function scopeCompliant(Builder $query): Builder
    {
        $soon = now()->addDays(self::EXPIRY_SOON_DAYS)->toDateString();

        return $query->where(function (Builder $q) use ($soon) {
            $q->where(function ($q2) use ($soon) {
                $q2->whereNull('drivers_license_expiry')
                   ->orWhere('drivers_license_expiry', '>', $soon);
            })->where(function ($q2) use ($soon) {
                $q2->whereNull('insurance_expiry')
                   ->orWhere('insurance_expiry', '>', $soon);
            })->where(function ($q2) {
                $q2->whereNull('background_check_status')
                   ->orWhere('background_check_status', '!=', 'failed');
            })->where(function ($q2) {
                $q2->whereNull('drug_screen_status')
                   ->orWhere('drug_screen_status', '!=', 'failed');
            });
        });
    }
}
