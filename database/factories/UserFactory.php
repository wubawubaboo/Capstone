<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * A verified resident of a new barangay, with password "password".
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone_number' => '09' . fake()->unique()->numerify('#########'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Resident,
            'barangay_id' => Barangay::factory(),
            'address' => fake()->streetAddress(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'start_of_residency' => fake()->numberBetween(1980, (int) date('Y')),
            'is_verified' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /** A user with the given role; admins are citywide, so get no barangay. */
    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role] + ($role->isCitywide() ? ['barangay_id' => null] : []));
    }

    /** A self-registered resident still waiting for ID verification. */
    public function unverified(): static
    {
        return $this->state(fn () => ['is_verified' => false]);
    }
}
