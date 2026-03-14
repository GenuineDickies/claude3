<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_mobile_phone' => $this->boolean('requires_mobile_phone'),
            'requires_sms_consent' => $this->boolean('requires_sms_consent'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'role_name' => ['required', 'string', 'max:255', Rule::unique('roles', 'role_name')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'requires_mobile_phone' => ['required', 'boolean'],
            'requires_sms_consent' => ['required', 'boolean'],
        ];
    }
}