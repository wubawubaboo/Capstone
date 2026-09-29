<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The app used to run in UTC; it now runs in Asia/Manila (config/app.php).
 * Laravel stores timestamps as local wall-clock time without an offset, so
 * every value the server recorded under UTC is moved forward 8 hours to the
 * same moment in Manila time. The Philippines has no daylight saving time,
 * so a fixed offset is exact.
 *
 * Skipped: mediation_schedules.scheduled_date, which staff typed in and so
 * was already Manila wall-clock time, and date-only columns (dates of birth).
 * Laravel's migrations table guarantees this runs once per database.
 */
return new class extends Migration {
    private const HOURS = 8;

    /** table => columns holding times people typed in, which are already Manila time. */
    private const ALREADY_LOCAL = [
        'mediation_schedules' => ['scheduled_date'],
    ];

    public function up(): void {
        $this->shift(self::HOURS);
    }

    public function down(): void {
        $this->shift(-self::HOURS);
    }

    private function shift(int $hours): void {
        foreach ($this->serverRecordedColumns() as $table => $columns) {
            $updates = [];
            foreach ($columns as $column) {
                $updates[$column] = DB::raw($this->addHours($column, $hours));
            }

            // One UPDATE per table; NULLs stay NULL.
            DB::table($table)->update($updates);
        }
    }

    /** @return array<string, list<string>> every timestamp/datetime column except typed-in ones */
    private function serverRecordedColumns(): array {
        $columns = [];

        // Only this app's database: on a shared server getTables() also lists
        // other databases' tables (e.g. phpMyAdmin's).
        foreach (Schema::getTables(Schema::getCurrentSchemaName()) as $table) {
            $name = $table['name'];

            foreach (Schema::getColumns($name) as $column) {
                $type = strtolower($column['type_name']);
                $isDateTime = in_array($type, ['timestamp', 'datetime'], true);

                if ($isDateTime && !in_array($column['name'], self::ALREADY_LOCAL[$name] ?? [], true)) {
                    $columns[$name][] = $column['name'];
                }
            }
        }

        return $columns;
    }

    /** SQL expression for `$column + $hours`, leaving NULLs as NULL. */
    private function addHours(string $column, int $hours): string {
        $quoted = DB::getQueryGrammar()->wrap($column);

        return match (DB::getDriverName()) {
            'sqlite' => "datetime({$quoted}, '" . sprintf('%+d', $hours) . " hours')",
            default => "DATE_ADD({$quoted}, INTERVAL {$hours} HOUR)",
        };
    }
};
