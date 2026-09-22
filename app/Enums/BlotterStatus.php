<?php

namespace App\Enums;

enum BlotterStatus: string
{
    case Pending = 'Pending';
    case UnderMediation = 'Under Mediation';
    case Resolved = 'Resolved';
    case EscalatedToCourt = 'Escalated to Court';

    /**
     * Statuses this case can move to directly. Resolved/EscalatedToCourt are
     * terminal — moving out of them requires the explicit reopen() action
     * instead of an ordinary transition, so closed cases can't be silently
     * changed without a recorded reason.
     *
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
