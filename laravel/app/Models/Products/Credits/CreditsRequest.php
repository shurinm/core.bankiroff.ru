<?php

namespace App\Models\Products\Credits;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class CreditsRequest extends Model
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

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'image', 'genitive', 'phone', 'type_slug');
    }

    public function user()
    {
        return $this->belongsTo('App\User')->select('id', 'nickname');
    }

    public function region()
    {
        return $this->hasOne('App\Models\Region', 'id', 'region_id');
    }

    public function credit()
    {
        return $this->belongsTo('App\Models\Products\Credits\Credit', 'item_id', 'id');
    }
    public function consumer()
    {
        return $this->belongsTo('App\Models\Products\Credits\Consumer', 'item_id', 'id');
    }
    public function microloan()
    {
        return $this->belongsTo('App\Models\Products\Credits\Microloan', 'item_id', 'id');
    }
}
