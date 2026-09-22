<?php

namespace App\Http\Requests\Vawc;

use Illuminate\Foundation\Http\FormRequest;

class StoreVawcBlotterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'nullable|exists:reports,id',

            'is_registered_complainant' => 'required|boolean',
            'complainant_id' => 'nullable|required_if:is_registered_complainant,true,1|exists:users,id',
            'complainant_name' => 'nullable|required_if:is_registered_complainant,false,0|string|max:255',

            'is_registered_respondent' => 'required|boolean',
            'receiver_id' => 'nullable|required_if:is_registered_respondent,true,1|exists:users,id',
            'receiver_name' => 'nullable|required_if:is_registered_respondent,false,0|string|max:255',

            'incident_type' => 'nullable|required_without:report_id|string|max:255',
            'description' => 'nullable|required_without:report_id|string',
            'confidential_notes' => 'nullable|string',
        ];
    }
}
