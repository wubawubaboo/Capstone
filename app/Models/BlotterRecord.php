<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlotterRecord extends Model
{
    /** @use HasFactory<\Database\Factories\BlotterRecordFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $casts = ['official_entry_date' => 'datetime'];

    public function report() { return $this->belongsTo(Report::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function vawcDetail() { return $this->hasOne(VawcDetail::class, 'blotter_id'); }
    public function mediations() { return $this->hasMany(MediationSchedule::class, 'blotter_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'blotter_id'); }

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
}
