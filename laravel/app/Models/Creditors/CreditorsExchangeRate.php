<?php

namespace App\Models\Creditors;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CreditorsExchangeRate extends Model
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
            return $query
                ->paginate($xnumber);
        } else {
            return $query
                ->take($xnumber)->get();
        }
    }

    public function creditor()
    {
        return $this->hasOne('App\Models\Creditors\Creditor', 'id', 'creditor_id')->select('id', 'name', 'genitive', 'image', 'type_slug', 'phone');
    }

    public function currency()
    {
        return $this->hasOne('App\Models\Currencies\Currency', 'id', 'currency_id');
    }

    public function scopeMatchCreditorsIds($query, $creditorsIds)
    {
        if ($creditorsIds) {
            return  $query->whereIn('creditor_id', $creditorsIds);
        }
    }
    public function scopeMatchCurrenciesIds($query, $currenciesIds)
    {
        if ($currenciesIds) {
            return  $query->whereIn('currency_id', $currenciesIds);
        }
    }
}
