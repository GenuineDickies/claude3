<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreServiceRequestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $streetAddress = trim((string) $this->input('street_address', ''));
        $city = trim((string) $this->input('city', ''));
        $state = trim((string) $this->input('state', ''));

        $locationParts = array_values(array_filter([
            $streetAddress,
            $city,
            $state,
        ], static fn (string $value): bool => $value !== ''));

        $location = $locationParts !== []
            ? implode(', ', $locationParts)
            : $this->input('location');

        $this->merge([
            'street_address' => $streetAddress,
            'city' => $city,
            'state' => $state,
            'location' => $location !== null ? trim((string) $location) : null,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'customer_action' => 'required|in:use_existing,create_new',
            'lead_id' => 'nullable|integer|exists:leads,id',
            'vehicle_year' => 'required|string|digits:4',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_color' => 'nullable|string|max:50',
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'quoted_price' => 'required|numeric|min:0',
            'street_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'verbal_opt_in' => 'nullable|boolean',
            'send_location_request' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_year.digits' => 'Vehicle year must be 4 digits.',
            'catalog_item_id.required' => 'Please select a service type.',
            'catalog_item_id.exists' => 'The selected service type is invalid.',
        ];
    }
}
