<?php

namespace App\Models\Products\Credits;

use Illuminate\Database\Eloquent\Model;

class CreditsProof extends Model
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
