<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BlotterRecord;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediation_schedules', function (Blueprint $table) {
            $table->id();
            
            // Fix: Added cascadeOnDelete
            $table->foreignIdFor(BlotterRecord::class)->constrained()->cascadeOnDelete();
            
            $table->integer('meeting_number');
            $table->dateTime('scheduled_date');
            $table->string('status')->default('Scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediation_schedules');
    }
};