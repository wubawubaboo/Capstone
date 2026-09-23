<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    protected $guarded = [];

    public function report() { return $this->belongsTo(Report::class); }
    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
