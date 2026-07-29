<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model {
    use HasFactory;

    public function user() {return $this->belongsTo(User::class, 'user_id');}
    public function blotter() { return $this->hasOne(BlotterRecord::class, 'report_id');}

    protected $fillable = [
        'user_id',
        'incident_type',
        'description',
        'latitude',
        'longitude',
        'status',
        'attachment_path'
    ];

    public function reporter() {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute() {
        if ($this->attachment_path) {
            return route('resident.reports.attachment', $this->id);
        }
        return null;
    }
}
