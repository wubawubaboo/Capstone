<?php

namespace App\Enums;


enum Role: string
{
    case Resident = 'resident';
    case Secretary = 'secretary';
    case Vawc = 'vawc';
    case Admin = 'admin';
    case BarangayPolice = 'barangay_police';

    public static function staff(): array
    {
        return [self::Secretary, self::Vawc, self::Admin];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }

    public function isCitywide(): bool
    {
        return $this === self::Admin;
    }

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
