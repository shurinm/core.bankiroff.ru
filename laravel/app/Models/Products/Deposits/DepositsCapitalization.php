<?php

namespace App\Models\Products\Deposits;

use Illuminate\Database\Eloquent\Model;

class DepositsCapitalization extends Model
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
