<?php

namespace App\Http\Requests\DocumentRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:Ready,Claimed',
        ];
    }
}
