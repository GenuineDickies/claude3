<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property bool $is_encrypted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 * @mixin \Eloquent
 */
class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    // ── Cache key used to bust the settings cache ─────────────
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600; // seconds

    /**
     * All known settings, grouped by section.
     * Each entry: label, help text, and whether the value is secret.
     */
    public static function definitions(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'fields' => [
                    'company_name' => [
                        'label'     => 'Company Name',
                        'help'      => 'Displayed in SMS messages, the sidebar, page titles, and the customer-facing location page.',
                        'encrypted' => false,
                        'placeholder' => 'Acme Roadside Assist',
                    ],
                    'company_tagline' => [
                        'label'     => 'Company Tagline',
                        'help'      => 'Short description shown on the dashboard and public pages (e.g. "24/7 Roadside Service").',
                        'encrypted' => false,
                        'placeholder' => 'Dispatch management',
                    ],
                    'company_logo' => [
                        'label'     => 'Company Logo',
                        'help'      => 'Brand logo shown in the application chrome and on the customer-facing location page. Upload a PNG, JPG, or WEBP with a transparent background when possible.',
                        'encrypted' => false,
                        'type'      => 'image',
                    ],
                    'company_phone' => [
                        'label'     => 'Company Phone',
                        'help'      => 'Business phone number displayed on receipts and public pages.',
                        'encrypted' => false,
                        'placeholder' => '+15551234567',
                    ],
                    'company_email' => [
                        'label'     => 'Company Email',
                        'help'      => 'Business email displayed on receipts.',
                        'encrypted' => false,
                        'placeholder' => 'info@example.com',
                        'type'      => 'email',
                    ],
                    'company_address' => [
                        'label'     => 'Company Address',
                        'help'      => 'Business mailing address displayed on receipts.',
                        'encrypted' => false,
                        'placeholder' => '123 Main St, Anytown, ST 12345',
                        'type'      => 'textarea',
                    ],
                    'location_link_expiry_hours' => [
                        'label'     => 'Location Link Expiry (hours)',
                        'help'      => 'How many hours a GPS location-sharing link stays valid before it expires. Default is 4.',
                        'encrypted' => false,
                        'placeholder' => '4',
                        'type'      => 'number',
                    ],
                    'compliance_tracking_enabled' => [
                        'label'     => 'Compliance Tracking',
                        'help'      => 'Enable technician compliance tracking (license, insurance, background checks). Enter "1" to enable or leave blank to disable.',
                        'encrypted' => false,
                        'placeholder' => '1',
                        'type'      => 'text',
                    ],
                    'estimate_approval_mode' => [
                        'label'     => 'Estimate Approval Requirement',
                        'help'      => 'At what dollar amount do customers need to approve estimates before work can begin?',
                        'encrypted' => false,
                        'type'      => 'approval_mode',
                    ],
                    'estimate_signature_threshold' => [
                        'label'     => 'Estimate Approval Threshold ($)',
                        'help'      => 'Estimates over this amount require customer approval. Only used when approval mode is set to threshold.',
                        'encrypted' => false,
                        'placeholder' => '200.00',
                        'type'      => 'number',
                        'hidden'    => true,
                    ],
                ],
            ],
            'google' => [
                'label' => 'Google Maps',
                'fields' => [
                    'google_maps_api_key' => [
                        'label'     => 'Google Maps API Key',
                        'help'      => 'Used for the embedded map customers see when confirming their location, the map dispatchers see on the service request page, and reverse-geocoding GPS coordinates into street addresses.',
                        'how_to'    => 'Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline text-blue-600">Google Cloud Console → APIs &amp; Services → Credentials</a>. Create an API key (or use an existing one). Then enable these APIs for the project: <strong>Maps Embed API</strong>, <strong>Maps JavaScript API</strong>, and <strong>Geocoding API</strong>. Restrict the key to your domain under "Application restrictions → HTTP referrers".',
                        'encrypted' => true,
                        'placeholder' => 'AIza…',
                    ],
                ],
            ],
            'telnyx' => [
                'label' => 'Telnyx SMS',
                'fields' => [
                    'telnyx_api_key' => [
                        'label'     => 'Telnyx API Key',
                        'help'      => 'API v2 key used to send outbound SMS messages.',
                        'how_to'    => 'Sign in to <a href="https://portal.telnyx.com/" target="_blank" class="underline text-blue-600">Telnyx Mission Control</a>. Go to <strong>Auth → API Keys</strong> and create a new key (or copy an existing one). It starts with <code>KEY…</code>.',
                        'encrypted' => true,
                        'placeholder' => 'KEY0123456789…',
                    ],
                    'telnyx_public_key' => [
                        'label'     => 'Telnyx Public Key',
                        'help'      => 'Base64-encoded ED25519 public key used to verify that inbound webhook requests genuinely come from Telnyx.',
                        'how_to'    => 'In Telnyx Mission Control, go to <strong>Auth → API Keys</strong>. Find the "Public Key" listed on that page (base64 string). Copy the full value.',
                        'encrypted' => false,
                        'placeholder' => 'abc123…base64…',
                    ],
                    'telnyx_from_number' => [
                        'label'     => 'Telnyx From Number',
                        'help'      => 'The phone number SMS messages are sent from, in E.164 format.',
                        'how_to'    => 'In Telnyx Mission Control, go to <strong>Numbers → My Numbers</strong>. Copy the number in E.164 format (e.g. <code>+1XXXXXXXXXX</code>). The number must have a Messaging Profile assigned.',
                        'encrypted' => false,
                        'placeholder' => '+1XXXXXXXXXX',
                    ],
                    'telnyx_messaging_profile_id' => [
                        'label'     => 'Telnyx Messaging Profile ID',
                        'help'      => 'Associates outbound messages with your 10DLC campaign and sender number.',
                        'how_to'    => 'In Telnyx Mission Control, go to <strong>Messaging → Messaging Profiles</strong>. Click on your profile and copy the UUID shown at the top of the page.',
                        'encrypted' => false,
                        'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                    ],
                ],
            ],
            'advanced' => [
                'label' => 'Advanced',
                'fields' => [
                    'location_base_url' => [
                        'label'     => 'Location Page Base URL',
                        'help'      => 'Public HTTPS URL for the customer-facing location page. Only set this if you deploy a standalone locate script on a different domain. Leave blank to use this app\'s built-in <code>/locate/…</code> route.',
                        'how_to'    => 'If your Laravel app is publicly accessible over HTTPS, leave this blank. If you use the standalone <code>locate.php</code> proxy script on your hosting, enter its URL, e.g. <code>https://yourdomain.com/webhook-proxy/locate.php</code>.',
                        'encrypted' => false,
                        'type'      => 'url',
                        'placeholder' => 'https://yourdomain.com/webhook-proxy/locate.php',
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve a single setting value.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        $setting = $all[$key];

        if ($setting['is_encrypted'] && $setting['value'] !== null && $setting['value'] !== '') {
            try {
                return Crypt::decryptString($setting['value']);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $setting['value'] ?? $default;
    }

    /**
     * Persist a setting value.
     */
    public static function setValue(string $key, ?string $value, bool $encrypted = false): void
    {
        $storedValue = $value;

        if ($encrypted && $value !== null && $value !== '') {
            $storedValue = Crypt::encryptString($value);
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value'        => $storedValue,
                'is_encrypted' => $encrypted,
                'group'        => static::groupForKey($key),
            ],
        );

        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Load all settings from cache or DB.
     *
     * @return array<string, array{value: ?string, is_encrypted: bool}>
     */
    public static function allCached(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return static::all()
                ->keyBy('key')
                ->map(fn (self $s) => [
                    'value'        => $s->value,
                    'is_encrypted' => $s->is_encrypted,
                ])
                ->toArray();
        });
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Resolve the public URL for the configured company logo.
     */
    public static function companyLogoUrl(): ?string
    {
        $value = static::getValue('company_logo');

        if (is_string($value) && $value !== '') {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }

            if (str_starts_with($value, '/')) {
                return $value;
            }

            return route('branding.logo');
        }

        $legacyLogo = public_path('images/company-logo.jpg');

        return is_file($legacyLogo) ? asset('images/company-logo.jpg') : null;
    }

    /**
     * Delete a stored logo file if it belongs to the local disk.
     */
    public static function deleteStoredLogo(?string $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) || str_starts_with($value, '/')) {
            return;
        }

        Storage::disk('local')->delete($value);
    }

    /**
     * Determine the group a key belongs to based on definitions.
     */
    private static function groupForKey(string $key): string
    {
        foreach (static::definitions() as $group => $section) {
            if (array_key_exists($key, $section['fields'])) {
                return $group;
            }
        }

        return 'general';
    }

    /**
     * Mask a secret value for display (show last 4 chars).
     */
    public static function masked(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (strlen($value) <= 4) {
            return str_repeat('•', strlen($value));
        }

        return str_repeat('•', strlen($value) - 4) . substr($value, -4);
    }
}
