<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number',
            'barangay_id' => 'nullable|required_unless:role,admin|exists:barangays,id',
            'role' => 'required|in:secretary,vawc,admin',
            'password' => 'required|min:8|confirmed',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'start_of_residency' => 'required|integer|min:1900|max:' . date('Y'),
        ];
    }
}
