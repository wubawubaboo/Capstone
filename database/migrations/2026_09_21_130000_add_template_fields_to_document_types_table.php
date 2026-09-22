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
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('template_type')->nullable()->after('base_fee');
            $table->string('template_path')->nullable()->after('template_type');
            $table->json('field_positions_json')->nullable()->after('template_path');
            $table->unsignedInteger('template_image_width')->nullable()->after('field_positions_json');
            $table->unsignedInteger('template_image_height')->nullable()->after('template_image_width');
            $table->boolean('is_active')->default(true)->after('template_image_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn([
                'template_type',
                'template_path',
                'field_positions_json',
                'template_image_width',
                'template_image_height',
                'is_active',
            ]);
        });
    }
};
