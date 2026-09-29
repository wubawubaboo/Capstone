<?php

namespace App\Console\Commands;

use App\Services\SecureFileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off conversion of sensitive uploads stored before SecureFileStore
 * existed. Each plain file is encrypted to "<path>.enc", the database column
 * is repointed, and only then is the plain file deleted. Safe to re-run:
 * already-encrypted paths are skipped.
 */
class EncryptSensitiveFiles extends Command
{
    /**
     * php artisan files:encrypt-sensitive --dry-run
     * php artisan files:encrypt-sensitive
     */
    protected $signature = 'files:encrypt-sensitive
        {--dry-run : List the files that would be encrypted without changing anything}';

    protected $description = 'Encrypt resident ID photos, selfies and report attachments that were stored before encryption at rest';

    /** table => path columns */
    private const COLUMNS = [
        'users' => ['id_photo_path', 'selfie_id_path'],
        'reports' => ['attachment_path'],
    ];

    public function handle(SecureFileStore $secureFiles): int
    {
        $encrypted = 0;
        $missing = 0;

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $rows = DB::table($table)->whereNotNull($column)->where($column, 'not like', '%.enc')->get(['id', $column]);

                foreach ($rows as $row) {
                    $path = $row->{$column};

                    if (!Storage::exists($path)) {
                        $this->warn("Missing file for {$table}#{$row->id}.{$column}: {$path}");
                        $missing++;
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("Would encrypt {$path}");
                        $encrypted++;
                        continue;
                    }

                    $encryptedPath = $secureFiles->encryptExisting($path);
                    DB::table($table)->where('id', $row->id)->update([$column => $encryptedPath]);
                    Storage::delete($path);
                    $encrypted++;
                }
            }
        }

        $verb = $this->option('dry-run') ? 'would be encrypted' : 'encrypted';
        $this->info("{$encrypted} file(s) {$verb}, {$missing} missing.");

        return self::SUCCESS;
    }
}
