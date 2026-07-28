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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->text('purpose');
            $table->string('status'); // 'Pending', 'Paid', 'Ready', 'Claimed'
            $table->string('reference_no')->unique();
            $table->timestamps(); // Provides created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
