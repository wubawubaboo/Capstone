<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestStatusHistory extends Model
{
    protected $fillable = [
        'document_request_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public const UPDATED_AT = null;

    public function documentRequest() { return $this->belongsTo(DocumentRequest::class); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
