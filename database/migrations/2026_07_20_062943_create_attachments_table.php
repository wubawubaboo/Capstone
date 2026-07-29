<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Report;
use App\Models\BlotterRecord;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_type')->nullable();
            
            // Fix: Added cascadeOnDelete to parent relations
            $table->foreignIdFor(Report::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(BlotterRecord::class)->nullable()->constrained()->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};