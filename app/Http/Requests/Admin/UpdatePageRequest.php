<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Page $page */
        $page = $this->route('page');

        return [
            'page_name' => ['required', 'string', 'max:255'],
            'page_path' => ['required', 'string', 'max:255', 'regex:/^\/.+/', Rule::unique('pages', 'page_path')->ignore($page)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}