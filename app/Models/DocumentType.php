<?php

namespace App\Models;

use App\Enums\DocumentTemplateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentType extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTypeFactory> */
    use HasFactory;protected $guarded = [];
    protected $casts = [
        'field_positions_json' => 'array',
        'is_active' => 'boolean',
        'template_type' => DocumentTemplateType::class,
    ];

    public function requests() { return $this->hasMany(DocumentRequest::class); }

    public function hasTemplate(): bool
    {
        return !empty($this->template_path);
    }

    public function isDocxTemplate(): bool
    {
        return $this->template_type === DocumentTemplateType::Docx;
    }

    public function isImageTemplate(): bool
    {
        return $this->template_type === DocumentTemplateType::Image;
    }

    /**
     * Replaces the template file, resetting any field-position layout since
     * it was tied to the old file. For image templates, records the image's
     * pixel dimensions so the field-position editor can scale correctly.
     */
    public function attachTemplate(UploadedFile $file, DocumentTemplateType $type): void
    {
        if ($this->template_path) {
            Storage::delete($this->template_path);
        }

        $path = $file->store('document_templates');

        $this->template_type = $type;
        $this->template_path = $path;
        $this->field_positions_json = null;
        $this->template_image_width = null;
        $this->template_image_height = null;

        if ($type === DocumentTemplateType::Image) {
            $dimensions = getimagesize(Storage::path($path));
            if ($dimensions) {
                $this->template_image_width = $dimensions[0];
                $this->template_image_height = $dimensions[1];
            }
        }
    }

    public function clearTemplate(): void
    {
        if ($this->template_path) {
            Storage::delete($this->template_path);
        }

        $this->update([
            'template_type' => null,
            'template_path' => null,
            'field_positions_json' => null,
            'template_image_width' => null,
            'template_image_height' => null,
        ]);
    }
}
