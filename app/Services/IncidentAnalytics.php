<?php

namespace App\Services;

use App\Enums\BlotterStatus;
use App\Enums\MediationStatus;
use App\Enums\ReportStatus;
use App\Models\Barangay;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Support\AnalyticsPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncidentAnalytics
{
    private const TREND_TYPES = 4;

    private const MAP_MAX_POINTS = 2000;

    private const HOTSPOT_PRECISION = 3;

    private const HOTSPOT_MIN_REPORTS = 3;

    private const TIME_BLOCK_HOURS = 3;

    private const PEAK_MIN_INCIDENTS = 10;

    private const PEAK_MIN_IN_BLOCK = 3;

    /**
     * @param  Builder  $reports
     * @param  Builder  $cases 
     * @param  array{map?: bool, sos?: bool, barangays?: bool}  $sections
     */
    public function dashboard(Builder $reports, Builder $cases, AnalyticsPeriod $period, array $sections = []): array
    {
        $sections += ['map' => false, 'sos' => false, 'barangays' => false];

        $reportRows = (clone $reports)
            ->whereBetween('created_at', $period->fullWindow())
            ->toBase()
            ->get(['id', 'barangay_id', 'incident_type', 'status', 'latitude', 'longitude', 'acknowledged_at', 'created_at'])
            ->map(fn ($row) => tap($row, fn ($row) => $row->created_at = CarbonImmutable::parse($row->created_at)));

        $caseRows = (clone $cases)
            ->whereBetween('official_entry_date', $period->fullWindow())
            ->toBase()
            ->get(['id', 'barangay_id', 'report_id', 'incident_type', 'status', 'official_entry_date'])
            ->map(fn ($row) => tap($row, fn ($row) => $row->official_entry_date = CarbonImmutable::parse($row->official_entry_date)));

        // Cases whose report this viewer can see are already counted through it.
        $linkedReportIds = (clone $reports)
            ->whereIn('id', $caseRows->pluck('report_id')->filter()->unique())
            ->pluck('id')
            ->flip();

        $incidents = $reportRows->map(fn ($row) => [
            'type' => $this->typeLabel($row->incident_type),
            'at' => $row->created_at,
            'barangay_id' => $row->barangay_id,
            'walk_in' => false,
        ])->concat($caseRows->reject(fn ($row) => $row->report_id && $linkedReportIds->has($row->report_id))->map(fn ($row) => [
            'type' => $this->typeLabel($row->incident_type),
            'at' => $row->official_entry_date,
            'barangay_id' => $row->barangay_id,
            'walk_in' => true,
        ]))->values();

        [$current, $previous] = $incidents->partition(fn ($incident) => $period->contains($incident['at']));
        $currentReports = $reportRows->filter(fn ($row) => $period->contains($row->created_at));
        $currentCases = $caseRows->filter(fn ($row) => $period->contains($row->official_entry_date));

        $categories = $this->categories($current, $previous);
        $timeOfWeek = $this->timeOfWeek($current);
        $backlog = $this->backlog($reports);
        $caseOutcomes = $this->caseOutcomes($cases, $currentCases, $period);
        $sos = $sections['sos'] ? $this->sos($currentReports, $reportRows->reject(fn ($row) => $period->contains($row->created_at))) : null;
        $map = $sections['map'] ? $this->map($currentReports) : null;
        $barangays = $sections['barangays'] ? $this->barangays($current, $previous) : null;

        $totals = [
            'current' => $current->count(),
            'previous' => $previous->count(),
            'reports' => $current->where('walk_in', false)->count(),
            'walk_ins' => $current->where('walk_in', true)->count(),
        ];

        return [
            'period' => $period->toArray(),
            'totals' => $totals,
            'sos' => $sos,
            'backlog' => $backlog,
            'trend' => $this->trend($current, $period),
            'categories' => $categories,
            'timeOfWeek' => $timeOfWeek,
            'reportStatuses' => $this->statusCounts($currentReports->pluck('status'), ReportStatus::cases(), fn (ReportStatus $status) => Str::headline($status->value)),
            'caseOutcomes' => $caseOutcomes,
            'map' => $map,
            'barangays' => $barangays,
            'insights' => $this->insights($period, $totals, $categories, $timeOfWeek, $backlog, $caseOutcomes, $sos, $map, $barangays),
        ];
    }

    private function typeLabel(?string $type): string
    {
        if ($type === Report::SOS_TYPE) {
            return 'SOS emergency';
        }

        return Str::ucfirst(trim((string) $type)) ?: 'Unspecified';
    }

    /** Incidents per bucket, one series per leading type plus "Other". */
    private function trend(Collection $current, AnalyticsPeriod $period): array
    {
        $buckets = $period->buckets();
        $leading = $current->countBy('type')->sortDesc()->keys()->take(self::TREND_TYPES);

        $series = $leading->map(fn ($type) => [
            'name' => $type,
            'data' => $this->perBucket($current->where('type', $type), $buckets, $period),
        ]);

        $rest = $current->whereNotIn('type', $leading);
        if ($rest->isNotEmpty()) {
            // Not "Other": residents can file a report under that type.
            $series->push(['name' => 'All other types', 'folded' => true, 'data' => $this->perBucket($rest, $buckets, $period)]);
        }

        return [
            'labels' => array_values($buckets),
            'series' => $series->values(),
        ];
    }

    private function perBucket(Collection $incidents, array $buckets, AnalyticsPeriod $period): array
    {
        $counts = $incidents->countBy(fn ($incident) => $period->bucketKey($incident['at']));

        return array_map(fn ($key) => $counts[$key] ?? 0, array_keys($buckets));
    }

    /** Every incident type seen in either window, with this and the previous period's counts. */
    private function categories(Collection $current, Collection $previous): array
    {
        $now = $current->countBy('type');
        $before = $previous->countBy('type');

        return $now->keys()->merge($before->keys())->unique()
            ->map(fn ($type) => ['name' => $type, 'current' => $now[$type] ?? 0, 'previous' => $before[$type] ?? 0])
            ->sortBy([['current', 'desc'], ['previous', 'desc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    /** Incident counts by weekday (Monday first) and 3-hour block of the day. */
    private function timeOfWeek(Collection $current): array
    {
        $grid = array_fill(0, 7, array_fill(0, 24 / self::TIME_BLOCK_HOURS, 0));

        foreach ($current as $incident) {
            $grid[$incident['at']->dayOfWeekIso - 1][intdiv($incident['at']->hour, self::TIME_BLOCK_HOURS)]++;
        }

        $blocks = array_map(
            fn ($block) => CarbonImmutable::today()->setHour($block * self::TIME_BLOCK_HOURS)->format('g A'),
            array_keys($grid[0])
        );

        return [
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'blocks' => $blocks,
            'blockHours' => self::TIME_BLOCK_HOURS,
            'grid' => $grid,
        ];
    }

    /** Reports still waiting on the desk, whenever they were filed. */
    private function backlog(Builder $reports): array
    {
        $open = (clone $reports)->whereIn('status', [ReportStatus::Pending, ReportStatus::InProgress]);
        $oldest = (clone $open)->min('created_at');

        return [
            'open' => $open->count(),
            'oldestDays' => $oldest ? (int) CarbonImmutable::parse($oldest)->diffInDays(now()) : null,
        ];
    }

    /** SOS alerts in the period and how quickly the desk acknowledged them. */
    private function sos(Collection $currentReports, Collection $previousReports): array
    {
        $alerts = $currentReports->where('incident_type', Report::SOS_TYPE);
        $minutes = $alerts->whereNotNull('acknowledged_at')
            ->map(fn ($alert) => $alert->created_at->diffInMinutes(CarbonImmutable::parse($alert->acknowledged_at)));

        return [
            'current' => $alerts->count(),
            'previous' => $previousReports->where('incident_type', Report::SOS_TYPE)->count(),
            'unacknowledged' => $alerts->whereNull('acknowledged_at')->count(),
            'medianAckMinutes' => $minutes->isEmpty() ? null : round($minutes->median(), 1),
        ];
    }

    /** What happened to the blotter cases recorded in the period. */
    private function caseOutcomes(Builder $cases, Collection $currentCases, AnalyticsPeriod $period): array
    {
        $periodCases = (clone $cases)->whereBetween('official_entry_date', [$period->start, $period->end])->select('id');

        $closedAt = DB::table('case_status_histories')
            ->whereIn('blotter_record_id', $periodCases)
            ->whereIn('to_status', [BlotterStatus::Resolved->value, BlotterStatus::EscalatedToCourt->value])
            ->groupBy('blotter_record_id')
            ->selectRaw('blotter_record_id, min(created_at) as closed_at')
            ->pluck('closed_at', 'blotter_record_id');

        $daysToClose = $currentCases
            ->filter(fn ($case) => isset($closedAt[$case->id]) && BlotterStatus::tryFrom($case->status)?->isTerminal())
            ->map(fn ($case) => $case->official_entry_date->diffInDays(CarbonImmutable::parse($closedAt[$case->id])));

        $sessions = MediationSchedule::whereIn('blotter_record_id', (clone $cases)->select('id'))
            ->whereBetween('scheduled_date', [$period->start, $period->end])
            ->toBase()
            ->pluck('status');

        $settled = $currentCases->where('status', BlotterStatus::Resolved->value)->count();
        $escalated = $currentCases->where('status', BlotterStatus::EscalatedToCourt->value)->count();

        return [
            'filed' => $currentCases->count(),
            'settled' => $settled,
            'escalated' => $escalated,
            'settlementRate' => $settled + $escalated > 0 ? (int) round($settled / ($settled + $escalated) * 100) : null,
            'medianDaysToClose' => $daysToClose->isEmpty() ? null : round($daysToClose->median(), 1),
            'statuses' => $this->statusCounts($currentCases->pluck('status'), BlotterStatus::cases(), fn (BlotterStatus $status) => $status->value),
            'mediation' => $this->statusCounts($sessions, MediationStatus::cases(), fn (MediationStatus $status) => $status->value),
        ];
    }

    private function statusCounts(Collection $values, array $statuses, callable $label): array
    {
        $counts = $values->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value)->countBy();

        return array_map(fn ($status) => [
            'status' => $status->value,
            'label' => $label($status),
            'count' => $counts[$status->value] ?? 0,
        ], $statuses);
    }

    private function map(Collection $currentReports): array
    {
        $located = $currentReports
            ->filter(fn ($row) => $row->latitude !== null && $row->longitude !== null)
            ->sortByDesc('created_at');

        $hotspot = $located
            ->groupBy(fn ($row) => round($row->latitude, self::HOTSPOT_PRECISION).','.round($row->longitude, self::HOTSPOT_PRECISION))
            ->sortByDesc(fn ($cell) => $cell->count())
            ->first();

        return [
            'points' => $located->take(self::MAP_MAX_POINTS)->map(fn ($row) => [
                (float) $row->latitude,
                (float) $row->longitude,
                $row->incident_type === Report::SOS_TYPE ? 2 : 1,
            ])->values(),
            'located' => $located->count(),
            'hotspot' => $hotspot && $hotspot->count() >= self::HOTSPOT_MIN_REPORTS ? [
                'lat' => round($hotspot->avg('latitude'), 6),
                'lng' => round($hotspot->avg('longitude'), 6),
                'count' => $hotspot->count(),
            ] : null,
        ];
    }

    private function barangays(Collection $current, Collection $previous): array
    {
        $now = $current->countBy('barangay_id');
        $before = $previous->countBy('barangay_id');

        return Barangay::orderBy('name')->pluck('name', 'id')
            ->map(fn ($name, $id) => ['id' => $id, 'name' => $name, 'current' => $now[$id] ?? 0, 'previous' => $before[$id] ?? 0])
            ->sortBy([['current', 'desc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    private function insights(AnalyticsPeriod $period, array $totals, array $categories, array $timeOfWeek, array $backlog, array $caseOutcomes, ?array $sos, ?array $map, ?array $barangays): array
    {
        $insights = [];
        $comparison = $period->toArray()['comparison'];
        ['current' => $total, 'previous' => $before] = $totals;

        if ($sos && $sos['unacknowledged'] > 0) {
            $insights[] = ['tone' => 'critical', 'text' => sprintf('%s in this period %s never acknowledged by the desk.', $this->plural($sos['unacknowledged'], 'SOS alert'), $sos['unacknowledged'] === 1 ? 'was' : 'were')];
        }

        if ($total === 0) {
            $insights[] = ['tone' => 'info', 'text' => "No incidents were recorded in the {$period->toArray()['label']}."];
        } elseif ($before === 0) {
            $insights[] = ['tone' => 'info', 'text' => sprintf('%s recorded, with none in the %s to compare against.', $this->plural($total, 'incident'), $comparison)];
        } else {
            $change = (int) round(($total - $before) / $before * 100);
            $insights[] = match (true) {
                $change >= 10 => ['tone' => 'warning', 'text' => "Incidents rose {$change}% compared with the {$comparison} ({$before} → {$total})."],
                $change <= -10 => ['tone' => 'good', 'text' => sprintf('Incidents fell %d%% compared with the %s (%d → %d).', abs($change), $comparison, $before, $total)],
                default => ['tone' => 'info', 'text' => "Incident volume held steady: {$total} against {$before} in the {$comparison}."],
            };
        }

        if ($total > 0 && $categories[0]['current'] > 0) {
            $top = $categories[0];
            $insights[] = ['tone' => 'info', 'text' => sprintf('%s is the most common incident type: %d of %d (%d%%).', $top['name'], $top['current'], $total, round($top['current'] / $total * 100))];
        }

        $rising = collect($categories)
            ->filter(fn ($type) => $type['current'] - $type['previous'] >= 3 && $type['current'] >= 1.5 * $type['previous'])
            ->sortByDesc(fn ($type) => $type['current'] - $type['previous'])
            ->first();
        if ($rising) {
            $insights[] = ['tone' => 'warning', 'text' => "{$rising['name']} incidents climbed from {$rising['previous']} to {$rising['current']}, the sharpest increase of any type."];
        }

        $peak = collect($timeOfWeek['grid'])
            ->flatMap(fn ($blocks, $day) => collect($blocks)->map(fn ($count, $block) => ['day' => $day, 'block' => $block, 'count' => $count]))
            ->sortByDesc('count')
            ->first();

        if ($total >= self::PEAK_MIN_INCIDENTS && $peak['count'] >= self::PEAK_MIN_IN_BLOCK) {
            $from = $timeOfWeek['blocks'][$peak['block']];
            $to = CarbonImmutable::today()->setHour(($peak['block'] + 1) * self::TIME_BLOCK_HOURS % 24)->format('g A');
            $insights[] = ['tone' => 'info', 'text' => "The busiest time is {$timeOfWeek['days'][$peak['day']]}, {$from}–{$to} ({$this->plural($peak['count'], 'incident')}). Plan patrols and desk coverage around it."];
        }

        if ($map && $map['hotspot']) {
            $share = (int) round($map['hotspot']['count'] / $map['located'] * 100);
            $insights[] = ['tone' => $share >= 25 ? 'warning' : 'info', 'text' => "{$map['hotspot']['count']} of {$map['located']} located reports ({$share}%) came from one area about 100 m across, circled on the hotspot map."];
        }

        if ($barangays && $total > 0 && count(array_filter($barangays, fn ($barangay) => $barangay['current'] > 0)) > 1) {
            $top = $barangays[0];
            $insights[] = ['tone' => 'info', 'text' => sprintf('%s recorded the most incidents: %d of %d citywide (%d%%).', $top['name'], $top['current'], $total, round($top['current'] / $total * 100))];
        }

        if ($sos && $sos['medianAckMinutes'] !== null) {
            $insights[] = ['tone' => $sos['medianAckMinutes'] <= 5 ? 'good' : 'warning', 'text' => "SOS alerts were acknowledged in a median of {$sos['medianAckMinutes']} minutes across {$this->plural($sos['current'], 'alert')}."];
        }

        if ($backlog['open'] > 0) {
            $insights[] = ['tone' => $backlog['oldestDays'] > 7 ? 'warning' : 'info', 'text' => sprintf('%s still pending or in progress; the oldest has waited %s.', $this->plural($backlog['open'], 'report is', 'reports are'), $this->plural($backlog['oldestDays'], 'day'))];
        }

        if ($caseOutcomes['settlementRate'] !== null) {
            $insights[] = ['tone' => $caseOutcomes['settlementRate'] >= 70 ? 'good' : 'warning', 'text' => "{$caseOutcomes['settlementRate']}% of this period's closed cases were settled at the barangay level; {$this->plural($caseOutcomes['escalated'], 'case')} went to court."];
        }

        return $insights;
    }

    private function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count === 1 ? $singular : ($plural ?? Str::plural($singular)));
    }
}
