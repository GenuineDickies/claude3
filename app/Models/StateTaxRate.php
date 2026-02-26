<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $state_code
 * @property string $state_name
 * @property numeric $tax_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereStateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereStateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateTaxRate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StateTaxRate extends Model
{
    protected $fillable = [
        'state_code',
        'state_name',
        'tax_rate',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
    ];

    /**
     * Get the tax rate for a given state code.
     */
    public static function rateForState(string $stateCode): ?float
    {
        $record = static::where('state_code', strtoupper($stateCode))->first();

        return $record?->tax_rate;
    }

    /**
     * All US states and territories with their codes.
     */
    public static function stateList(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
            'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
            'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
            'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
            'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
            'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
            'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
            'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
            'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        ];
    }
}
