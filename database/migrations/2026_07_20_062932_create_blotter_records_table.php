<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Report;
use App\Models\Barangay;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blotter_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Barangay::class)->constrained()->cascadeOnDelete();
            
            // Link to original report (Nullable in case it's a walk-in)
            $table->foreignIdFor(Report::class)->nullable()->constrained()->nullOnDelete();
            
            // Complainant Details (Stored permanently)
            $table->foreignId('complainant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('complainant_name')->nullable();
            
            // Incident Details (Stored permanently)
            $table->string('incident_type');
            $table->text('incident_description')->nullable();

            // Respondent / Accused Details
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receiver_name')->nullable();
            
            // Case Tracking
            $table->string('case_number')->unique();
            $table->string('status')->default('Pending');
            $table->timestamp('official_entry_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('blotter_records');
        Schema::enableForeignKeyConstraints();
    }
};