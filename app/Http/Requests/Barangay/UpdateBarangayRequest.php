<?php

namespace App\Http\Requests\Barangay;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by the admin's update() (route-bound {barangay}) and the
 * secretary's updateProfile() (the authenticated user's own barangay).
 */
class UpdateBarangayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barangay = $this->route('barangay') ?? $this->user()->barangay;

        return [
            'name' => 'required|string|max:255|unique:barangays,name,' . $barangay->id,
            'contact_number' => 'nullable|string|max:255',
        ];
    }
}
