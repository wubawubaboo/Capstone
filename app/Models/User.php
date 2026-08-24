<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone_number',
        'password',
        'role',
        'barangay_id',
        'id_photo_path',
        'selfie_id_path',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

protected $guarded = [];

    // Relationships
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function reports() { return $this->hasMany(Report::class, 'reporter_id'); }
    public function documentRequests() { return $this->hasMany(DocumentRequest::class, 'requester_id'); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class, 'requester_id'); }
    public function receivedBlotters() { return $this->hasMany(BlotterRecord::class, 'receiver_id'); }
    public function handledVawcCases() { return $this->hasMany(VawcDetail::class, 'officer_in_charge_id'); }
    public function systemLogs() { return $this->hasMany(SystemLog::class, 'actor_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'uploaded_by'); }

    // Role Checks
    public function isRole($role) { return $this->role === $role; }
    public function isCityAdmin() { return $this->role === 'city_admin'; }
    public function isSecretary() { return $this->role === 'secretary'; }
    public function isResident() { return $this->role === 'resident'; }

    // Query Scopes
    public function scopeByBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }
    public function scopeSecretaries($query) { return $query->where('role', 'secretary'); }
}
