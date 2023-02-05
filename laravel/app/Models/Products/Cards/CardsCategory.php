<?php

namespace App\Models\Products\Cards;

use Illuminate\Database\Eloquent\Model;

class CardsCategory extends Model
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
