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
        Schema::create('vawc_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blotter_id')->constrained('blotter_records')->cascadeOnDelete();
            $table->foreignId('officer_in_charge_id')->constrained('users')->cascadeOnDelete();
            $table->text('confidential_notes')->nullable();
            $table->string('settlement_record_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vawc_details');
    }
};
