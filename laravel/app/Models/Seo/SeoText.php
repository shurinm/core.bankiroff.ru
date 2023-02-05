<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoText extends Model
{

    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeMatchUrl($query, $url)
    {
        return $query
            ->where('url', $url)
            ->orWhere('url', rtrim($url, "/"));
    }

    public function scopeMatchIsAboutPage($query, $aboutPages)
    {
        if ($aboutPages) {
            return $query
                ->where('is_about_page', 1);
        }
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

    public function scopeMatchTitleLike($query, $title)
    {
        if ($title) {
            return  $query->where('title', 'like', "%{$title}%");
        }
    }

    public function scopeMatchH1Like($query, $h1)
    {
        if ($h1) {
            return  $query->where('h1', 'like', "%{$h1}%");
        }
    }
    public function scopeMatchUrlLike($query, $url)
    {
        if ($url) {
            return  $query->where('url', 'like', "%{$url}%");
        }
    }

    public function scopeMatchSubdomainLike($query, $subdomain)
    {
        // if ($subdomain) {
        //     return  $query->where('subdomain', 'like', "%{$subdomain}%");
        // }
        if ($subdomain) {
            return $query
                ->where('subdomain', 'like', "%{$subdomain}%")
                ->orWhere('subdomain', 'all');
        }
        return $query
            ->where('subdomain', 'all')->orWhere('subdomain', null)->orWhere('subdomain', '');
    }
}
