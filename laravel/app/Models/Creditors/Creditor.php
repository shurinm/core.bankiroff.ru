<?php

namespace App\Models\Creditors;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use DB;

class Creditor extends Model
{

    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeActiveOrAccessibleByDirectLink($query)
    {
        return $query
            ->where('active', 1)->orWhere('direct_access', 1);
    }

    public function scopeWhereSlug($query, $slug)
    {
        if ($slug) {
            return $query
                ->where('type_slug', $slug);
        }
    }
    public function scopeMatchSearch($query, $search)
    {
        if ($search) {
            return $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('alternative', 'like', "%{$search}%")
                ->orWhere('ogrn', '=', "{$search}")
                ->orWhere('license_number', '=', "{$search}");
        }
    }

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            if ($xnumber != 'all') {
                return $query->paginate($xnumber);
            } else {
                return $query->paginate(10000000000000000000000000);
            }
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


    public function scopeMatchId($query, $id)
    {
        if ($id) {
            return $query
                ->where('id', $id);
        }
    }

    public function scopeMatchCreditorsIds($query, $creditorsIds)
    {
        if ($creditorsIds) {
            return  $query->whereIn('id', $creditorsIds);
        }
    }
    public function scopeMatchTypes($query, $types)
    {
        if ($types) {
            return  $query->whereIn('type_slug', $types);
        }
    }

    public function scopeMatchNameLike($query, $name)
    {
        if ($name) {
            return  $query->where('name', 'like', "%{$name}%");
        }
    }

