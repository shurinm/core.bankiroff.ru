<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class ReadyQueriesDisplayPage extends Model
{
    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'title as title');
        }
        return $query
            ->select('id', 'title');
    }
}
