<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    /** @use HasFactory<\Database\Factories\SystemLogFactory> */
    use HasFactory;

    protected $guarded = [];
    public const UPDATED_AT = null;

    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    public static function logAction($barangayId, $actorId, $actionType, $module, $description, $ip = null)
    {
        return self::create([
            'barangay_id' => $barangayId,
            'actor_id'    => $actorId,
            'action_type' => $actionType,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => $ip ?? request()->ip(),
        ]);
    }
}
