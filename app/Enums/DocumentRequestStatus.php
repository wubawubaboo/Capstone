<?php

namespace App\Enums;

enum DocumentRequestStatus: string
{
    case Pending = 'Pending';
    case Ready = 'Ready';
    case Claimed = 'Claimed';

    /**
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Ready],
            self::Ready => [self::Claimed],
            self::Claimed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Claimed;
    }
}
