<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'budget' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
        ];
    }
}
