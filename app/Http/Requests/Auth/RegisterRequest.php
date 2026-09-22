<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:11', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'address' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'start_of_residency' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'id_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'selfie_id_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }
}
