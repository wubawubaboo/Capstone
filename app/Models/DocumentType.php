<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTypeFactory> */
    use HasFactory;protected $guarded = [];
    protected $casts = ['requirements_json' => 'array'];

    public function requests() { return $this->hasMany(DocumentRequest::class); }

    // Utility
    public function hasRequirement($requirementName)
    {
        $reqs = $this->requirements_json ?? [];
        return in_array($requirementName, $reqs);
    }
}
