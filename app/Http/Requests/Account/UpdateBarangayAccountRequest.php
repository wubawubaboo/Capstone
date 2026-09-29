<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A secretary editing the personal details of a resident or barangay_police
 * account in their barangay. Citywide staff accounts are edited through
 * App\Http\Requests\Admin\UpdateStaffAccountRequest instead.
 */
class UpdateBarangayAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:11', 'unique:users,phone_number,' . $userId],
            'address' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'start_of_residency' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
        ];
    }
}
