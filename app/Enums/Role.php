<?php

namespace App\Enums;

/**
 * Every account role. users.role is cast to this enum, so compare against
 * cases (`$user->role === Role::Secretary`), never raw strings.
 */
enum Role: string
{
    case Resident = 'resident';
    case Secretary = 'secretary';
    case Vawc = 'vawc';
    case Admin = 'admin';
    /** Receives SOS alerts by SMS only; has no portal access. */
    case BarangayPolice = 'barangay_police';

    /** Staff roles: they sign in through the staff portal and are managed by the citywide admin. */
    public static function staff(): array
    {
        return [self::Secretary, self::Vawc, self::Admin];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }

    /** Admins are citywide; every other role belongs to a barangay. */
    public function isCitywide(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Name of the route this role lands on after login. This is the single
     * source of truth for role landing pages. Null means the role has no portal.
     */
    public function homeRoute(): ?string
    {
        return match ($this) {
            self::Resident => 'resident.home',
            self::Secretary => 'secretary.analytics',
            self::Vawc => 'vawc.analytics',
            self::Admin => 'admin.analytics',
            self::BarangayPolice => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Resident => 'Resident',
            self::Secretary => 'Secretary',
            self::Vawc => 'VAWC Officer',
            self::Admin => 'Admin',
            self::BarangayPolice => 'Barangay Police',
        };
    }
}
