<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Model;

class AdvertisementTypes extends Model
{
    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'type as type', 'title as title');
        }
        return $query
            ->select('id', 'type', 'title');
    }
}
