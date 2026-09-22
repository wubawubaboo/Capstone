<?php

namespace App\Http\Requests\Blotter;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlotterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'nullable|exists:reports,id',

            'complainant_id' => 'nullable|required_without:report_id|exists:users,id',
            'incident_type' => 'nullable|required_without:report_id|string|max:255',
            'description' => 'nullable|required_without:report_id|string',

            'is_registered_respondent' => 'required|boolean',

            'receiver_id' => 'nullable|required_if:is_registered_respondent,true,1|exists:users,id',
            'receiver_name' => 'nullable|required_if:is_registered_respondent,false,0|string|max:255',
        ];
    }
}
