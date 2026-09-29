<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStatusHistory extends Model
{
    protected $fillable = [
        'blotter_record_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public const UPDATED_AT = null;

    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_record_id'); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
