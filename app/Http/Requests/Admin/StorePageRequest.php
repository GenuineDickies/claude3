<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page_name' => ['required', 'string', 'max:255'],
            'page_path' => ['required', 'string', 'max:255', 'regex:/^\/.+/', 'unique:pages,page_path'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}