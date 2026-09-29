<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One counter row per barangay, case-number prefix (BLT / VAWC) and year,
 * holding the last number issued. BlotterRecord::generateCaseNumber() locks
 * the row and increments it, so numbers are never reused, even after a case
 * is deleted, and the two desks' numbering doesn't reveal each other's volume.
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('case_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->string('prefix', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['barangay_id', 'prefix', 'year']);
        });

        $this->seedFromExistingCases();
    }

    public function down(): void {
        Schema::dropIfExists('case_number_sequences');
    }

    /** Continues each sequence from the highest number already issued (not the count). */
    private function seedFromExistingCases(): void {
        $highest = [];

        foreach (DB::table('blotter_records')->select('barangay_id', 'case_number')->cursor() as $case) {
            if (!preg_match('/^([A-Z]+)-(\d{4})-(\d+)$/', $case->case_number, $parts)) {
                continue;
            }

            $key = "{$case->barangay_id}|{$parts[1]}|{$parts[2]}";
            $highest[$key] = max($highest[$key] ?? 0, (int) $parts[3]);
        }

        foreach ($highest as $key => $lastNumber) {
            [$barangayId, $prefix, $year] = explode('|', $key);

            DB::table('case_number_sequences')->insert([
                'barangay_id' => $barangayId,
                'prefix' => $prefix,
                'year' => $year,
                'last_number' => $lastNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
