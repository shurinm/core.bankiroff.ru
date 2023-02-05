<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InternalLog extends Model
{
    public function scopeMatchTypesIds($query, $types)
    {
        if ($types) {
            return $query->whereIn('type_id', $types);
        }
    }

    public function scopeMatchUsersIds($query, $usersIds)
    {
        if ($usersIds) {
            return $query->whereIn('user_id', $usersIds);
        }
    }

    public function scopeMatchActions($query, $actions)
    {
        if ($actions) {
            return $query->whereIn('action', $actions);
        }
    }

    public function scopeMatchItemId($query, $itemId)
    {
        if ($itemId) {
            return $query->where('item_id', $itemId);
        }
    }

    public function scopeMatchNickOrEmail($query, $nickOrEmail)
    {
        if ($nickOrEmail) {
            return $query->whereHas('user', function ($query2) use ($nickOrEmail) {
                $query2->where('nickname', 'like', "%{$nickOrEmail}%")->orWhere('email', 'like', "%{$nickOrEmail}%");
            });
        }
    }

    public function scopeOrderByDate($query)
    {
        return $query
            ->orderBy('created_at', 'desc');
    }

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y H:i');
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

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
    
    public function type()
    {
        return $this->hasOne('App\Models\Logs\InternalLogsType', 'id', 'type_id')->select("id", "type");
    }
}
