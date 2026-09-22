<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Default page size for paginated index listings. Kept in one place so
     * every listing page (blotters, reports, requests, assets, audit logs)
     * paginates consistently instead of each controller picking its own number.
     */
    protected const PER_PAGE = 15;
}
