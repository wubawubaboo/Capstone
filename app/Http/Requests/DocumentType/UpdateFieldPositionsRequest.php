<?php

namespace App\Http\Requests\DocumentType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'positions' => 'required|array',
            'positions.*.field_key' => 'required|string',
            'positions.*.x' => 'required|numeric',
            'positions.*.y' => 'required|numeric',
            'positions.*.width' => 'required|numeric',
            'positions.*.height' => 'required|numeric',
            'positions.*.font_size' => 'required|numeric',
            'positions.*.font_align' => 'required|string',
            'positions.*.font_weight' => 'required|string',
        ];
    }
}
