<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by updatePolice() and updateResident() — both edit the same set of
 * personal-detail fields on a User record.
 */
class UpdateStaffAccountRequest extends FormRequest
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
