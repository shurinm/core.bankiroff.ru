<?php

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Review extends Model
{

    protected $dates = ['published_at'];

    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopePublishedAndActive($query)
    {
        return $query
            ->where(function ($query) {
                $query->where('published_at', '!=', null)->where('published_at', '<=', Carbon::now())
                    ->orWhere('published_at', null);
            })
            ->where('active', 1)
            ->orderBy('created_at', 'desc')->orderBy('published_at', 'desc');
    }

    public function scopeOrderByDate($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('published_at', $sort)->orderBy('created_at', $sort);
        }

        return $query
            ->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }

    public function scopeOrderByCreationDate($query, $sort)
    {

        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopeSelectFieldsKV($query,  $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'id as title');
        }
    }

    public function scopeMatchType($query,  $slug)
    {
        if ($slug && $slug != 'all') {
            return $query
                ->where('type_slug', $slug);
        }
    }

    public function scopeOrderByPopularity($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('views', $sort);
        }
    }

    public function scopeOrderByRating($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('stars', $sort);
        }
    }

    public function scopeMatchSlugAndID($query, $slug, $id)
    {
        if ($slug == 'creditors') {
            return $query
                ->where('creditor_id', $id);
        }
        return $query
            ->where('type_slug', $slug)->where('item_id', $id);
    }

    public function scopeMatchTypeSlug($query, $slug)
    {
        if ($slug) {
            return $query
                ->where('type_slug', $slug);
        }
    }

    public function scopeMatchProductType($query, $slug, $product_slug)
    {
        if ($slug && $slug == 'credits') {
            if ($product_slug !== 'mortgage' && $product_slug !== 'refinancing') {
                return $query
                    ->where('type_slug', $slug)
                    ->whereHas('credit', function ($query) use ($product_slug) {
                        $query->where('pledge_slug', $product_slug);
                    });
            } else {
                return $query
                    ->where('type_slug', $slug)
                    ->whereHas('credit', function ($query) use ($product_slug) {
                        $query->where('type_slug', $product_slug);
                    });
            }
        } else if ($slug == 'cards') {
            return $query
                ->where('type_slug', $slug)
                ->whereHas('card', function ($query) use ($product_slug) {
                    $query->where('type', $product_slug);
                });;
        }

        return $query
            ->where('type_slug', $slug);
    }

    public function scopeMatchCreditorSlug($query, $slug)
    {
        if ($slug) {
            return $query
                ->whereHas('creditor', function ($query) use ($slug) {
                    $query->where('type_slug', $slug);
                });
        }
    }

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            return $query
                ->paginate($xnumber);
        } else if ($xnumber && $xnumber != 'all') {
            return $query
                ->take($xnumber)->get();
        } else {
            return $query->get();
        }
    }

    public function scopeMatchReviewTextLike($query, $text)
    {
        if ($text) {
            return  $query->where('text', 'like', "%{$text}%");
        }
    }

    public function scopeMatchUsersNicknameLike($query, $nick)
    {
        if ($nick) {
            return $query
                ->whereHas('user', function ($query) use ($nick) {
                    $query->where('nickname', 'like', "%{$nick}%");
                });
        }
    }

    public function scopeMatchAuthorLike($query, $author)
    {
        if ($author) {
            return $query
                ->where('author', 'like', "%{$author}%");
        };
    }

    public function scopeMatchUsersTypes($query, $types)
    {
        if ($types) {
            return $query
                ->whereIn('user_type', $types);
        };
    }

    public function scopeMatchCreditorsIds($query, $creditorsIds)
    {
        if ($creditorsIds) {
            return  $query->whereIn('creditor_id', $creditorsIds);
        }
    }
    public function scopeMatchStars($query, $stars)
    {
        if ($stars) {
            return  $query->whereIn('stars', $stars);
        }
    }

    public function scopeMatchProductId($query, $productId)
    {
        if ($productId) {
            return $query
                ->where('id', $productId);
        }
    }


    public function scopeMatchProductsFilters($query, $sort, $id)
    {

        $local_sort = $sort ?? 'default';
        switch ($local_sort) {
            case 'consumers':
            case 'microloans':
            case 'deposits':
                return $query
                    ->where('creditor_id', $id)->where('type_slug', $local_sort);
            case 'cards_credit':
            case 'cards_debit':
                $pieces_sort = explode("_", $local_sort);
                $slug = $pieces_sort[0];
                $card_type = $pieces_sort[1];
                return $query
                    ->where('creditor_id', $id)->where('type_slug', $slug)->whereHas('card', function ($q) use ($card_type) {
                        $q->where('type', $card_type);
                    });
            case 'credits_refinancing':
            case 'credits_mortgage':
                $pieces_sort = explode("_", $local_sort);
                $slug = $pieces_sort[0];
                $sub_slug = $pieces_sort[1];
                return $query
                    ->where('creditor_id', $id)->where('type_slug', $slug)->whereHas('credit', function ($q) use ($sub_slug) {
                        $q->where('type_slug', $sub_slug);
                    });
            case 'credits_flats':
            case 'credits_rooms':
            case 'credits_shared':
            case 'credits_house':
            case 'credits_parcels':
            case 'credits_apartments':
            case 'credits_townhouse':
            case 'credits_commercial':
                $pieces_sort = explode("_", $local_sort);
                $slug = $pieces_sort[0];
                $sub_slug = $pieces_sort[1];
                return $query
                    ->where('creditor_id', $id)->where('type_slug', $slug)->whereHas('credit', function ($q) use ($sub_slug) {
                        $q->where('type_slug', 'pledge')->where('pledge_slug', $sub_slug);
                    });

            default:
                return $query
                    ->where('creditor_id', $id);
        }
    }

    public function scopeSelectFields($query)
    {
        return $query
            ->select('id', 'user_id', 'user_type', 'type_slug', 'item_id', 'creditor_id', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }

    public function scopeGetCountComments($query)
    {
        return $query
            ->withCount(['comments as count_comments' => function ($internal_query) {
                $internal_query;
            }]);
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

    // public function getCreatedAtAttribute()
    // {
    //     return Carbon::parse($this->attributes['created_at'])->format('d.m.Y');
    // }

    // public function getPublishedAtAttribute()
    // {
    //     return $this->attributes['published_at']?Carbon::parse($this->attributes['published_at'])->format('d.m.Y'):null;
    // }

    public function user()
    {
        return $this->belongsTo('App\User')->select('id', 'nickname', 'full_name', 'image');
    }
    public function creditor()
    {
        //'item_id'
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'prepositional', 'genitive', 'image', 'phone', 'type_slug');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Reviews\ReviewsComment')->where('active', 1)->select('text', 'created_at', 'user_id')->orderBy('created_at', 'desc');
    }

    public function card()
    {
        return $this->hasOne('App\Models\Products\Cards\Card', 'id', 'item_id');
    }
    public function card_type()
    {
        return $this->hasOne('App\Models\Products\Cards\Card', 'id', 'item_id')->select('type');
    }

    public function credit()
    {
        return $this->hasOne('App\Models\Products\Credits\Credit', 'id', 'item_id');
    }
    public function credit_types()
    {
        return $this->hasOne('App\Models\Products\Credits\Credit', 'id', 'item_id')->select('type_slug', 'pledge_slug');
    }

    public function deposit()
    {
        return $this->hasOne('App\Models\Products\Deposits\Deposit', 'id', 'item_id');
    }
    public function consumer()
    {
        return $this->hasOne('App\Models\Products\Credits\Consumer', 'id', 'item_id');
    }
    public function microloan()
    {
        return $this->hasOne('App\Models\Products\Credits\Microloan', 'id', 'item_id');
    }
    public function regions()
    {
        return $this->hasMany('App\Interceptions\ReviewsRegionsInterception');
    }
    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ReviewsRegionsInterception')->select('region_id AS value');
    }
}
