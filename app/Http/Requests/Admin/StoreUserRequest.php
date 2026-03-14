<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'phone' => $this->phoneRules(),
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists(Role::class, 'id')],
        ];
    }

    private function phoneRules(): array
    {
        return [
            Rule::requiredIf(fn () => $this->input('status') === 'active' && $this->selectedRolesRequireMobilePhone()),
            'nullable',
            'string',
            'max:20',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $normalized = User::normalizePhone(is_string($value) ? $value : null);

                if ($normalized !== null && strlen($normalized) < 10) {
                    $fail('The mobile phone must contain at least 10 digits.');
                }
            },
        ];
    }

    private function selectedRolesRequireMobilePhone(): bool
    {
        $roleIds = array_map('intval', Arr::wrap($this->input('role_ids', [])));

        if ($roleIds === []) {
            return false;
        }

        return Role::query()->whereIn('id', $roleIds)->get()->contains(fn (Role $role): bool => $role->requiresMobilePhone());
    }
}