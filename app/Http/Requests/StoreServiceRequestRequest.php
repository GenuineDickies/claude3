<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
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
            'vehicle_year' => 'required|string|digits:4',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_color' => 'nullable|string|max:50',
            'service_type_id' => 'required|exists:service_types,id',
            'quoted_price' => 'required|numeric|min:0',
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
            'service_type_id.required' => 'Please select a service type.',
            'service_type_id.exists' => 'The selected service type is invalid.',
        ];
    }
}
