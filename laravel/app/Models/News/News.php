<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class News extends Model
{

    protected $dates = ['published_at'];

    public function scopePublishedAndActive($query, $isAdmin = false)
    {
        if (!$isAdmin) {
            return $query
                ->where(function ($query) {
                    $query->where('published_at', '!=', null)->where('published_at', '<=', Carbon::now())
                        ->orWhere('published_at', null);
                })
                ->where('active', 1)
                ->orderBy('published_at', 'desc');
        }
    }

    
    public function scopeMatchPublishedAtFromXDays($query, $xDays)
    {

        if($xDays){
            return $query->where(function ($query) use ($xDays) {
                $query->where('published_at', '!=', null)->where('published_at', '<=', Carbon::now())->where('published_at', '>=', Carbon::now()->subDays($xDays))
                    ->orWhere('published_at', null)->where('created_at', '>=', Carbon::now()->subDays($xDays));
            });
        }
    }

    public function scopeMatchArticlesOrNews($query, $type)
    {
        $articles_theme_slugs = ['advices', 'analytics', 'comparisons'];
        if($type && $type != 'all'){
            if($type == 'articles'){
                return $query
                ->whereIn('theme_slug', $articles_theme_slugs);
            }
            return $query
                ->whereNotIn('theme_slug', $articles_theme_slugs);
        }
    }

    public function scopeMatchNoSpecialCharacterForCNC($query)
    {
        return $query
                ->where('title', 'not like', "%ъ%");
    }

    public function scopeWhereThemeSlug($query, $slug)
    {

        if ($slug && $slug != 'all' && $slug != 'newsfeed') {
            return $query
                ->where('theme_slug', $slug);
        } else if ($slug == 'newsfeed') {
            return $query
                ->whereNotIn('theme_slug', ['advices', 'analytics', 'comparisons']);
        }
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
    public function scopeOrderByDate($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('created_at', $sort);
        }

        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('published_at', '<=', Carbon::now())
            ->orderBy('published_at', 'desc');
    }

    public function scopeWhereAdviceSlug($query, $advice_slug)
    {
        return $query
            ->where('theme_slug', 'advices')
            ->where('advice_slug', $advice_slug);
    }

    public function scopeMatchSubdomain($query, $subdomain)
    {

        if ($subdomain) {
            return  $query
                ->doesntHave('regions')
                ->orWhereHas('regions', function ($query) use ($subdomain) {
                    $query->where('region_id', $subdomain);
                });
        }
    }

    public function scopeMatchSubdomainRuleSitemaps($query, $subdomain)
    {

        if ($subdomain) {
            /* The material should have just 1 region and that region should match the subdomain */
            return  $query
                ->has('regions', '=', 1)
                ->whereHas('regions', function ($query) use ($subdomain) {
                    $query->where('region_id', $subdomain);
                });
        }
        /* Material should be global in order to be in sitemaps for bankiroff.ru */
        return  $query->has('regions', '=', 0)->orHas('regions', '>', 1);
    }

    public function scopeSelectFields($query)
    {

        return $query
            ->select('id', 'title', 'theme_slug', 'advice_slug', 'image', 'text', 'views', 'created_at', 'updated_at', 'published_at');
    }

    public function scopeSelectFieldsKV($query,  $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'title as title');
        }
    }

    public function scopeGetCommentsCount($query)
    {
        return $query
            ->withCount(['comments as comments_count' => function ($internal_query) {
                $internal_query;
            }]);
    }

    public function scopeMatchThemes($query, $themes)
    {
        if ($themes) {
            return $query->whereIn('theme_slug', $themes);
        }
    }
    public function scopeMatchAdvicesSlugs($query, $slugs)
    {
        if ($slugs) {
            return $query->whereIn('advice_slug', $slugs);
        }
    }

    public function scopeMatchTitleLike($query, $title)
    {
        if ($title) {
            return  $query->where('title', 'like', "%{$title}%");
        }
    }

    // public function getCreatedAtAttribute()
    // {
    //     return Carbon::parse($this->attributes['created_at'])->format('d.m.Y ');
    // }

    // public function getUpdatedAtAttribute()
    // {
    //     return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y');
    // }

    // public function getPublishedAtAttribute()
    // {
    //     return Carbon::parse($this->attributes['published_at'])->format('d.m.Y');
    // }

    public function regions()
    {
        return $this->hasMany('App\Interceptions\NewsRegionsInterception');
    }

    public function tags()
    {
        return $this->hasMany('App\Interceptions\NewsTagsInterception')->select("id", "news_id", "news_tag_id");
    }

    public function comments()
    {

        return $this->hasMany('App\Models\News\NewsComment')->where('active', 1)->select('text', 'news_id', 'created_at', 'user_id')->orderBy('created_at', 'desc');
    }


    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\NewsRegionsInterception')->select('region_id AS value');
    }

    public function tagsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\NewsTagsInterception')->select('news_tag_id AS value');
    }

    public function productsCreditorsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\NewsProductsCreditorsInterception')->select('item_id AS value', 'type as type_product');
    }
}
