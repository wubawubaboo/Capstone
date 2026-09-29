<?php
namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Report extends Model {
    use HasFactory;

    public function user() {return $this->belongsTo(User::class, 'user_id');}
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function blotter() { return $this->hasOne(BlotterRecord::class, 'report_id');}
    public function statusHistory() { return $this->hasMany(ReportStatusHistory::class)->latest('id'); }

    public const SOS_TYPE = 'SOS_CRITICAL';

    /**
     * A report belongs to the barangay it was filed in: the reporter's
     * barangay at filing time. It stays there if the resident later moves.
     */
    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->barangay_id ??= User::whereKey($report->user_id)->value('barangay_id');
        });
    }

    public function scopeForBarangay($query, $barangayId)
    {
        return $query->where('barangay_id', $barangayId);
    }

    /**
     * Reports the given staff member's desk handles, within their barangay.
     * Resident-flagged VAWC reports are confidential to the vawc desk; the
     * secretary desk gets everything else, including every SOS alert.
     * Keep in sync with isHandledBy().
     */
    public function scopeVisibleTo($query, User $user)
    {
        $query->forBarangay($user->barangay_id);

        return match ($user->role) {
            Role::Secretary => $query->where('is_vawc', false),
            Role::Vawc => $query->where('is_vawc', true),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** Open reports that no blotter case has been filed from yet. */
    public function scopeAwaitingBlotter($query)
    {
        return $query->whereDoesntHave('blotter')
            ->whereIn('status', [ReportStatus::Pending, ReportStatus::InProgress]);
    }

    /**
     * Whether a staff desk of the given role handles this report.
     * Keep in sync with scopeVisibleTo().
     */
    public function isHandledBy(?Role $role): bool
    {
        return match ($role) {
            Role::Secretary => !$this->is_vawc,
            Role::Vawc => $this->is_vawc,
            default => false,
        };
    }

    protected $fillable = [
        'user_id',
        'barangay_id',
        'incident_type',
        'description',
        'is_vawc',
        'latitude',
        'longitude',
        'status',
        'attachment_path',
        'acknowledged_at',
        'responded_at',
        'resolved_at',
    ];

    /** Match the column default, so a just-created report isn't "null" VAWC. */
    protected $attributes = [
        'is_vawc' => false,
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'description' => 'encrypted',
        'is_vawc' => 'boolean',
        'acknowledged_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Moves the report to a new status following the allowed transition map,
     * recording a timeline entry. Throws for illegal transitions.
     */
    public function transitionStatus(ReportStatus $to, ?User $actor = null, ?string $note = null): void
    {
        $from = $this->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidStatusTransitionException("Report #{$this->id} cannot move from \"{$from->value}\" to \"{$to->value}\".");
        }

        DB::transaction(function () use ($from, $to, $actor, $note) {
            $this->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor?->id,
                'note' => $note,
            ]);

            $this->update(['status' => $to]);
        });
    }

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute() {
        if ($this->attachment_path) {
            return route('resident.reports.attachment', $this->id);
        }
        return null;
    }
}
