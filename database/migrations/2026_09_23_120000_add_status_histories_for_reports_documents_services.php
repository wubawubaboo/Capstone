<?php

use App\Models\DocumentRequest;
use App\Models\Report;
use App\Models\ServiceRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize pre-existing status casing before these columns get a
        // backed-enum cast — any stored value that doesn't match one of the
        // enum's cases would otherwise throw when the row is loaded.
        DB::table('reports')->update(['status' => DB::raw('LOWER(status)')]);

        DB::table('document_requests')->whereIn('status', ['pending', 'Paid', 'paid'])->update(['status' => 'Pending']);
        DB::table('document_requests')->whereIn('status', ['ready', 'ready_for_pickup'])->update(['status' => 'Ready']);
        DB::table('document_requests')->where('status', 'claimed')->update(['status' => 'Claimed']);

        DB::table('service_requests')->where('status', 'pending')->update(['status' => 'Pending']);
        DB::table('service_requests')->whereIn('status', ['in_progress', 'in progress', 'inprogress'])->update(['status' => 'In Progress']);
        DB::table('service_requests')->where('status', 'completed')->update(['status' => 'Completed']);

        Schema::create('report_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Report::class)->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('document_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DocumentRequest::class)->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('service_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ServiceRequest::class)->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_status_histories');
        Schema::dropIfExists('document_request_status_histories');
        Schema::dropIfExists('report_status_histories');
    }
};
