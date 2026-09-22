<?php

namespace App\Models;

use App\Enums\DocumentTemplateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTypeFactory> */
    use HasFactory;protected $guarded = [];
    protected $casts = [
        'field_positions_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function requests() { return $this->hasMany(DocumentRequest::class); }

    public function hasTemplate(): bool
    {
        return !empty($this->template_path);
    }

    public function isDocxTemplate(): bool
    {
        return $this->template_type === DocumentTemplateType::Docx->value;
    }

    public function isImageTemplate(): bool
    {
        return $this->template_type === DocumentTemplateType::Image->value;
    }
}
