<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Barangay;
use App\Models\BarangayAsset;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo data: one barangay, one account per role, a few document types
 * and assets. Safe to re-run (existing rows are left alone). Refuses to run
 * in production, since every demo account has the password "password".
 */
class DatabaseSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Refusing to seed demo accounts with a known password in production.');

            return;
        }

        $barangay = Barangay::firstOrCreate(['name' => 'San Nicolas'], ['contact_number' => '09170000000']);

        $accounts = [
            ['09170000001', 'Demo Admin', Role::Admin],
            ['09170000002', 'Demo Secretary', Role::Secretary],
            ['09170000003', 'Demo VAWC Officer', Role::Vawc],
            ['09170000004', 'Demo Resident', Role::Resident],
            ['09170000005', 'Demo Barangay Police', Role::BarangayPolice],
        ];

        foreach ($accounts as [$phone, $name, $role]) {
            User::firstOrCreate(['phone_number' => $phone], [
                'full_name' => $name,
                'password' => self::PASSWORD,
                'role' => $role,
                'barangay_id' => $role->isCitywide() ? null : $barangay->id,
                'address' => 'Barangay Hall, San Nicolas',
                'date_of_birth' => '1990-01-01',
                'start_of_residency' => 2010,
                'is_verified' => true,
            ]);
        }

        foreach (['Barangay Clearance' => 50, 'Certificate of Residency' => null, 'Certificate of Indigency' => null] as $name => $fee) {
            DocumentType::firstOrCreate(['name' => $name], ['base_fee' => $fee, 'is_active' => true]);
        }

        foreach (['Patrol Vehicle' => 'Vehicle', 'Ambulance' => 'Vehicle', 'Event Tent' => 'Equipment', 'Sound System' => 'Equipment'] as $name => $type) {
            BarangayAsset::firstOrCreate(
                ['barangay_id' => $barangay->id, 'asset_name' => $name],
                ['asset_type' => $type, 'is_available' => true, 'is_archived' => false]
            );
        }

        $this->command->table(
            ['Role', 'Phone number', 'Password'],
            array_map(fn ($account) => [$account[2]->label(), $account[0], self::PASSWORD], $accounts)
        );
        $this->command->warn('The staff accounts sign in at /portal/secure-login; the resident at /login. The police account has no portal access.');
    }
}
