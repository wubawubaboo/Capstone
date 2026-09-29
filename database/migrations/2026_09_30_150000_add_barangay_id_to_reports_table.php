<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records the barangay a report was filed in, so barangay-scoped report
 * queries no longer join through users, and a report stays with the barangay
 * it concerns even if the resident later moves. Filled by Report::booted()
 * on create; existing rows take the reporter's current barangay.
 */
return new class extends Migration {
    public function up(): void {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('barangay_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        DB::table('reports')->update([
            'barangay_id' => DB::raw('(select users.barangay_id from users where users.id = reports.user_id)'),
        ]);

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['barangay_id', 'is_vawc', 'created_at']);
            $table->index(['barangay_id', 'created_at']);
        });

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->index(['barangay_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropIndex(['barangay_id', 'created_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['barangay_id', 'is_vawc', 'created_at']);
            $table->dropIndex(['barangay_id', 'created_at']);
            $table->dropConstrainedForeignId('barangay_id');
        });
    }
};
