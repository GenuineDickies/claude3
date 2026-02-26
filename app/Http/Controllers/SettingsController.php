<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /settings
     */
    public function edit()
    {
        $definitions = Setting::definitions();

        // Load current values for display
        $values = [];
        foreach ($definitions as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                $raw = Setting::getValue($key);
                $values[$key] = [
                    'raw'    => $raw,
                    'masked' => $field['encrypted'] ? Setting::masked($raw) : null,
                ];
            }
        }

        return view('settings.edit', compact('definitions', 'values'));
    }

    /**
     * PUT /settings
     */
    public function update(Request $request): RedirectResponse
    {
        $definitions = Setting::definitions();

        foreach ($definitions as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                $inputValue = $request->input('settings.' . $key);

                // For encrypted fields: skip if the user left the masked placeholder unchanged
                if ($field['encrypted'] && $this->isUnchangedMask($inputValue)) {
                    continue;
                }

                // Treat empty strings as null for cleanliness
                $value = ($inputValue === '' || $inputValue === null) ? null : $inputValue;

                // Sanitise URL fields
                if ($value !== null && ($field['type'] ?? '') === 'url') {
                    $value = $this->sanitiseUrl($value);
                }

                Setting::setValue($key, $value, $field['encrypted']);
            }
        }

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }

    /**
     * PUT /settings/{key} — save a single setting field.
     */
    public function updateSingle(Request $request, string $key): RedirectResponse
    {
        $field = $this->findFieldDefinition($key);

        if (!$field) {
            abort(404);
        }

        $inputValue = $request->input('value');

        // For encrypted fields: skip if the user left the masked placeholder unchanged
        if ($field['encrypted'] && $this->isUnchangedMask($inputValue)) {
            return redirect()->route('settings.edit')->with('success', $field['label'] . ' unchanged.');
        }

        $value = ($inputValue === '' || $inputValue === null) ? null : $inputValue;

        // Sanitise URL fields — fix common typos like extra colons/slashes in scheme
        if ($value !== null && ($field['type'] ?? '') === 'url') {
            $value = $this->sanitiseUrl($value);
        }

        Setting::setValue($key, $value, $field['encrypted']);

        $action = $value === null ? 'cleared' : 'saved';

        return redirect()->route('settings.edit')->with('success', $field['label'] . ' ' . $action . '.');
    }

    /**
     * Look up a field definition by key.
     */
    private function findFieldDefinition(string $key): ?array
    {
        foreach (Setting::definitions() as $section) {
            if (isset($section['fields'][$key])) {
                return $section['fields'][$key];
            }
        }

        return null;
    }

    /**
     * Detect when a masked field was submitted unchanged (all bullets or empty).
     */
    private function isUnchangedMask(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // Matches strings that are entirely bullet characters (•)
        return (bool) preg_match('/^[•]+$/', $value);
    }

    /**
     * Fix common URL typos (extra colons/slashes in scheme, trailing whitespace).
     */
    private function sanitiseUrl(string $url): string
    {
        $url = trim($url);

        // Normalise scheme — collapse "https::://" or "https://" to "https://"
        $url = preg_replace('#^(https?)\s*:+\s*/+#i', '$1://', $url);

        return $url;
    }
}
