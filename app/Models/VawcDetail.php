<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VawcDetail extends Model
{
    /** @use HasFactory<\Database\Factories\VawcDetailFactory> */
    use HasFactory;

    protected $guarded = [];

    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_record_id'); }
    public function officer() { return $this->belongsTo(User::class, 'officer_in_charge_id'); }

    public function isSettled()
    {
        return !is_null($this->settlement_record_path);
    }
}
