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
        Schema::create('mediation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blotter_id')->constrained('blotter_records')->cascadeOnDelete();
            $table->integer('meeting_number');
            $table->timestamp('scheduled_date');
            $table->string('status');
            $table->string('summons_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediation_schedules');
    }
};
