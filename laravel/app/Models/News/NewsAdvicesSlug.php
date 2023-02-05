<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;

class NewsAdvicesSlug extends Model
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
