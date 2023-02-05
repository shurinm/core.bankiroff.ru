<?php

namespace App\Models\Products\Cards;

use Illuminate\Database\Eloquent\Model;

class CardsOption extends Model
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

    public function scopeWhereSlug($query, $slug)
    {
        if ($slug) {
            return $query
                ->where('slug', $slug);
        }
    }
}
