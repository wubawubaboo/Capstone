<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blottered = 'blottered';
    case Completed = 'completed';
    case Escalated = 'escalated';

    /**
     * Statuses this report can move to directly. Blottered/Escalated are set
     * as side effects of the blotter workflow (logging a report to a case,
     * then resolving/escalating that case), not through the plain report
     * status dropdown.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Blottered],
            self::InProgress => [self::Completed, self::Blottered],
            self::Blottered => [self::Completed, self::Escalated],
            self::Escalated => [self::Completed],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed;
    }
}
