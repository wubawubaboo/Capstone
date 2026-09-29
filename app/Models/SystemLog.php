<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SystemLog extends Model
{
    /** @use HasFactory<\Database\Factories\SystemLogFactory> */
    use HasFactory;

    protected $fillable = [
        'barangay_id',
        'actor_id',
        'action_type',
        'module',
        'description',
        'ip_address',
    ];

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

    /**
     * Convenience wrapper around logAction() for the common case: the acting
     * user is the authenticated user, and the log entry belongs to their own
     * barangay. Pass $barangayId explicitly when logging an action that
     * targets a different barangay than the actor's own (e.g. an admin
     * managing a specific barangay's resources).
     */
    public static function record(string $actionType, string $module, string $description, ?int $barangayId = null)
    {
        return static::logAction(
            $barangayId ?? Auth::user()?->barangay_id,
            Auth::id(),
            $actionType,
            $module,
            $description
        );
    }
}
