<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'file_path',
        'file_type',
        'report_id',
        'blotter_record_id',
    ];

    public function report() { return $this->belongsTo(Report::class); }
    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_record_id'); }
}
