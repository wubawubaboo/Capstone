<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->json('boundary')->nullable()->after('contact_number');
            $table->timestamp('boundary_fetched_at')->nullable()->after('boundary');
        });
    }

    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn(['boundary', 'boundary_fetched_at']);
        });
    }
};
