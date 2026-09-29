<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VAWC accounts created by the admin screen between 2026-09-06 and 2026-09-22
 * were saved with the old role name 'vawc_officer'. users.role is now cast to
 * App\Enums\Role, which only knows 'vawc', so such a row would fail to load.
 */
return new class extends Migration {
    public function up(): void {
        DB::table('users')->where('role', 'vawc_officer')->update(['role' => 'vawc']);
    }

    public function down(): void {
        // Not reversible: renamed rows can't be told apart from genuine 'vawc' accounts.
    }
};
