<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SubdomainsKey extends Model
{
    public static function scopeMatchSubdomain($query, $subdomain)
    {
        return $query
            ->where('subdomain', '=', $subdomain);
    }

    public static function scopeMatchType($query, $type)
    {
        return $query
            ->where('type', '=', $type);
    }
}
