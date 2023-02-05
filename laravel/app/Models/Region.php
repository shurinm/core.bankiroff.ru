<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{

    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeActiveSubdomain($query)
    {
        return $query
            ->where('is_active_subdomain', 1);
    }
    
    public function getSubdomainAttribute()
    {
        return $this->attributes['subdomain'];
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

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            return $query
                ->paginate($xnumber);
        } else if ($xnumber) {
            return $query
                ->take($xnumber)->get();
        } else {
            return $query
                ->get();
        }
    }

    public function scopeMatchNameLike($query, $name)
    {
        if ($name) {
            return  $query->where('name', 'like', "%{$name}%");
        }
    }

    public function scopeMatchSubdomainLike($query, $subdomain)
    {
        if ($subdomain) {
            return  $query->where('subdomain', 'like', "%{$subdomain}%");
        }
    }

    public function scopeMatchSubdomain($query, $subdomain)
    {
        if ($subdomain) {
            return  $query->where('subdomain', $subdomain);
        }
    }

    public function scopeMatchActiveSubdomainState($query, $active)
    {
        $boolean_state = $active == 'yes' ? 1 : 0;
        if ($active) {
            $query
                ->where('is_active_subdomain', $boolean_state);
        }
    }
}
