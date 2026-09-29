<?php

namespace App\Models;

use App\Enums\BlotterStatus;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BlotterRecord extends Model
{
    /** @use HasFactory<\Database\Factories\BlotterRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'barangay_id',
        'report_id',
        'complainant_id',
        'complainant_name',
        'incident_type',
        'incident_description',
        'receiver_id',
        'receiver_name',
        'case_number',
        'status',
        'official_entry_date',
    ];

    protected $casts = [
        'official_entry_date' => 'datetime',
        'status' => BlotterStatus::class,
        'incident_description' => 'encrypted',
        'complainant_name' => 'encrypted',
        'receiver_name' => 'encrypted',
    ];

    public function report() { return $this->belongsTo(Report::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function complainant() { return $this->belongsTo(User::class, 'complainant_id'); }
    public function vawcDetail() { return $this->hasOne(VawcDetail::class, 'blotter_record_id'); }
    public function mediations() { return $this->hasMany(MediationSchedule::class, 'blotter_record_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'blotter_record_id'); }
    public function statusHistory() { return $this->hasMany(CaseStatusHistory::class)->latest('id'); }

    // Scopes
    public function scopeForBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }
    public function scopeResolved($query) { return $query->where('status', BlotterStatus::Resolved); }
    public function scopeEscalatedToCourt($query) { return $query->where('status', BlotterStatus::EscalatedToCourt); }

    // Utility
    public function isVawcCase()
    {
        return $this->vawcDetail()->exists();
    }

    /**
     * Issues the next case number for a barangay, prefix (BLT / VAWC) and
     * year from its row in case_number_sequences. Must be called inside the
     * DB::transaction() that creates the case: the row lock makes concurrent
     * filings wait their turn, and a rolled-back filing gives its number back.
     */
    public static function generateCaseNumber(string $prefix, int $barangayId): string
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('generateCaseNumber() must run inside the transaction that creates the case.');
        }

        $year = now()->year;
        $key = ['barangay_id' => $barangayId, 'prefix' => $prefix, 'year' => $year];

        // First case of the year: create the counter. insertOrIgnore lets two
        // simultaneous first filings race safely against the unique index.
        DB::table('case_number_sequences')->insertOrIgnore($key + ['last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);

        $sequence = DB::table('case_number_sequences')->where($key)->lockForUpdate()->first();
        $next = $sequence->last_number + 1;

        DB::table('case_number_sequences')->where('id', $sequence->id)->update(['last_number' => $next, 'updated_at' => now()]);

        return sprintf('%s-%s-%04d', $prefix, $year, $next);
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
            throw new InvalidStatusTransitionException("Case #{$this->case_number} cannot move from \"{$from->value}\" to \"{$to->value}\". Reopen the case first if it is closed.");
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
            throw new InvalidStatusTransitionException("Case #{$this->case_number} is not closed, so it cannot be reopened.");
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
