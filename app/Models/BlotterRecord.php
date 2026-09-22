<?php

namespace App\Models;

use App\Enums\BlotterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BlotterRecord extends Model
{
    /** @use HasFactory<\Database\Factories\BlotterRecordFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'official_entry_date' => 'datetime',
        'status' => BlotterStatus::class,
    ];

    public function report() { return $this->belongsTo(Report::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function vawcDetail() { return $this->hasOne(VawcDetail::class, 'blotter_record_id'); }
    public function mediations() { return $this->hasMany(MediationSchedule::class, 'blotter_record_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'blotter_id'); }
    public function statusHistory() { return $this->hasMany(CaseStatusHistory::class)->latest('id'); }

    // Utility
    public function isVawcCase()
    {
        return $this->vawcDetail()->exists();
    }

    public function scheduleMediation($date, $meetingNumber = 1)
    {
        return $this->mediations()->create([
            'scheduled_date' => $date,
            'meeting_number' => $meetingNumber,
            'status' => 'Scheduled'
        ]);
    }

    /**
     * Generates the next sequential case number for a barangay's cases filed
     * this year. Must be called inside a DB::transaction() — the lockForUpdate
     * serializes concurrent requests against the same barangay/year so two
     * simultaneous filings can't compute the same next number.
     */
    public static function generateCaseNumber(string $prefix, int $barangayId): string
    {
        $count = static::where('barangay_id', $barangayId)
            ->whereYear('created_at', now()->year)
            ->lockForUpdate()
            ->count();

        return sprintf('%s-%s-%04d', $prefix, now()->year, $count + 1);
    }

    /**
     * Moves the case to a new status following the allowed transition map,
     * recording a timeline entry. Throws for illegal transitions (e.g. jumping
     * out of a closed case) — callers should use reopen() for that instead.
     */
    public function transitionStatus(BlotterStatus $to, User $actor, ?string $note = null): void
    {
        $from = $this->status;

        if (!$from->canTransitionTo($to)) {
            throw new \DomainException("Case #{$this->case_number} cannot move from \"{$from->value}\" to \"{$to->value}\". Reopen the case first if it is closed.");
        }

        DB::transaction(function () use ($from, $to, $actor, $note) {
            $this->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor->id,
                'note' => $note,
            ]);

            $this->update(['status' => $to]);
        });
    }

    /**
     * Reopens a closed (Resolved/EscalatedToCourt) case back to an active
     * status, requiring a reason so the timeline shows why it was revisited.
     */
    public function reopen(BlotterStatus $to, User $actor, string $reason): void
    {
        if (!$this->status->isTerminal()) {
            throw new \DomainException("Case #{$this->case_number} is not closed, so it cannot be reopened.");
        }

        DB::transaction(function () use ($to, $actor, $reason) {
            $this->statusHistory()->create([
                'from_status' => $this->status->value,
                'to_status' => $to->value,
                'changed_by' => $actor->id,
                'note' => "[REOPENED] {$reason}",
            ]);

            $this->update(['status' => $to]);
        });
    }
}
