<?php

namespace App\Models\Products\Credits;

use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Microloan extends Model
{

    public function scopeActive($query)
    {
        return $query
            ->where('active', 1);
    }

    public function scopeMatchDisplayBusinessLogic($query, $disallowDisplayBusinessLogic = false)
    {
        if (!$disallowDisplayBusinessLogic) {
            return
                $query->whereHas('creditor', function ($query) {
                    $query->where('active', 1)->where('license_revoked', 0)->where('direct_access', 0);
                })->where('active', 1);
        }
        return $query->where('active', 1)
            ->orWhereHas('creditor', function ($query) {
                $query->where('license_revoked', 1)->orWhere('direct_access', 1);
            })->where('direct_access', 1);
    }

    public function scopeOrderByFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        $local_sort = $sort ?? 'default';
        switch ($sort) {
            case 'percent':
            case 'monthly_payment':
                return $query->orderBy('percent', 'asc');
            case 'sum':
                return $query->orderBy('sum_max', 'desc');
            case 'creditor':
                return $query->orderBy('name', 'asc');
            case 'default':
            default:
                return $query;
        }
    }
    public function scopeMatchFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        switch ($sort) {
            case 'creditor':
            default:
                return $query
                    ->withCount([
                        'creditor AS name' => function ($internal_query) {
                            $internal_query->select(DB::raw("name"));
                        },
                    ]);
        }
    }

    public function scopeActiveOrAccessibleByDirectLink($query)
    {
        return $query
            ->where('active', 1)->orWhere('direct_access', 1);
    }

    public function scopeOrderByDate($query)
    {
        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopeOrderByBOCLogic($query)
    {
        return $query
            ->orderBy('special_priority_id', 'desc');
    }

    public function scopeMatchBOCLogic($query, $isBOCLogic)
    {
        if ($isBOCLogic) {
            return $query
                ->where('special_priority_id', 3);
        }
    }

    public function scopeOrderByDateConditional($query, $order)
    {
        if ($order) {
            return $query
                ->orderBy('created_at', $order)->orderBy('updated_at', 'desc');;
        }
        return $query
            ->orderBy('created_at', 'desc')->orderBy('updated_at', 'desc');
    }

    public function scopeOrderByLessPercent($query)
    {
        return $query;
    }

    public function scopeOrderByPercent($query, $order)
    {
        if ($order) {
            return $query
                ->orderBy('percent', $order);
        }

        return $query
            ->orderBy('percent', 'asc');
    }

    public function scopeOrderByCountReviews($query)
    {
        return $query
            ->withCount([
                'reviews AS avg_rating' => function ($internal_query) {
                    $internal_query->select(DB::raw("AVG(stars) as average"));
                },
            ])->withCount(['reviews as reviews_count' => function ($internal_query) {
                $internal_query;
            }])
            ->orderBy('reviews_count', 'desc')->orderBy('avg_rating', 'desc');
    }

    public function scopeCountReviews($query)
    {
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
    }

    public function scopeMatchCreditorId($query, $creditorId)
    {
        if ($creditorId) {
            return $query
                ->where('creditor_id', $creditorId);
        }
    }
    public function scopeMatchProductId($query, $productId)
    {
        if ($productId) {
            return $query
                ->where('id', $productId);
        }
    }
    public function scopeMatchMinAmount($query, $amount)
    {
        if ($amount) {
            return $query
                ->where('sum_min', '>=', (int)$amount);
        }
    }

    public function scopeMatchMaxAmount($query, $amount)
    {
        if ($amount) {
            return $query
                ->where('sum_max', '<=', (int)$amount);
        }
    }

    public function scopeMatchMaxPercent($query, $percent)
    {
        if ($percent) {
            return $query
                ->where('percent', '<=', $percent);
        }
    }

    public function scopeMatchCreditorsIds($query, $creditorsIds)
    {
        if ($creditorsIds) {
            return  $query->whereIn('creditor_id', $creditorsIds);
        }
    }
    public function scopeMatchTitleLike($query, $title)
    {
        if ($title) {
            return  $query->where('title', 'like', "%{$title}%");
        }
    }

    public function scopeMatchCreditorSlugs($query, $slugsOrSlug)
    {
        if (($slugsOrSlug && $slugsOrSlug != 'none' && $slugsOrSlug != 'default')) {
            $slugs = [];
            if (gettype($slugsOrSlug) == 'array') $slugs = $slugsOrSlug;
            else $slugs[0] = $slugsOrSlug;

            if (in_array('all', $slugs)) return  $query;

            return
                $query->whereHas('creditor', function ($query) use ($slugs) {
                    $query->whereIn('type_slug', $slugs);
                });
        }
    }

    /* If the creditor does not have anymore a license, we do not show those products on the front */
    public function scopeMatchCreditorsWithLicense($query)
    {
        return
            $query->whereHas('creditor', function ($query) {
                $query->where('license_revoked', 0);
            });
    }

    public function scopeMatchCreditorsWithoutDirectAccess($query)
    {
        return
            $query->whereHas('creditor', function ($query) {
                $query->where('direct_access', 0);
            });
    }

    public function scopeMatchNoDirectAccess($query)
    {
        return
            $query->where('direct_access', 0);
    }

    public function scopeMatchAmount($query, $amount)
    {
        if ($amount && $amount != 'none') {
            $query
                ->where('sum_max', '>=', (int)$amount)
                ->where('sum_min', '<=', (int)$amount)->orWhere('sum_min', null)->orWhere('sum_min', 0);
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

    public function scopeMatchSpecialState($query, $special)
    {
        $boolean_state = $special == 'yes' ? 1 : 0;
        if ($special) {
            $query
                ->where('special', $boolean_state);
        }
    }

    public function scopeMatchPeriod($query, $period)
    {
        if ($period && $period != 'none') {
            $query
                ->where('days_max', '>=', $period)
                ->where('days_min', '<=', $period)->orWhere('days_min', null);
            /* Add here logic to search by years, months or days. Add on front filter */
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

    public function scopePaginateOrGet($query, $pagination, $xnumber, $isRandom = false)
    {
        $all_products = 10000000000000000000000000;
        if ($pagination) {
            return $query->paginate($xnumber == 'all'? $all_products : $xnumber);
        } else if($isRandom){
            return $query->inRandomOrder()->limit($xnumber == 'all'? $all_products : $xnumber)->get();
        } else {
            return $query->take($xnumber == 'all'? $all_products : $xnumber)->get();
        }
    }

    public function scopeSelectFields($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'purpose', 'advantages', 'issuance', 'requirements', 'documents', 'percent_min', 'percent_max', 'percent', 'sum_min', 'sum_max', 'days_max', 'days_min', 'updated_at', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsForUnions($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'percent', 'percent_min', 'percent_max', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'advantages', 'requirements', 'documents', 'created_at')
            ->addSelect(DB::raw('null as type_slug'))
            ->addSelect(DB::raw('null as pledge_slug'))
            ->addSelect('special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsCommonUnion($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'special','special_priority_id', 'special_link', 'active', 'created_at');
    }

    public function scopeAddTypeProductColumn($query)
    {
        return $query->addSelect(DB::raw("'microloans' as type_product"));
    }

    public function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y H:i');
    }

    public function regions()
    {
        return $this->hasMany('App\Interceptions\MicroloansRegionsInterception');
    }

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'genitive', 'dative', 'image', 'thumbnail', 'phone', 'type_slug', 'license_revoked', 'direct_access');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'microloans')->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }

    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'microloans')->select('id', 'creditor_id', 'item_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }

    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\MicroloansRegionsInterception')->select('region_id AS value');
    }

    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\MicroloansSettlementsInterception')->select('aoid');
    }

    public function provisionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\MicroloansProvisionsInterception')->select('provision_id AS value');
    }
}
