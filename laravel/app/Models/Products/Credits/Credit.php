<?php

namespace App\Models\Products\Credits;

use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;

class Credit extends Model
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
        // return $query->orderBy('updated_at', 'desc');
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

    public function scopeOrderByFilters($query, $sort, $isKeyValue)
    {
        if ($isKeyValue) return $query;
        switch ($sort) {
            case 'creditor':
                return $query->orderBy('name', 'asc');
            case 'monthly_payment':
                return $query->orderBy('percent', 'asc');
            case 'percent':
                return $query->orderBy('percent', 'asc');
            case 'default':
            default:
                return $query->orderBy('updated_at', 'desc');
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

    public function scopeOrderByLessPercent($query)
    {
        return $query
            ->orderBy('percent', 'asc');
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

    public function scopeMatchCreditors($query, $request)
    {
        $creditors_array = null;
        if ($request->creditor && $request->creditor != 'default') {
            $new_array = [];
            $new_array[0] = $request->creditor;
            $creditors_array = $new_array;
        } else if ($request->creditors  && $request->creditors != 'default') {
            $creditors_array = $request->creditors;
        }
        if ($creditors_array) {
            $query
                ->whereIn('creditor_id', $creditors_array);
        }
    }

    public function scopeMatchTitleLike($query, $title)
    {
        if ($title) {
            return  $query->where('title', 'like', "%{$title}%");
        }
    }

    public function scopeMatchCreditRefinancingAndExtraSlugs($query, $slug, $extraSlugs)
    {
        if ($slug && $slug == 'refinancing') {
            if ($extraSlugs && count($extraSlugs) > 0) {
                if(!in_array('default', $extraSlugs) || count($extraSlugs) > 0) {
                    // dd('here');
                    return  $query->where('is_refinancing', true)->whereIn('type_slug', $extraSlugs);
                }
            }

            return $query->where('is_refinancing', true)->orWhereHas('extraCategories', function ($query) use ($slug) {
                $query->where('type_slug', $slug);
            });
        }
    }

    public function scopeMatchCreditTypeSlug($query, $slug)
    {
        if ($slug && $slug == 'refinancing') {
            return $query
                ->where(function ($query) use ($slug) {
                    $query->where('type_slug', $slug)->orWhere('is_refinancing', true);
                })->orWhereHas('extraCategories', function ($query) use ($slug) {
                    $query->where('type_slug', $slug);
                });
        } else if ($slug) {
            return $query
                ->where('type_slug', $slug)->where('is_refinancing', 0)->orWhereHas('extraCategories', function ($query) use ($slug) {
                    $query->where('type_slug', $slug);
                });
        }
    }

    public function scopeMatchTypes($query, $types)
    {
        if ($types && in_array("refinancing", $types)) {
            return $query->whereIn('type_slug', $types)->orWhere('is_refinancing', true);
        } else if ($types) {
            return $query->whereIn('type_slug', $types);
        }
    }

    public function scopeMatchType($query, $type)
    {
        if ($type && $type != 'all') {
            $types = ['mortgage', 'refinancing'];
        } else {
            $types = ['pledge', 'refinancing'];
        }
        return $query->whereIn('type_slug', $types);
    }

    public function scopeMatchPledgeSlugs($query, $slugs)
    {
        if ($slugs) {
            error_log("scopeMatchPledgeSlugs");
            return $query->whereIn('pledge_slug', $slugs);
        }
    }

    public function scopeMatchCreditTypeAndPledgeSlug($query, $slug, $pledge_slug)
    {
        if ($slug && $pledge_slug) {
            return $query
                /*Use this to show all credits with realstate pledge_slug */
                // ->where('type_slug', 'pledge')->whereIn('pledge_slug', [$pledge_slug, "realstate"])
                ->where('is_refinancing', 0)->whereHas('pledges', function ($query) use ($pledge_slug) {
                    $query->whereIn('credit_pledge_slug', [$pledge_slug, "realstate"]);
                })->orWhereHas('extraCategories', function ($query) use ($slug, $pledge_slug) {
                    $query->where('type_slug', $slug)->where('pledge_slug', $pledge_slug);
                });
        }
    }

    public function scopeMatchCreditsRealState($query)
    {
        $realstate_pledge_slugs = ['realstate', 'flats', 'rooms', 'shared', 'commercial', 'house', 'townhouse', 'parcels', 'apartments'];
        return $query
            ->where('type_slug', 'pledge')->whereIn('pledge_slug', $realstate_pledge_slugs)->where('is_refinancing', 0)->orWhereHas('extraCategories', function ($query) use ($realstate_pledge_slugs) {
                $query->where('type_slug', 'pledge')->whereIn('pledge_slug', $realstate_pledge_slugs);
            });
    }

    public function scopeMatchExtraCategory($query, $category)
    {
        return $query
            ->whereHas('extraCategories', function ($query) use ($category) {
                $query->where('type_slug', $category);
            });
    }

    public function scopeMatchTypeAndPledgeSlugAuto($query, $slug)
    {
        if ($slug) {
            switch ($slug) {
                case 'flats':
                case 'rooms':
                case 'shared':
                case 'house':
                case 'parcels':
                case 'apartments':
                case 'townhouse':
                case 'commercial':
                case 'auto':
                    return $query->where('type_slug', 'pledge')->where('pledge_slug', $slug);
                case 'mortgage':
                case 'refinancing':
                    return $query->where('type_slug', $slug);
            }
        }
    }

    public function scopeMatchAmount($query, $amount)
    {
        if ($amount && $amount != 'none') {
            return $query
                ->where('sum_max', '>=', (int)$amount)
                ->where('sum_min', '<=', (int)$amount)->orWhere('sum_min', null)->orWhere('sum_min', 0);
        }
    }

    public function scopeMatchPeriod($query, $period, $slug = null)
    {
        if ($period && $period != 'none') {
            switch ($slug) {
                case "pledge":
                case "refinancing":
                    return $query
                        ->where('months_max', '>=', $period)
                        ->where('months_min', '<=', $period)->orWhere('months_min', null);
                case "mortgage":
                default:
                    return $query
                        ->where('years_max', '>=', $period)
                        ->where('years_min', '<=', $period)->orWhere('years_min', null);
            }
            /* Add here logic to search by years, months or days. Add on front filter */
        }
    }

    public function scopeMatchHistories($query, $request)
    {
        $histories_array = null;
        if ($request->history && $request->history != 'default') {
            $new_array = [];
            $new_array[0] = $request->history;
            $histories_array = $new_array;
        } else if ($request->histories) {
            $histories_array = $request->histories;
        }
        if ($histories_array) {
            return $query
                ->whereHas('histories', function ($query) use ($histories_array) {
                    $query->whereIn('credit_history_id', $histories_array);
                });
        }
    }
    public function scopeMatchProofs($query, $request)
    {
        $proofs_array = null;
        if ($request->proof && $request->proof != 'default') {
            $new_array = [];
            $new_array[0] = $request->proof;
            $proofs_array = $new_array;
        } else if ($request->proofs) {
            $proofs_array = $request->proofs;
        }
        if ($proofs_array) {
            return  $query
                ->whereHas('proofs', function ($query) use ($proofs_array) {
                    $query->whereIn('credit_proof_id', $proofs_array);
                });
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
    public function scopeMatchOccupations($query, $request)
    {

        $occupations_array = null;
        if ($request->occupation && $request->occupation != 'default') {
            $new_array = [];
            $new_array[0] = $request->occupation;
            $occupations_array = $new_array;
        } else if ($request->occupations) {
            $occupations_array = $request->occupations;
        }
        if ($occupations_array) {
            return $query
                ->whereHas('occupations', function ($query) use ($occupations_array) {
                    $query->whereIn('credit_occupation_id', $occupations_array);
                });
        }
        // else {
        //     return $query
        //     ->doesntHave('occupations')
        //     ->orWhereHas('occupations',function($query) {
        //         $query->whereIn('credit_occupation_id', [1]);
        //     });
        // }
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

    public function scopeMatchPercentMin($query, $percent)
    {
        if ($percent) {
            return $query
                ->where('percent_min', '>=', floatval($percent));
        }
    }

    public function scopeMatchPercentMax($query, $percent)
    {
        if ($percent) {
            return $query
                ->where('percent_max', '<=', floatval($percent));
        }
    }

    public function scopeMatchInitialPaymentMin($query, $initialPayment)
    {
        if ($initialPayment) {
            return $query
                ->where('initial_payment', '>=', floatval($initialPayment))->orWhere('initial_payment', null);
        }
    }

    public function scopeMatchInitialPaymentMax($query, $initialPayment)
    {
        if ($initialPayment) {
            return $query
                ->where('initial_payment', '<=', floatval($initialPayment))->orWhere('initial_payment', null);
        }
    }

    public function scopeMatchNoInsurance($query, $noInsurance)
    {
        if ($noInsurance) {
            $query
                ->whereHas('insurances', function ($query) {
                    $query->whereIn('insurance_id', [1]);
                });;
        }
    }

    public function scopeMatchPercent($query, $percent)
    {
        if ($percent) {
            $query
                ->where('percent', '<=', $percent);
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
            ->select('id', 'creditor_id', 'title', 'advantages', 'documents', 'percent', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'type_slug', 'pledge_slug', 'updated_at', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsForUnions($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'percent', 'percent_min', 'percent_max', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'advantages', 'requirements', 'documents', 'created_at', 'type_slug', 'pledge_slug', 'is_refinancing', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsForUnionWithMicroloans($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'percent', 'percent_min', 'percent_max', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'advantages', 'requirements', 'documents', 'created_at', 'type_slug', 'pledge_slug', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsCommonUnion($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'special','special_priority_id', 'special_link', 'active', 'created_at');
    }

    public function scopeAddTypeProductColumn($query)
    {
        return $query->addSelect(DB::raw("'credits' as type_product"));
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

    public function regions()
    {
        return $this->hasMany('App\Interceptions\CreditsRegionsInterception');
    }

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'genitive', 'dative', 'image', 'thumbnail', 'type_slug', 'phone', 'license_revoked', 'direct_access');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'credits')->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }

    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'credits')->select('id', 'creditor_id', 'item_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }

    public function histories()
    {
        return $this->hasMany('App\Interceptions\CreditsHistoriesInterception')->select('credit_history_id');
    }

    public function proofs()
    {
        return $this->hasMany('App\Interceptions\CreditsProofsInterception')->select('credit_proof_id');
    }
    public function occupations()
    {
        return $this->hasMany('App\Interceptions\CreditsOccupationsInterception')->select('credit_occupation_id');
    }

    public function insurances()
    {
        return $this->hasMany('App\Interceptions\CreditsInsurancesInterception');
    }

    public function pledges()
    {
        return $this->hasMany('App\Interceptions\CreditsPledgesSlugsInterception');
    }
    public function rateTables()
    {
        return $this->hasMany('App\Models\Products\Credits\CreditsRatesTable');
    }
    public function extraCategories()
    {
        return $this->hasMany('App\Models\Products\Credits\CreditsExtraCategory');
    }

    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsRegionsInterception')->select('region_id AS value');
    }

    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsSettlementsInterception')->select('aoid');
    }

    public function historiesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsHistoriesInterception')->select('credit_history_id AS value');
    }

    public function proofsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsProofsInterception')->select('credit_proof_id AS value');
    }

    public function occupationsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsOccupationsInterception')->select('credit_occupation_id AS value');
    }
    public function pledgesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsPledgesSlugsInterception')->select('credit_pledge_slug AS value');
    }
    public function insurancesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CreditsInsurancesInterception')->select('insurance_id AS value');
    }
}
