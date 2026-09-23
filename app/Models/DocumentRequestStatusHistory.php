<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestStatusHistory extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;

    public function documentRequest() { return $this->belongsTo(DocumentRequest::class); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
