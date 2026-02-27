<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $signature_data
 * @property string $signer_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $signed_at
 * @property string|null $token
 * @property \Illuminate\Support\Carbon|null $token_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereSignatureData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereSignerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceSignature whereUserAgent($value)
 * @mixin \Eloquent
 */
class ServiceSignature extends Model
{
    protected $fillable = [
        'service_request_id',
        'signature_data',
        'signer_name',
        'ip_address',
        'user_agent',
        'signed_at',
        'token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at'        => 'datetime',
            'token_expires_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Generate a unique signing token (valid for configurable hours).
     */
    public static function generateToken(ServiceRequest $serviceRequest): self
    {
        $hours = (int) Setting::getValue('location_link_expiry_hours', 4);

        return self::create([
            'service_request_id' => $serviceRequest->id,
            'signature_data'     => '',
            'signer_name'        => '',
            'signed_at'          => now(),
            'token'              => Str::random(48),
            'token_expires_at'   => now()->addHours($hours ?: 4),
        ]);
    }

    public function isTokenValid(): bool
    {
        return $this->token
            && $this->token_expires_at
            && $this->token_expires_at->isFuture()
            && empty($this->signature_data);
    }
}
