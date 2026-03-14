<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                new RequiredIf($user !== null && $user->requiresMobilePhone()),
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = User::normalizePhone(is_string($value) ? $value : null);

                    if ($normalized !== null && strlen($normalized) < 10) {
                        $fail('The mobile phone must contain at least 10 digits.');
                    }
                },
            ],
            'grant_sms_consent' => [
                new RequiredIf($user !== null && $user->requiresSmsConsent() && ! $user->hasSmsConsent()),
                'nullable',
                'accepted',
            ],
        ];
    }
}
