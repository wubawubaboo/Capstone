<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The reports table defaulted to 'Pending', but App\Enums\ReportStatus uses
 * 'pending'. A report inserted without an explicit status would fail to load.
 */
return new class extends Migration {
    public function up(): void {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        DB::table('reports')->where('status', 'Pending')->update(['status' => 'pending']);
    }

    public function down(): void {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status')->default('Pending')->change();
        });
    }
};
