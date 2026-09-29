<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->barangay = Barangay::create(['name' => 'San Nicolas', 'boundary' => [[1, 2], [3, 4]]]);
    }

    public function test_verified_resident_logs_in_to_resident_home(): void
    {
        $resident = $this->makeUser('resident');

        $this->post('/login', ['phone_number' => $resident->phone_number, 'password' => 'password'])
            ->assertRedirect(route('resident.home'));

        $this->assertAuthenticatedAs($resident);
    }

    public function test_unverified_resident_cannot_log_in(): void
    {
        $resident = $this->makeUser('resident', ['is_verified' => false]);

        $this->post('/login', ['phone_number' => $resident->phone_number, 'password' => 'password'])
            ->assertSessionHasErrors(['phone_number' => 'Your account is still pending ID verification by the Secretary.']);

        $this->assertGuest();
    }

    public function test_staff_cannot_use_resident_login(): void
    {
        $secretary = $this->makeUser('secretary');

        $this->post('/login', ['phone_number' => $secretary->phone_number, 'password' => 'password'])
            ->assertSessionHasErrors(['phone_number' => 'Staff members must use the secure staff portal login.']);

        $this->assertGuest();
    }

    public function test_each_staff_role_lands_on_its_home_route(): void
    {
        foreach (['secretary' => 'secretary.analytics', 'vawc' => 'vawc.analytics', 'admin' => 'admin.analytics'] as $role => $home) {
            $user = $this->makeUser($role);

            $this->post('/portal/secure-login', ['phone_number' => $user->phone_number, 'password' => 'password'])
                ->assertRedirect(route($home));

            $this->assertAuthenticatedAs($user);
            $this->post(route("{$role}.logout"))->assertRedirect(route('staff.login'));
            $this->assertGuest();
        }
    }

    public function test_resident_cannot_use_staff_login(): void
    {
        $resident = $this->makeUser('resident');

        $this->post('/portal/secure-login', ['phone_number' => $resident->phone_number, 'password' => 'password'])
            ->assertSessionHasErrors(['phone_number' => 'Residents must use the public login page.']);

        $this->assertGuest();
    }

    public function test_police_account_is_refused_portal_access(): void
    {
        $police = $this->makeUser('barangay_police');

        $this->post('/portal/secure-login', ['phone_number' => $police->phone_number, 'password' => 'password'])
            ->assertSessionHasErrors('phone_number');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $resident = $this->makeUser('resident');
        $attempt = fn (string $password) => $this->post('/login', ['phone_number' => $resident->phone_number, 'password' => $password]);

        for ($i = 0; $i < 5; $i++) {
            $attempt('wrong')->assertSessionHasErrors(['phone_number' => 'The provided phone number or password does not match our records.']);
        }

        $attempt('password');
        $this->assertGuest();
        $this->assertStringStartsWith('Too many login attempts.', session('errors')->first('phone_number'));
    }

    public function test_signed_in_users_are_sent_home_from_guest_pages(): void
    {
        $this->actingAs($this->makeUser('secretary'))
            ->get('/login')
            ->assertRedirect(route('secretary.analytics'));
    }

    public function test_guests_are_sent_to_the_matching_login_page(): void
    {
        $this->get('/secretary/analytics')->assertRedirect(route('staff.login'));
        $this->get('/vawc/analytics')->assertRedirect(route('staff.login'));
        $this->get('/admin/analytics')->assertRedirect(route('staff.login'));
        $this->get('/resident/home')->assertRedirect(route('login'));
    }

    public function test_registration_creates_unverified_resident_and_flashes_success(): void
    {
        Storage::fake();

        $this->post('/register', [
            'name' => 'New Resident',
            'phone_number' => '09123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'barangay_id' => $this->barangay->id,
            'address' => 'Purok 1',
            'date_of_birth' => '1995-01-01',
            'start_of_residency' => 2010,
            'id_photo' => UploadedFile::fake()->image('id.jpg'),
            'selfie_id_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $user = User::where('phone_number', '09123456789')->firstOrFail();
        $this->assertSame(Role::Resident, $user->role);
        $this->assertFalse((bool) $user->is_verified);
        $this->assertTrue(Hash::check('secret123', $user->password));
        Storage::assertExists([$user->id_photo_path, $user->selfie_id_path]);
    }

    public function test_registration_page_only_sends_barangay_id_and_name(): void
    {
        $this->get('/register')->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Registration')
            ->has('barangays', 1, fn (Assert $barangay) => $barangay->hasAll(['id', 'name'])->missing('boundary')));
    }

    public function test_resident_logout_goes_to_landing_page(): void
    {
        $this->actingAs($this->makeUser('resident'))
            ->post(route('resident.logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    public function test_secretary_deleting_resident_removes_account_and_id_photos(): void
    {
        Storage::fake();
        Storage::put('id_photos/a.jpg', 'x');
        Storage::put('id_photos/selfies/a.jpg', 'x');

        $resident = $this->makeUser('resident', [
            'id_photo_path' => 'id_photos/a.jpg',
            'selfie_id_path' => 'id_photos/selfies/a.jpg',
        ]);

        $this->actingAs($this->makeUser('secretary'))
            ->delete(route('secretary.resident.destroy', $resident))
            ->assertSessionHas('success', 'Resident account deleted.');

        $this->assertModelMissing($resident);
        Storage::assertMissing(['id_photos/a.jpg', 'id_photos/selfies/a.jpg']);
    }

    public function test_secretary_cannot_edit_resident_of_another_barangay(): void
    {
        $other = Barangay::create(['name' => 'Elsewhere']);
        $resident = $this->makeUser('resident', ['barangay_id' => $other->id]);

        $this->actingAs($this->makeUser('secretary'))
            ->put(route('secretary.resident.update', $resident), [
                'full_name' => 'Changed',
                'phone_number' => $resident->phone_number,
                'address' => 'x',
                'date_of_birth' => '1990-01-01',
                'start_of_residency' => 2000,
            ])->assertNotFound();
    }
}
