<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class RejectAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:Blurry ID,Mismatched Information,Invalid ID,Expired ID'],
            'custom_message' => ['nullable', 'string', 'max:150'],
        ];
    }
}
