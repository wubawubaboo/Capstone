<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestStatusHistory extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;

    public function serviceRequest() { return $this->belongsTo(ServiceRequest::class); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
