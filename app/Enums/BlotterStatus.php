<?php

namespace App\Enums;

enum BlotterStatus: string
{
    case Pending = 'Pending';
    case UnderMediation = 'Under Mediation';
    case Resolved = 'Resolved';
    case EscalatedToCourt = 'Escalated to Court';

    /**
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::UnderMediation, self::Resolved, self::EscalatedToCourt],
            self::UnderMediation => [self::UnderMediation, self::Resolved, self::EscalatedToCourt],
            self::Resolved, self::EscalatedToCourt => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Resolved || $this === self::EscalatedToCourt;
    }
}
