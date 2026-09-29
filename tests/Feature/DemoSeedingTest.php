<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BarangayAsset;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_account_per_role_and_can_be_rerun(): void
    {
        $this->seed();
        $this->seed();

        foreach (Role::cases() as $role) {
            $this->assertSame(1, User::where('role', $role)->count(), "Expected one {$role->value} account.");
        }
        $this->assertNull(User::where('role', Role::Admin)->value('barangay_id'));
        $this->assertSame(3, DocumentType::count());
        $this->assertSame(4, BarangayAsset::count());
    }

    public function test_seeded_accounts_can_sign_in(): void
    {
        $this->withoutVite();
        $this->seed();

        $this->post('/portal/secure-login', ['phone_number' => '09170000002', 'password' => 'password'])
            ->assertRedirect(route('secretary.analytics'));
    }

    public function test_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        // --force skips Laravel's own production prompt, so this checks the seeder's guard.
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
    }

    public function test_user_factory_builds_valid_accounts(): void
    {
        $resident = User::factory()->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $pending = User::factory()->unverified()->create();

        $this->assertSame(Role::Resident, $resident->role);
        $this->assertNotNull($resident->barangay_id);
        $this->assertNull($admin->barangay_id);
        $this->assertFalse((bool) $pending->is_verified);
    }
}
