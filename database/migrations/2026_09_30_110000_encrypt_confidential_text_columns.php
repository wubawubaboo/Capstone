<?php
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypts free-text columns that can hold confidential incident details,
 * VAWC narratives in particular. The matching models cast these columns as
 * `encrypted`, using APP_KEY. Losing APP_KEY makes this data unreadable, so it
 * must be backed up together with the database.
 *
 * Safe to re-run: values that already decrypt are left alone.
 */
return new class extends Migration {
    /** table => column. vawc_details.confidential_notes was always encrypted by its cast; it's included only to catch stray plain-text rows. */
    private const COLUMNS = [
        'reports' => 'description',
        'blotter_records' => 'incident_description',
        'mediation_schedules' => 'notes',
        'vawc_details' => 'confidential_notes',
    ];

    public function up(): void {
        foreach (self::COLUMNS as $table => $column) {
            $this->transform($table, $column, fn (string $value) => $this->isEncrypted($value) ? null : Crypt::encryptString($value));
        }
    }

    public function down(): void {
        foreach (self::COLUMNS as $table => $column) {
            if ($table === 'vawc_details') {
                continue; // Encrypted before this migration existed.
            }

            $this->transform($table, $column, fn (string $value) => $this->isEncrypted($value) ? Crypt::decryptString($value) : null);
        }
    }

    /** Applies $convert to every non-null value; a null result means "leave unchanged". */
    private function transform(string $table, string $column, callable $convert): void {
        DB::table($table)->whereNotNull($column)->select('id', $column)->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column, $convert) {
                foreach ($rows as $row) {
                    $converted = $convert($row->{$column});

                    if ($converted !== null) {
                        DB::table($table)->where('id', $row->id)->update([$column => $converted]);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
