<?php

namespace App\Http\Requests\Barangay;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:barangays',
            'contact_number' => 'nullable|string|max:255',
        ];
    }
}
