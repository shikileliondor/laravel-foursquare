<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sql
{
    /**
     * Case-insensitive LIKE operator for the active connection.
     */
    public static function like(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
