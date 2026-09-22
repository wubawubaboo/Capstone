<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->index('status');
            $table->index('incident_type');
            $table->index('created_at');
        });

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->index('status');
            $table->index('official_entry_date');
            $table->index(['barangay_id', 'status']);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index(['barangay_id', 'status']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index(['barangay_id', 'status']);
        });

        Schema::table('mediation_schedules', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('is_verified');
            $table->index(['barangay_id', 'role']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['barangay_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['incident_type']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['official_entry_date']);
            $table->dropIndex(['barangay_id', 'status']);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['barangay_id', 'status']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['barangay_id', 'status']);
        });

        Schema::table('mediation_schedules', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['barangay_id', 'role']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['barangay_id', 'created_at']);
        });
    }
};
