<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

final class AnalyticsPeriod
{
    public const RANGES = [
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
    ];

    public const DEFAULT_RANGE = '90d';

    private function __construct(
        public readonly string $range,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $previousStart,
    ) {}

    /** Unknown or missing ranges fall back to the default. */
    public static function fromRequest(Request $request): self
    {
        $range = (string) $request->query('range', self::DEFAULT_RANGE);

        if (! array_key_exists($range, self::RANGES)) {
            $range = self::DEFAULT_RANGE;
        }

        $today = CarbonImmutable::today();

        [$start, $previousStart] = match ($range) {
            '30d' => [$today->subDays(29), $today->subDays(59)],
            '90d' => [$today->subDays(89), $today->subDays(179)],
            '12m' => [$today->startOfMonth()->subMonths(11), $today->startOfMonth()->subMonths(23)],
        };

        return new self($range, $start, $today->endOfDay(), $previousStart);
    }

    public function contains(CarbonInterface $moment): bool
    {
        return $moment->between($this->start, $this->end);
    }

    /** From the start of the previous window to the end of this one, for a single query. */
    public function fullWindow(): array
    {
        return [$this->previousStart, $this->end];
    }

    /** Trend charts plot days for 30 days, weeks for 90 days and months for 12 months. */
    public function bucketUnit(): string
    {
        return match ($this->range) {
            '30d' => 'day',
            '90d' => 'week',
            '12m' => 'month',
        };
    }

    /** The trend bucket a moment falls in, keyed by the bucket's first day. */
    public function bucketKey(CarbonInterface $moment): string
    {
        return $this->bucketStart(CarbonImmutable::instance($moment))->toDateString();
    }

    /**
     * Every trend bucket in the period, in order, as key => axis label.
     *
     * @return array<string, string>
     */
    public function buckets(): array
    {
        $buckets = [];

        for ($cursor = $this->bucketStart($this->start); $cursor <= $this->end; $cursor = $cursor->addUnit($this->bucketUnit())) {
            $buckets[$cursor->toDateString()] = $cursor->format($this->bucketUnit() === 'month' ? 'M Y' : 'M j');
        }

        return $buckets;
    }

    public function toArray(): array
    {
        return [
            'range' => $this->range,
            'label' => self::RANGES[$this->range],
            'comparison' => 'previous '.strtolower(substr(self::RANGES[$this->range], 5)),
            'from' => $this->start->format('M j, Y'),
            'to' => $this->end->format('M j, Y'),
            'bucket' => $this->bucketUnit(),
            'options' => collect(self::RANGES)->map(fn ($label, $value) => compact('value', 'label'))->values(),
        ];
    }

    private function bucketStart(CarbonImmutable $moment): CarbonImmutable
    {
        return match ($this->bucketUnit()) {
            'day' => $moment->startOfDay(),
            'week' => $moment->startOfWeek(CarbonInterface::MONDAY),
            'month' => $moment->startOfMonth(),
        };
    }
}
