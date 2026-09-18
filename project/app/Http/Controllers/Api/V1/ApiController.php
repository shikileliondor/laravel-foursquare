<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected const PER_PAGE = 20;

    protected const MAX_PER_PAGE = 100;

    protected function perPage(Request $request): int
    {
        return (int) max(1, min(self::MAX_PER_PAGE, $request->integer('per_page', self::PER_PAGE)));
    }
}
