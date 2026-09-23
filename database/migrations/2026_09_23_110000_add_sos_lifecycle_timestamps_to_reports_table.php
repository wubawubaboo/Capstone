<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('reports', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('acknowledged_at');
            $table->timestamp('resolved_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['acknowledged_at', 'responded_at', 'resolved_at']);
        });
    }
};
