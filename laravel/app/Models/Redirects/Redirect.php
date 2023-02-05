<?php

namespace App\Models\Redirects;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeMatchUrl($query, $url)
    {
        return $query
            ->where('old_url', $url);
    }
}
