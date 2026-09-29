<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit entries for citywide actions (admin accounts, failed logins for
 * unknown numbers) have no barangay, and deleting a barangay must not erase
 * its audit trail.
 */
return new class extends Migration {
    public function up(): void {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropForeign(['barangay_id']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('barangay_id')->nullable()->change();
            $table->foreign('barangay_id')->references('id')->on('barangays')->nullOnDelete();
        });
    }

    public function down(): void {
        // The column stays nullable: rows written since up() may have no
        // barangay, and deleting audit entries to roll back isn't acceptable.
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropForeign(['barangay_id']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->foreign('barangay_id')->references('id')->on('barangays')->cascadeOnDelete();
        });
    }
};
