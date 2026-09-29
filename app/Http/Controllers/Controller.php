<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Default page size for paginated index listings. Kept in one place so
     * every listing page (blotters, reports, requests, assets, audit logs)
     * paginates consistently instead of each controller picking its own number.
     */
    protected const PER_PAGE = 15;

    /**
     * First day of the month a calendar page shows, from `?month=YYYY-MM`.
     * Missing or invalid values fall back to the current month.
     */
    protected function calendarMonth(Request $request): CarbonImmutable
    {
        $month = (string) $request->query('month', '');

        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $parts) && checkdate((int) $parts[2], 1, (int) $parts[1])) {
            return CarbonImmutable::create((int) $parts[1], (int) $parts[2], 1);
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * The date range to load for a calendar month, padded by a day on each
     * side: the page groups hearings by the viewer's local date, which can
     * differ from the server's UTC date near midnight.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function calendarRange(CarbonImmutable $month): array
    {
        return [$month->subDay(), $month->endOfMonth()->addDay()];
    }
}
