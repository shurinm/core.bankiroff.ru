<?php

namespace App\Models\Currencies;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Currency extends Model
{

    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'title as title');
        }
        return $query
            ->select('id', 'title', 'iso_name');
    }

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y H:i');
    }

    public function scopeOrderByDate($query)
    {
        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            if ($xnumber != 'all') {
                return $query->paginate($xnumber);
            } else {
                return $query->paginate(10000000000000000000000000);
            }
        } else {
            return $query
                ->take($xnumber)->get();
        }
    }
}
