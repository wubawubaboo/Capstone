<?php
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts the free-typed names of the parties to a case (an unregistered
 * complainant, and a respondent recorded by name). On VAWC cases these
 * identify the victim and the alleged abuser. Encrypted values don't fit in
 * varchar(255), so the columns become text first.
 *
 * Registered parties are linked by complainant_id/receiver_id instead, and
 * their names live in users.full_name, which stays searchable.
 *
 * Safe to re-run: values that already decrypt are left alone.
 */
return new class extends Migration {
    private const COLUMNS = ['complainant_name', 'receiver_name'];

    public function up(): void {
        Schema::table('blotter_records', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->text($column)->nullable()->change();
            }
        });

        $this->transform(fn (string $value) => $this->isEncrypted($value) ? null : Crypt::encryptString($value));
    }

    public function down(): void {
        $this->transform(fn (string $value) => $this->isEncrypted($value) ? Crypt::decryptString($value) : null);

        Schema::table('blotter_records', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->string($column)->nullable()->change();
            }
        });
    }

    /** Applies $convert to every non-null name; a null result means "leave unchanged". */
    private function transform(callable $convert): void {
        DB::table('blotter_records')->select(['id', ...self::COLUMNS])->orderBy('id')
            ->chunkById(200, function ($rows) use ($convert) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach (self::COLUMNS as $column) {
                        if ($row->{$column} !== null && ($converted = $convert($row->{$column})) !== null) {
                            $updates[$column] = $converted;
                        }
                    }

                    if ($updates) {
                        DB::table('blotter_records')->where('id', $row->id)->update($updates);
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
