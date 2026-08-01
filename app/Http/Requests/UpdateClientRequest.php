<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('clients', 'email')->ignore($this->route('client'))],
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ];
    }
}
