<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Setting;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $body
 * @property string $category
 * @property array<array-key, mixed>|null $variables
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate category(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageTemplate whereVariables($value)
 * @mixin \Eloquent
 */
class MessageTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'body',
        'category',
        'variables',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Auto-generate slug from name when creating.
     */
    protected static function booted(): void
    {
        static::creating(function (self $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ------------------------------------------------------------------
    // Variable helpers
    // ------------------------------------------------------------------

    /**
     * All available variable definitions grouped by source.
     *
     * Each key is a variable placeholder (e.g. "customer_first_name").
     * Values describe where the data comes from so the UI can display them
     * and the rendering engine can auto-resolve them when context is available.
     */
    public static function availableVariables(): array
    {
        return [
            // Customer fields
            'customer_first_name' => [
                'label'  => 'Customer First Name',
                'source' => 'customer',
                'field'  => 'first_name',
            ],
            'customer_last_name' => [
                'label'  => 'Customer Last Name',
                'source' => 'customer',
                'field'  => 'last_name',
            ],
            'customer_phone' => [
                'label'  => 'Customer Phone',
                'source' => 'customer',
                'field'  => 'phone',
            ],

            // Service-request fields
            'service_request_id' => [
                'label'  => 'Ticket #',
                'source' => 'service_request',
                'field'  => 'id',
            ],
            'service_type' => [
                'label'  => 'Service Type',
                'source' => 'service_request',
                'field'  => 'catalogItem.name',
            ],
            'quoted_price' => [
                'label'  => 'Quoted Price',
                'source' => 'service_request',
                'field'  => 'quoted_price',
            ],
            'location' => [
                'label'  => 'Service Location',
                'source' => 'service_request',
                'field'  => 'location',
            ],
            'status' => [
                'label'  => 'Request Status',
                'source' => 'service_request',
                'field'  => 'status',
            ],

            // Vehicle fields
            'vehicle_year' => [
                'label'  => 'Vehicle Year',
                'source' => 'service_request',
                'field'  => 'vehicle_year',
            ],
            'vehicle_make' => [
                'label'  => 'Vehicle Make',
                'source' => 'service_request',
                'field'  => 'vehicle_make',
            ],
            'vehicle_model' => [
                'label'  => 'Vehicle Model',
                'source' => 'service_request',
                'field'  => 'vehicle_model',
            ],
            'vehicle_color' => [
                'label'  => 'Vehicle Color',
                'source' => 'service_request',
                'field'  => 'vehicle_color',
            ],

            // Business / misc
            'company_name' => [
                'label'  => 'Company Name',
                'source' => 'config',
                'field'  => 'app.name',
            ],
            'company_phone' => [
                'label'  => 'Company Phone',
                'source' => 'config',
                'field'  => 'services.telnyx.from_number',
            ],

            // Location sharing
            'location_link' => [
                'label'  => 'Location Share Link',
                'source' => 'service_request',
                'field'  => 'locationShareUrl',
            ],
        ];
    }

    /**
     * Extract placeholder names found in the template body.
     * Placeholders use double-curly syntax: {{ variable_name }}
     *
     * @return string[]
     */
    public function extractPlaceholders(): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_]+)\s*\}\}/', $this->body, $matches);

        return array_unique($matches[1]);
    }

    /**
     * Render the template body, replacing placeholders with provided values.
     *
     * @param  array<string, string|null>  $variables  key => value pairs
     * @return string Rendered message text
     */
    public function render(array $variables = []): string
    {
        $text = $this->body;

        foreach ($variables as $key => $value) {
            $text = preg_replace(
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                (string) ($value ?? ''),
                $text
            );
        }

        return $text;
    }

    /**
     * Auto-resolve variable values from available context objects.
     *
     * @param  Customer|null        $customer
     * @param  ServiceRequest|null  $serviceRequest
     * @return array<string, string|null>
     */
    public function resolveVariables(?Customer $customer = null, ?ServiceRequest $serviceRequest = null): array
    {
        $available = self::availableVariables();
        $placeholders = $this->extractPlaceholders();
        $resolved = [];

        foreach ($placeholders as $key) {
            if (! isset($available[$key])) {
                $resolved[$key] = null;
                continue;
            }

            $def = $available[$key];

            $resolved[$key] = match ($def['source']) {
                'customer' => $customer?->{$def['field']},
                'service_request' => $this->resolveServiceRequestField($serviceRequest, $def['field']),
                'config' => $this->resolveConfigField($def['field']),
                default => null,
            };
        }

        return $resolved;
    }

    /**
     * Resolve a value from a ServiceRequest, supporting both properties and methods.
     */
    private function resolveServiceRequestField(?ServiceRequest $sr, string $field): mixed
    {
        if (! $sr) {
            return null;
        }

        // If it's a method on the model (e.g. locationShareUrl), call it
        if (method_exists($sr, $field)) {
            return $sr->{$field}();
        }

        return data_get($sr, $field);
    }

    /**
     * Resolve a config-sourced variable, preferring the DB setting over config().
     */
    private function resolveConfigField(string $configKey): mixed
    {
        // Map config keys to their Setting DB keys
        $settingMap = [
            'app.name'                      => 'company_name',
            'services.telnyx.from_number'   => 'telnyx_from_number',
        ];

        $settingKey = $settingMap[$configKey] ?? null;

        if ($settingKey) {
            $dbValue = Setting::getValue($settingKey);
            if ($dbValue !== null && $dbValue !== '') {
                return $dbValue;
            }
        }

        return config($configKey);
    }

    /**
     * Convenient one-shot: resolve + render.
     */
    public function renderWith(?Customer $customer = null, ?ServiceRequest $serviceRequest = null, array $overrides = []): string
    {
        $vars = array_merge($this->resolveVariables($customer, $serviceRequest), $overrides);

        return $this->render($vars);
    }

    /**
     * Template category options for the UI.
     */
    public static function categories(): array
    {
        return [
            'compliance'    => '10DLC Compliance',
            'dispatch'      => 'Dispatch & Status',
            'confirmation'  => 'Confirmations',
            'follow_up'     => 'Follow-up',
            'payment'       => 'Payment',
            'general'       => 'General',
        ];
    }
}
