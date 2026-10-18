<?php

namespace App\Services\Supabase;

class Supabase
{
    public static function table(string $table): SupabaseQuery
    {
        return new SupabaseQuery($table);
    }
}
