<?php

namespace App\Http\Requests\Blotter;

use Illuminate\Foundation\Http\FormRequest;

/** Shared by the secretary and VAWC reopenCase() actions. */
class ReopenCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
        ];
    }
}
