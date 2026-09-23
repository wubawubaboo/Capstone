<?php
namespace App\Models;

use App\Enums\ReportStatus;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Report extends Model {
    use HasFactory;

    public function user() {return $this->belongsTo(User::class, 'user_id');}
    public function blotter() { return $this->hasOne(BlotterRecord::class, 'report_id');}
    public function statusHistory() { return $this->hasMany(ReportStatusHistory::class)->latest('id'); }

    public function scopeForBarangay($query, $barangayId)
    {
        return $query->whereHas('user', fn ($q) => $q->where('barangay_id', $barangayId));
    }

    protected $fillable = [
        'user_id',
        'incident_type',
        'description',
        'latitude',
        'longitude',
        'status',
        'attachment_path',
        'acknowledged_at',
        'responded_at',
        'resolved_at',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
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
