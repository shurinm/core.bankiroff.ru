<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;

class NewsTheme extends Model
{
    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('slug AS value', 'title as title');
        }
        return $query
            ->select('id', 'title');
    }
}
