<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestStatusHistory extends Model
{
    protected $fillable = [
        'service_request_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public const UPDATED_AT = null;

    public function serviceRequest() { return $this->belongsTo(ServiceRequest::class); }
    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