    public function scopeMatchActiveState($query, $active)
    {
        $boolean_state = $active == 'yes' ? 1 : 0;
        if ($active) {
            $query
                ->where('active', $boolean_state);
        }
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

    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'name as title');
        }
        return $query
            ->select('id', 'phone', 'name', 'work_days', 'schedule', 'image', 'thumbnail', 'genitive', 'prepositional', 'type_slug', 'license_revoked', 'direct_access');
    }

    public function scopeAppendRating($query, $id, $request){
        $count_creditors_with_higher_rating =  DB::select("select count(*) as c
            from  creditors left join reviews on reviews.creditor_id = creditors.id where
            creditors.active = 1 and reviews.active = 1 and reviews.user_type = 'client' and
            creditors.type_slug = (select type_slug from creditors where creditors.id = {$id} limit 1)
            group by creditors.id
            HAVING AVG(stars) > (select AVG(stars) from creditors left join reviews on reviews.creditor_id = creditors.id
            WHERE reviews.active = 1 and reviews.user_type = 'client' and creditors.id = {$id}  group by creditors.id )", [1]);

        $count_creditors_with_same_rating_less_name =  DB::select("select count(*) as c
            from  creditors left join reviews on reviews.creditor_id = creditors.id where
            creditors.active = 1 and reviews.active = 1 and reviews.user_type = 'client' and creditors.name < (select name from creditors where creditors.id = {$id} limit 1) and
            creditors.type_slug = (select type_slug from creditors where creditors.id = {$id} limit 1)
            group by creditors.id
            HAVING AVG(stars) = (select AVG(stars) from creditors left join reviews on reviews.creditor_id = creditors.id
            WHERE reviews.active = 1 and reviews.user_type = 'client' and creditors.id = {$id}  group by creditors.id )", [1]);
            $rating_position = count($count_creditors_with_higher_rating)+count($count_creditors_with_same_rating_less_name);

        return $query->addSelect(DB::raw("$rating_position as rating_position"));
    }

    public function scopeMatchFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        $local_sort = $sort ?? 'default';
        switch ($sort) {
            case 'rating_unofficial':
            case 'rating_official':
                /* Filtering by sort, ordering by avg_rating and also reviews_count */
                $query_sort = $sort == 'rating_unofficial' ? "client" : "worker";
                return $query
                    ->withCount([
                        'reviews AS avg_rating' => function ($internal_query) use ($query_sort) {
                            $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', $query_sort);
                        },
                    ])->withCount(['reviews as reviews_count' => function ($internal_query) use ($query_sort) {
                        $internal_query->where('user_type', $query_sort);
                    }])->orderBy('avg_rating', 'desc')->orderBy('reviews_count', 'desc');
            case 'reviews_count&ratings':
                return $query
                    ->withCount([
                        'reviews AS avg_rating' => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"));
                        },
                    ])
                    ->withCount([
                        'reviews AS avg_official_rating' => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', 'worker');
                        },
                    ])->withCount([
                        'reviews AS avg_unofficial_rating' => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', 'client');
                        },
                    ])
                    ->withCount(['reviews as reviews_count' => function ($internal_query) {
                        $internal_query;
                    }]);

            case 'reviews_count':
            default:
                return $query
                    ->withCount([
                        'reviews AS avg_rating' => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"));
                        },
                    ])->withCount(['reviews as reviews_count' => function ($internal_query) {
                        $internal_query;
                    }])->orderBy('reviews_count', 'desc')->orderBy('avg_rating', 'desc');
        }
    }
    public function scopeMatchRatingFilters($query, $rating_slug, $sort, $order)
    {
        $local_sort = $sort ?? 'default';
        $type_order = '';
        $order_direction = '';
        if ($order && count(explode("_", $order)) > 1) {
            $pieces_order = explode("_", $order);
            $type_order = $pieces_order[0];
            $order_direction = $pieces_order[1]  == 'asc' ? 'asc' : 'desc';
        }

        $local_raiting_order = $type_order == 'ratings' ? $order_direction : 'desc';
        $local_reviews_order = $type_order == 'reviews' ? $order_direction : 'desc';

        $user_rating_type = $rating_slug == 'unofficial' ? "client" : "worker";
        error_log("MAKING REQUEST scopeMatchRatingFilters, rating_slug: $rating_slug | local_sort: $local_sort");

        switch ($local_sort) {
            case 'consumers':
            case 'microloans':
            case 'deposits':
            case 'cards_credit':
            case 'cards_debit':
            case 'credits_refinancing':
            case 'credits_flats':
            case 'credits_rooms':
            case 'credits_shared':
            case 'credits_house':
            case 'credits_parcels':
            case 'credits_apartments':
            case 'credits_townhouse':
            case 'credits_commercial':
            case 'credits_mortgage':
                if ($order == 'reviews_desc' || $order == 'reviews_asc') {

                    return $query
                        ->withCount([
                            "reviews_$local_sort AS avg_rating" => function ($internal_query) use ($user_rating_type) {
                                $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', $user_rating_type);
                            },
                        ])->withCount(["reviews_$local_sort as reviews_count" => function ($internal_query) use ($user_rating_type) {
                            $internal_query->where('user_type', $user_rating_type);
                        }])->orderBy('reviews_count', $local_reviews_order)->orderBy('avg_rating', $local_raiting_order);
                }
                return $query
                    ->withCount([
                        "$local_sort AS min_rate" => function ($internal_query) {
                            $internal_query->select(DB::raw("MIN(percent) as min"))->where('percent', '>', 0);
                        },
                    ])
                    ->withCount([
                        "reviews_$local_sort AS avg_rating" => function ($internal_query) use ($user_rating_type) {
                            $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', $user_rating_type);
                        },
                    ])->withCount(["reviews_$local_sort as reviews_count" => function ($internal_query) use ($user_rating_type) {
                        $internal_query->where('user_type', $user_rating_type);
                    }])->orderBy('avg_rating', $local_raiting_order)->orderBy('reviews_count', $local_reviews_order);

            default:

                if ($order == 'reviews_desc' || $order == 'reviews_asc') {
                    return $query
                        ->withCount([
                            'reviews AS avg_rating' => function ($internal_query) use ($user_rating_type) {
                                $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', $user_rating_type);
                            },
                        ])->withCount(['reviews as reviews_count' => function ($internal_query) use ($user_rating_type) {
                            $internal_query->where('user_type', $user_rating_type);
                        }])->orderBy('reviews_count', $local_reviews_order)->orderBy('avg_rating', $local_raiting_order);
                }
                return $query
                    ->withCount([
                        'reviews AS avg_rating' => function ($internal_query) use ($user_rating_type) {
                            $internal_query->select(DB::raw("AVG(stars) as average"))->where('user_type', $user_rating_type);
                        },
                    ])->withCount(['reviews as reviews_count' => function ($internal_query) use ($user_rating_type) {
                        $internal_query->where('user_type', $user_rating_type);
                    }])->orderBy('avg_rating', $local_raiting_order)->orderBy('reviews_count', $local_reviews_order);
        }
    }

    public function scopeMatchFiltersTop5($query, $sort)
    {
        $local_sort = $sort ?? 'default';

        switch ($local_sort) {
            case 'consumers':
            case 'microloans':
            case 'deposits':
            case 'cards_credit':
            case 'cards_debit':
            case 'credits_refinancing':
            case 'credits_flats':
            case 'credits_rooms':
            case 'credits_shared':
            case 'credits_house':
            case 'credits_parcels':
            case 'credits_apartments':
            case 'credits_townhouse':
            case 'credits_commercial':
            case 'credits_mortgage':
                return $query
                    ->withCount([
                        "$local_sort AS min_rate" => function ($internal_query) {
                            $internal_query->select(DB::raw("MIN(percent) as min"))->where('percent', '>', 0);
                        },
                    ])
                    ->withCount([
                        "reviews_$local_sort AS avg_rating" => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"));
                        },
                    ])->withCount(["reviews_$local_sort as reviews_count" => function ($internal_query) {
                        $internal_query;
                    }])
                    ->orderBy('avg_rating', 'desc')->orderBy('reviews_count', 'desc')->orderBy('min_rate', 'asc');
            default:
                return $query
                    ->withCount([
                        'deposits AS min_rate' => function ($internal_query) {
                            $internal_query->select(DB::raw("MIN(percent) as min"))->where('percent', '>', 0);
                        },
                    ])
                    ->withCount([
                        'reviews AS avg_rating' => function ($internal_query) {
                            $internal_query->select(DB::raw("AVG(stars) as average"));
                        },
                    ])->withCount(['reviews as reviews_count' => function ($internal_query) {
                        $internal_query;
                    }])
                    ->orderBy('avg_rating', 'desc')->orderBy('reviews_count', 'desc');
        }
    }

    public function scopeCountProducts($query, $subdomain)
    {
        return $query
            ->withCount(['credits as credits_count' => function ($internal_query) use ($subdomain) {
                $internal_query->active()->matchSubdomain($subdomain);
            }])
            ->withCount(['consumers as consumers_count' => function ($internal_query) use ($subdomain) {
                $internal_query->active()->matchSubdomain($subdomain);
            }])
            ->withCount(['microloans as microloans_count' => function ($internal_query) use ($subdomain) {
                $internal_query->active()->matchSubdomain($subdomain);
            }])
            ->withCount(['cards as cards_count' => function ($internal_query) use ($subdomain) {
                $internal_query->active()->matchSubdomain($subdomain);
            }])
            ->withCount(['deposits as deposits_count' => function ($internal_query) use ($subdomain) {
                $internal_query->active()->matchSubdomain($subdomain);
            }])
            ->withCount(['credits as credits_count_all' => function ($internal_query) use ($subdomain) {
                $internal_query->matchSubdomain($subdomain)->where('active', 1)->orWhere('direct_access', 1);
            }])
            ->withCount(['consumers as consumers_count_all' => function ($internal_query) use ($subdomain) {
                $internal_query->matchSubdomain($subdomain)->where('active', 1)->orWhere('direct_access', 1);
            }])
            ->withCount(['microloans as microloans_count_all' => function ($internal_query) use ($subdomain) {
                $internal_query->matchSubdomain($subdomain)->where('active', 1)->orWhere('direct_access', 1);
            }])
            ->withCount(['cards as cards_count_all' => function ($internal_query) use ($subdomain) {
                $internal_query->matchSubdomain($subdomain)->where('active', 1)->orWhere('direct_access', 1);
            }])
            ->withCount(['deposits as deposits_count_all' => function ($internal_query) use ($subdomain) {
                $internal_query->matchSubdomain($subdomain)->where('active', 1)->orWhere('direct_access', 1);
            }]);
    }

    /* If the creditor does not have anymore a license, we do not show those products on the front */
    public function scopeMatchCreditorsWithLicense($query)
    {
        return
            $query->where('license_revoked', 0);
    }

    public function scopeMatchHasProducts($query, $productType)
    {
        if($productType){
            if($productType == 'credits_refinancing') return $query->whereHas('credits_refinancing')->orWhereHas('consumers_refinancing');
            return $query->whereHas($productType);
        }
    }

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y');
    }

    public function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y H:i');
    }

    public function regions()
    {
        return $this->hasMany('App\Interceptions\CreditorsRegionsInterception');
    }

    public function extraPhones()
    {
        return $this->hasMany('App\Models\Creditors\CreditorsPhone')->select('phone');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('active', 1)->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug');
    }


    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('active', 1)->select('id', 'creditor_id', 'user_id', 'user_type', 'item_id', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }


    public function getLowestCreditRate()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->min('percent');
    }

    /* DEPOSITS */
    /* ====================================================================================================================================== */
    public function deposits()
    {
        return $this->hasMany('App\Models\Products\Deposits\Deposit');
    }
    /* Deposits reviews */
    public function reviews_deposits()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'deposits')->where('active', 1)->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at');
    }
    /* MICROLOANS */
    /* ====================================================================================================================================== */
    public function microloans()
    {
        return $this->hasMany('App\Models\Products\Credits\Microloan');
    }

    /* Microloans reviews */
    public function reviews_microloans()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'microloans')->where('active', 1)->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at');
    }

    /* CONSUMERS */
    /* ====================================================================================================================================== */
    public function consumers()
    {
        return $this->hasMany('App\Models\Products\Credits\Consumer');
    }

    public function consumers_refinancing()
    {
        return $this->hasMany('App\Models\Products\Credits\Consumer')->where('is_refinancing', true);
    }

    public function reviews_consumers()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'consumers')->where('active', 1)->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at');
    }

    /* CREDITS */
    /* ====================================================================================================================================== */

    public function credits()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit');
    }
    public function credits_mortgage()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'mortgage');
    }

    public function credits_refinancing()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'refinancing')->orWhere('is_refinancing', true);
    }

    public function credits_flats()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'flats');
    }

    public function credits_rooms()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'rooms');
    }
    public function credits_shared()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'shared');
    }
    public function credits_house()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'house');
    }
    public function credits_parcels()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'parcel');
    }
    public function credits_apartments()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'apartments');
    }
    public function credits_townhouse()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'townhouse');
    }
    public function credits_commercial()
    {
        return $this->hasMany('App\Models\Products\Credits\Credit')->where('active', 1)->where('type_slug', 'pledge')->where('pledge_slug', 'commercial');
    }

    /* Credits reviews by type_slug*/
    public function reviews_credits_mortgage()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'mortgage');
        });
    }
    public function reviews_credits_refinancing()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'refinancing');
        });
    }

    public function reviews_credits_flats()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'flats');
        });
    }
    public function reviews_credits_rooms()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'rooms');
        });
    }
    public function reviews_credits_shared()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'shared');
        });
    }
    public function reviews_credits_house()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'house');
        });
    }
    public function reviews_credits_parcels()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'parcel');
        });
    }
    public function reviews_credits_apartments()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'apartments');
        });
    }
    public function reviews_credits_townhouse()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'townhouse');
        });
    }
    public function reviews_credits_commercial()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'credits')->where('active', 1)->whereHas('credit', function ($q) {
            $q->where('type_slug', 'pledge')->where('pledge_slug', 'commercial');
        });
    }


    /* CARDS */
    /* ====================================================================================================================================== */
    public function cards()
    {
        return $this->hasMany('App\Models\Products\Cards\Card');
    }
    public function cards_debit()
    {
        return $this->hasMany('App\Models\Products\Cards\Card')->where('active', 1)->where('type', 'debit');
    }
    public function cards_credit()
    {
        return $this->hasMany('App\Models\Products\Cards\Card')->where('active', 1)->where('type', 'credit');
    }

    /* Cards reviews by type_slug*/

    public function reviews_cards_credit()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'cards')->where('active', 1)->whereHas('card', function ($q) {

            $q->where('type', '=', 'credit');
        });
    }

    public function reviews_cards_debit()
    {
        return $this->hasMany('App\Models\Reviews\Review')->where('type_slug', 'cards')->where('active', 1)->whereHas('card', function ($q) {

            $q->where('type', '=', 'debit');
        });
    }

    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditorsRegionsInterception')->select('region_id AS value');
    }

    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditorsSettlementsInterception')->select('aoid');
    }
}
