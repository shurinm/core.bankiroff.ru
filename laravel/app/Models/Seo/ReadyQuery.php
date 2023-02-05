<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class ReadyQuery extends Model
{
    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeMatchUrl($query, $display_page_id)
    {
        return $query
            ->whereHas('displayPages', function ($query) use ($display_page_id) {
                $query->where('ready_query_display_page_id', $display_page_id);
            });
    }

    public function scopeMatchSubdomain($query, $subdomain)
    {
        if ($subdomain) {
            return $query
                ->where('subdomain', $subdomain)
                ->orWhere('subdomain', 'all');
        }
        return $query
            ->where('subdomain', 'all')->orWhere('subdomain', null)->orWhere('subdomain', '');
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

    public function scopeOrderByDate($query)
    {
        return $query
            ->orderBy('created_at', 'desc');
    }

    public function displayPages()
    {
        return $this->hasMany('App\Interceptions\ReadyQueriesDisplayPagesInterception');
    }

    public function division()
    {
        return $this->hasOne('App\Models\Seo\ReadyQueriesDivision', 'id', 'division_id')->select('id', 'title', 'description');
    }

    public function displayPagesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ReadyQueriesDisplayPagesInterception')->select('ready_query_display_page_id AS value');
    }
}
