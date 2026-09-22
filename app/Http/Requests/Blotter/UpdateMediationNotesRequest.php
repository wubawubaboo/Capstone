<?php

namespace App\Http\Requests\Blotter;

use Illuminate\Foundation\Http\FormRequest;

/** Shared by the secretary and VAWC updateMediationNotes() actions. */
class UpdateMediationNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:10000',
        ];
    }
}
