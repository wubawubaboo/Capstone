<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * UserFactory is out of date (it sets name/email, which don't exist), so
 * tests create users directly. Using classes must set $this->barangay.
 */
trait CreatesUsers
{
    private static int $nextPhone = 9000000000;

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'full_name' => ucfirst($role) . ' User',
            'phone_number' => '0' . self::$nextPhone++,
            'password' => 'password',
            'role' => $role,
            'barangay_id' => $this->barangay->id,
            'address' => 'Somewhere',
            'date_of_birth' => '1990-05-01',
            'start_of_residency' => 2000,
            'is_verified' => true,
        ], $overrides));
    }
}
