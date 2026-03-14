<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
        return [
            'role_name' => ['required', 'string', 'max:255', 'unique:roles,role_name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'requires_mobile_phone' => ['required', 'boolean'],
            'requires_sms_consent' => ['required', 'boolean'],
        ];
    }
}