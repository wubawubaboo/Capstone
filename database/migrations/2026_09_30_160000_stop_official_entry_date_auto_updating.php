<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * On MySQL/MariaDB with explicit_defaults_for_timestamp off (the XAMPP
 * default), the first NOT NULL `timestamp` column of a table silently gets
 * ON UPDATE CURRENT_TIMESTAMP. That made blotter_records.official_entry_date
 * reset to "now" on every case update, including each status change.
 *
 * A `datetime` column never auto-updates. The corrupted values are restored
 * from created_at: both are set to the same moment when a case is filed
 * (FileBlotterCase / FileVawcCase).
 */
return new class extends Migration {
    public function up(): void {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dateTime('official_entry_date')->nullable()->change();
        });

        DB::table('blotter_records')->whereNotNull('created_at')->update(['official_entry_date' => DB::raw('created_at')]);
    }

    public function down(): void {
        // Only the column type is reverted; the restored dates are correct either way.
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->timestamp('official_entry_date')->useCurrent()->change();
        });
    }
};
