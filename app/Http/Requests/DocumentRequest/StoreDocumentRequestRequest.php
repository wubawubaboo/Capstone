<?php

namespace App\Http\Requests\DocumentRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => 'required|exists:document_types,id',
            'purpose' => 'required|string|max:500',
        ];
    }
}
