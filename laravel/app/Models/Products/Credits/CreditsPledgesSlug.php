<?php

namespace App\Models\Products\Credits;

use Illuminate\Database\Eloquent\Model;

class CreditsPledgesSlug extends Model
{
    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('slug AS value', 'title as title')->orderBy('id', 'desc');
        }
        return $query
            ->select('id', 'title');
    }
}
