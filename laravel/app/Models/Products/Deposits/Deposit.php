<?php

namespace App\Models\Products\Deposits;

use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
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

    public function scopeMatchCreditorId($query, $creditorId)
    {
        if ($creditorId) {
            return $query
                ->where('creditor_id', $creditorId);
        }
    }

    public function scopeOrderByFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        $local_sort = $sort ?? 'default';
        switch ($sort) {
            case 'creditor':
                return $query->orderBy('name', 'asc');
            case 'percent':
                return $query->orderBy('percent', 'asc');
            case 'sum':
                return $query->orderBy('sum_min', 'desc');
            case 'default':
            default:
                return $query;
        }
    }
    public function scopeMatchFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        switch ($sort) {
            case 'creditor':return $query->withCount([
                'creditor AS name' => function ($internal_query) {
                    $internal_query->select(DB::raw("name"));
                },
            ]);
            default:
                return $query;
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

    public function scopeMatchTypes($query, $types)
    {
        if ($types) {
            return $query->whereHas('types', function ($query) use ($types){
                $query->whereIn('deposit_type_id', $types);
            });
        }
    }

    public function scopeMatchCapitalizations($query, $capitalizations)
    {
        if ($capitalizations) {
            return $query->whereHas('capitalizations', function ($query) use ($capitalizations){
                $query->whereIn('capitalization_id', $capitalizations);
            });
        }
    }

    public function scopeMatchInterestPayments($query, $interestPayments)
    {
        if ($interestPayments) {
            return $query->whereHas('interestPayments', function ($query) use ($interestPayments){
                $query->whereIn('interest_payment_id', $interestPayments);
            });
        }
    }

    public function scopeSelectFields($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'advantages', 'requirements', 'documents', 'percent_min', 'percent_max', 'percent', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'type_id', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsCommonUnion($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'special','special_priority_id', 'special_link', 'active', 'created_at');
    }

    public function scopeAddTypeProductColumn($query)
    {
        return $query->addSelect(DB::raw("'deposits' as type_product"));
    }

    public function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y H:i');
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

    public function scopeMatchType($query, $type_id)
    {
        if ($type_id && $type_id !== 'default') {
            $query
                ->where('type_id', $type_id);
        }
    }

    public function scopeMatchAmount($query, $amount)
    {
        if ($amount && $amount !== 'default') {
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
        if ($period && $period !== 'default') {
            $query
                ->where('days_max', '>=', (int)$period)
                ->where('days_min', '<=', (int)$period)->orWhere('days_min', null);
            /* Add here logic to search by years, months or days. Add on front filter */
        }
    }

    public function scopeMatchCurrencies($query, $currencies)
    {
        if ($currencies) {
            $query->whereIn('currency_id', $currencies);
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

    public function regions()
    {
        return $this->hasMany('App\Interceptions\DepositsRegionsInterception');
    }

    public function types()
    {
        return $this->hasMany('App\Interceptions\DepositsTypesInterception');
    }

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'genitive', 'dative', 'image', 'thumbnail', 'phone', 'type_slug', 'license_revoked', 'direct_access');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'deposits')->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }
    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'deposits')->select('id', 'creditor_id', 'item_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }
    public function rateTables()
    {
        return $this->hasMany('App\Models\Products\Deposits\DepositsRatesTable');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currencies\Currency')->select('id', 'title', 'iso_name', 'symbol');
    }

    public function capitalizations()
    {
        return $this->hasMany('App\Interceptions\DepositsCapitalizationsInterception');
    }

    public function interestPayments()
    {
        return $this->hasMany('App\Interceptions\DepositsInterestPaymentsInterception');
    }


    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\DepositsRegionsInterception')->select('region_id AS value');
    }


    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\DepositsSettlementsInterception')->select('aoid');
    }
    public function typesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\DepositsTypesInterception')->select('deposit_type_id AS value');
    }

    public function capitalizationAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\DepositsCapitalizationsInterception')->select('capitalization_id AS value');
    }
    public function interestPaymentsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\DepositsInterestPaymentsInterception')->select('interest_payment_id AS value');
    }
}
