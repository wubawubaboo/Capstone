<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BlotterRecord;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vawc_details', function (Blueprint $table) {
            $table->id();
            
            // Fix: Added cascadeOnDelete
            $table->foreignIdFor(BlotterRecord::class)->constrained()->cascadeOnDelete();
            
            $table->foreignId('officer_in_charge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('confidential_notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vawc_details');
    }
};