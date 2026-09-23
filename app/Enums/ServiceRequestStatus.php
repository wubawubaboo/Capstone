<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Pending = 'Pending';
    case InProgress = 'In Progress';
    case Completed = 'Completed';

    /**
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress],
            self::InProgress => [self::Completed],
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
