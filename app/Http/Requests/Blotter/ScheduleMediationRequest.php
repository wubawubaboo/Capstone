<?php

namespace App\Http\Requests\Blotter;

use Illuminate\Foundation\Http\FormRequest;

/** Shared by the secretary and VAWC scheduleMediation() actions. */
class ScheduleMediationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => 'required|date|after:now',
            'status' => 'nullable|string|max:50',
        ];
    }
}
