<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $rules = [];
        foreach ($definitions as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                $rules['settings.' . $key] = $this->validationRulesFor($field);
            }
        }

        $request->validate($rules);

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

        $request->validate([
            'value' => $this->validationRulesFor($field),
        ]);

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
     * PUT /settings/approval-mode — save the estimate approval mode + threshold together.
     */
    public function updateApprovalMode(Request $request): RedirectResponse
    {
        $request->validate([
            'approval_mode'    => 'required|string|in:all,none,threshold',
            'threshold_amount' => 'nullable|numeric|min:0.01|max:10000',
        ]);

        $mode = $request->input('approval_mode');

        Setting::setValue('estimate_approval_mode', $mode);

        if ($mode === 'threshold') {
            $amount = $request->input('threshold_amount');
            if ($amount === null || $amount === '') {
                return redirect()->route('settings.edit')
                    ->withErrors(['threshold_amount' => 'Please enter a dollar amount for the threshold.']);
            }
            Setting::setValue('estimate_signature_threshold', $amount);
        }

        return redirect()->route('settings.edit')->with('success', 'Estimate approval setting saved.');
    }

    /**
     * Build validation rules for a field definition.
     */
    private function validationRulesFor(array $field): array
    {
        $rules = ['nullable', 'string', 'max:1000'];

        $type = $field['type'] ?? 'text';

        return match ($type) {
            'url'      => ['nullable', 'string', 'max:2048', 'url:https'],
            'email'    => ['nullable', 'string', 'max:255', 'email'],
            'number'   => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'textarea' => ['nullable', 'string', 'max:5000'],
            default    => $rules,
        };
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
