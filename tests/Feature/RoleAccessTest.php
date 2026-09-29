<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Barangay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    public function test_role_is_cast_to_the_enum(): void
    {
        $this->assertSame(Role::Vawc, $this->makeUser('vawc')->fresh()->role);
    }

    public function test_each_area_only_admits_its_own_role(): void
    {
        $areas = [
            'resident' => '/resident/home',
            'secretary' => '/secretary/analytics',
            'vawc' => '/vawc/analytics',
            'admin' => '/admin/analytics',
        ];

        foreach (array_keys($areas) as $role) {
            $user = $this->makeUser($role, $role === 'admin' ? ['barangay_id' => null] : []);

            foreach ($areas as $area => $url) {
                $response = $this->actingAs($user)->get($url);

                $area === $role ? $response->assertOk() : $response->assertForbidden();
            }
        }
    }

    public function test_admin_can_only_create_staff_roles(): void
    {
        $admin = $this->makeUser('admin', ['barangay_id' => null]);

        foreach (['resident', 'barangay_police', 'superuser'] as $role) {
            $this->actingAs($admin)->post(route('admin.accounts.store'), [
                'full_name' => 'X',
                'phone_number' => '0912000000' . strlen($role),
                'role' => $role,
                'barangay_id' => $this->barangay->id,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'address' => 'x',
                'date_of_birth' => '1990-01-01',
                'start_of_residency' => 2000,
            ])->assertSessionHasErrors('role');
        }
    }

    public function test_admin_accounts_page_shows_readable_role_names(): void
    {
        $this->makeUser('vawc');

        $this->actingAs($this->makeUser('admin', ['barangay_id' => null]))
            ->get(route('admin.accounts'))
            ->assertInertia(fn (Assert $page) => $page->where('staffAccounts.data.1.role_label', 'VAWC Officer'));
    }

    public function test_frontend_receives_the_role_as_a_plain_string(): void
    {
        $this->actingAs($this->makeUser('secretary'))
            ->get('/secretary/analytics')
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.role', 'secretary'));
    }

    public function test_old_vawc_officer_role_is_renamed(): void
    {
        $user = $this->makeUser('vawc');
        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['role' => 'vawc_officer']);

        (require database_path('migrations/2026_09_30_120000_rename_vawc_officer_role.php'))->up();

        $this->assertSame(Role::Vawc, $user->fresh()->role);
    }
}
