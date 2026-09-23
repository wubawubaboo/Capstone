<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStatusHistory extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;

    public function report() { return $this->belongsTo(Report::class); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
