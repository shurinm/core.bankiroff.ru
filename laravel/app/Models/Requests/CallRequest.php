<?php

namespace App\Models\Requests;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class CallRequest extends Model
{
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
