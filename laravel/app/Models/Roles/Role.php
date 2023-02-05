<?php

namespace App\Models\Roles;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
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

    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'name as title');
        }
        return $query
            ->select('id', 'title');
    }

    public function permissions()
    {
        return $this->hasMany('App\Interceptions\RolesPermissionsInterception')->select('permission_id AS value');
    }
}
