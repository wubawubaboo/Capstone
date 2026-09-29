<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * The two desks that handle blotter cases. Both run the same case workflow
 * (App\Http\Controllers\CaseDeskController); this enum holds everything that
 * differs between them: which cases they see, their pages and routes, and
 * the wording used in status history and the audit log.
 */
enum CaseDesk: string
{
    case Secretary = 'secretary';
    case Vawc = 'vawc';

    /** VAWC cases are confidential: viewing one is audited, and wording says so. */
    public function isConfidential(): bool
    {
        return $this === self::Vawc;
    }

    /** Limits a BlotterRecord query to this desk's cases. */
    public function scopeCases(Builder $query): Builder
    {
        return $this->isConfidential()
            ? $query->whereHas('vawcDetail')
            : $query->whereDoesntHave('vawcDetail');
    }

    /** Folder under resources/js/Pages holding this desk's pages. */
    public function pageFolder(): string
    {
        return $this->isConfidential() ? 'VAWC' : 'Secretary';
    }

    /** Route-name prefix of this desk's route group in routes/web.php. */
    public function routePrefix(): string
    {
        return $this->value;
    }

    /** Relations a case page or PDF needs beyond the common ones. */
    public function extraCaseRelations(): array
    {
        return $this->isConfidential() ? ['vawcDetail.officer'] : [];
    }

    /** "Case" / "VAWC Case", as in "Resolved VAWC Case #VAWC-2026-0001." */
    public function caseLabel(): string
    {
        return $this->isConfidential() ? 'VAWC Case' : 'Case';
    }

    /** Start of status-history notes: "Case resolved." / "Confidential VAWC case resolved." */
    public function historyNoun(): string
    {
        return $this->isConfidential() ? 'Confidential VAWC case' : 'Case';
    }

    /** Audit-log module for cases, and for their mediation sessions. */
    public function logModule(): string
    {
        return $this->isConfidential() ? 'VAWC Blotter' : 'Blotter';
    }

    public function mediationLogModule(): string
    {
        return $this->isConfidential() ? 'VAWC Mediation Schedule' : 'Mediation Schedule';
    }

    public function reportFilePrefix(): string
    {
        return $this->isConfidential() ? 'vawc-case-report' : 'case-report';
    }
}
