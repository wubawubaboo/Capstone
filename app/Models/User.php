<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
        'address',     
        'date_of_birth',     
        'start_of_residency', 
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
            'password' => 'hashed',
            'role' => Role::class,
            // Sent to pages as a plain "YYYY-MM-DD" date, not a midnight timestamp
            // that the viewer's timezone could shift to the previous day.
            'date_of_birth' => 'date:Y-m-d',
            'start_of_residency' => 'integer', // Added cast
        ];
    }

    // Relationships
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function reports() { return $this->hasMany(Report::class, 'user_id'); }
    public function documentRequests() { return $this->hasMany(DocumentRequest::class, 'requester_id'); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class, 'requester_id'); }
    public function receivedBlotters() { return $this->hasMany(BlotterRecord::class, 'receiver_id'); }
    public function handledVawcCases() { return $this->hasMany(VawcDetail::class, 'officer_in_charge_id'); }
    public function systemLogs() { return $this->hasMany(SystemLog::class, 'actor_id'); }

    // Computed
    public function getAge(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getYearsOfResidency(): ?int
    {
        return $this->start_of_residency ? now()->year - $this->start_of_residency : null;
    }

    // Role Checks
    public function isResident(): bool { return $this->role === Role::Resident; }
    public function isPendingVerification(): bool { return $this->isResident() && !$this->is_verified; }
    public function usesStaffPortal(): bool { return $this->role?->isStaff() ?? false; }

    /** See Role::homeRoute(), the single source of truth for landing pages. */
    public function homeRoute(): ?string
    {
        return $this->role?->homeRoute();
    }

    /**
     * Removes the resident's ID and selfie uploads. Call this only after the
     * account row has been deleted: file deletion isn't transactional, so a
     * failure should leave orphaned files rather than a user with no evidence.
     */
    public function deleteVerificationDocuments(): void
    {
        foreach ([$this->id_photo_path, $this->selfie_id_path] as $path) {
            if ($path) {
                Storage::delete($path);
            }
        }
    }

    // Query Scopes
    public function scopeByBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }

    /**
     * Admin accounts are citywide (no barangay); every other staff role
     * belongs to the barangay it was assigned.
     */
    public static function barangayIdForRole(Role $role, ?int $requestedBarangayId): ?int
    {
        return $role->isCitywide() ? null : $requestedBarangayId;
    }
}