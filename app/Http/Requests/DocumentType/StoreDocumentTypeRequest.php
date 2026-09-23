<?php

namespace App\Http\Requests\DocumentType;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'base_fee' => $this->base_fee === '' ? null : $this->base_fee,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'base_fee' => 'nullable|numeric|min:0',
            'template_type' => 'required_with:template_file|nullable|in:docx,image',
            'template_file' => [
                'nullable',
                'file',
                function ($attribute, $value, $fail) {
                    if ($this->input('template_type') === 'docx' && strtolower($value->getClientOriginalExtension()) !== 'docx') {
                        $fail('The template must be a .docx file.');
                    }
                    if ($this->input('template_type') === 'image' && !in_array(strtolower($value->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])) {
                        $fail('The template must be a JPG or PNG image.');
                    }
                },
            ],
        ];
    }
}
