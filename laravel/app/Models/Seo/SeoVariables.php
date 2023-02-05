<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoVariables extends Model
{


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
}
