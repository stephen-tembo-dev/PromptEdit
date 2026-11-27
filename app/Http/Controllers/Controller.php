<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Page sizes the tables allow. The first is the default. The Pagination
     * component uses the same list.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    /**
     * Page size from ?per_page. Unknown values get the default, which stops
     * anyone asking for thousands of rows.
     */
    protected function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page');

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0];
    }
}
