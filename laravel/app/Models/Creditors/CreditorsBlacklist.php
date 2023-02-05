<?php

namespace App\Models\Creditors;

use Illuminate\Database\Eloquent\Model;

class CreditorsBlacklist extends Model
{
    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeMatchSearch($query, $search)
    {
        if ($search) {
            return $query
                ->whereHas('creditor', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('alternative', 'like', "%{$search}%");
                });
        }
    }

    public function scopeOrderByDate($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('created_at', $sort);
        }

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

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id')->select('id', 'nickname', 'full_name');
    }
}
