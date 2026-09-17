<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateRootAdmin extends Command
{
    /**
     * php artisan admin:create-root
     * php artisan admin:create-root --phone=09171234567 --password=changeme123
     */
    protected $signature = 'admin:create-root
        {--name=admin : Full name for the root admin account}
        {--phone=09000000000 : Phone number used to log in at the staff portal (must be unique)}
        {--password=admin : Login password}';

    protected $description = 'Create (or reset) the root admin account used to set up the system';

    public function handle(): int
    {
        $name = $this->option('name');
        $phone = $this->option('phone');
        $password = $this->option('password');

        $admin = User::where('phone_number', $phone)->first();

        $attributes = [
            'full_name' => $name,
            'phone_number' => $phone,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_verified' => true,
        ];

        if ($admin) {
            $admin->update($attributes);
            $this->info("Existing root admin account ({$phone}) was updated.");
        } else {
            User::create($attributes);
            $this->info('Root admin account created.');
        }

        $this->warn("Log in at the staff portal with phone number [{$phone}] and password [{$password}].");
        $this->warn('Change this password after first login.');

        return self::SUCCESS;
    }
}
