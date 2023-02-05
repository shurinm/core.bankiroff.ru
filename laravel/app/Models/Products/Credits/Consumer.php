<?php

namespace App\Models\Products\Credits;

use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;

class Consumer extends Model
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

    public function scopeOrderByLessPercent($query)
    {
        return $query
            ->orderBy('percent', 'asc');
    }

    /* Please check comment on ProductsController with COMMENT_ID: COMMENT_1_POPULARS */
    public function scopeOrderByCountReviews($query)
    {
        return $query
            ->withCount([
                'reviews AS avg_rating' => function ($internal_query) {
                    $internal_query->select(DB::raw("AVG(stars) as average"));
                },
            ])
            ->withCount(['reviews as reviews_count' => function ($internal_query) {
                $internal_query;
            }])
            ->orderBy('reviews_count', 'desc')->orderBy('avg_rating', 'desc');
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
            case 'default':
            default:
                return $query->orderBy('percent', 'asc');
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

    public function scopeMatchAmount($query, $amount)
    {
        if ($amount && $amount != 'none') {
            $query
                ->where('sum_max', '>=', (int)$amount)
                ->where('sum_min', '<=', (int)$amount)->orWhere('sum_min', null)->orWhere('sum_min', 0);
        }
    }

    public function scopeMatchPeriod($query, $period)
    {
        if ($period && $period != 'none') {
            $query
                ->where('years_max', '>=', $period)
                ->where('years_min', '<=', $period)->orWhere('years_min', null);
            /* Add here logic to search by years, months or days. Add on front filter */
        }
    }
    public function scopeMatchPercent($query, $percent)
    {
        if ($percent) {
            $query
                ->where('percent', '<=', $percent);
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


    public function scopeMatchNoInsurance($query, $noInsurance)
    {
        if ($noInsurance) {
            $query
                ->where('no_insurance', 1)->orWhereHas('insurances', function ($query) {
                    $query->whereIn('insurance_id', [1]);
                });;
        }
    }

    public function scopeMatchNoPledge($query, $noPledge)
    {
        if ($noPledge) {
            $query
                ->where('no_pledge', 1);
        }
    }
    public function scopeMatchNoProof($query, $noProof)
    {
        if ($noProof) {
            $query
                ->where('no_proof', 1);
        }
    }

    public function scopeMatchThreeDayReview($query, $threeDay)
    {
        if ($threeDay) {
            $query
                ->where('three_day_review', 1);
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
    
    public function scopeMatchPurposes($query, $purposeOrPurposes)
    {
        if (($purposeOrPurposes && $purposeOrPurposes != 'none' && $purposeOrPurposes != 'default')) {
            $purposes = [];
            if (gettype($purposeOrPurposes) == 'array') $purposes = $purposeOrPurposes;
            else $purposes[0] = $purposeOrPurposes;

            if (in_array('all', $purposes)) return  $query;

            return
                $query->whereIn('purpose_id', $purposes);
        }
    }

    public function scopeMatchPercentMin($query, $percent)
    {
        if ($percent) {
            return $query
                ->where('percent_min', '>=', (int)$percent);
        }
    }

    public function scopeMatchPercentMax($query, $percent)
    {
        if ($percent) {
            return $query
                ->where('percent_max', '<=', (int)$percent);
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


    public function scopeMatchExtraCategory($query, $category)
    {
        if ($category && $category == 'refinancing') {
            return $query
                ->whereHas('extraCategories', function ($query) use ($category) {
                    $query->where('type_slug', $category);
                })->orWhere('is_refinancing', true);
        } else if ($category) {
            return $query
                ->whereHas('extraCategories', function ($query) use ($category) {
                    $query->where('type_slug', $category);
                });
        }
    }

    public function scopeMatchExtraCategoryWithPledge($query, $category, $pledge)
    {
        return $query
            ->whereHas('extraCategories', function ($query) use ($category, $pledge) {
                $query->where('type_slug', $category)->where('pledge_slug', $pledge);
            });
    }

    public function scopeMatchCreditRefinancingAndExtraSlugs($query, $slug, $extraSlugs)
    {
        if ($slug && $slug == 'refinancing') {
            if ($extraSlugs && count($extraSlugs) > 0) {
                if(!in_array('default', $extraSlugs) && !in_array('consumers', $extraSlugs)){
                    // dd('here2');
                    /*We do not return this product in case there is an array of extraSlugs and consumers type is not sent*/
                    return $query->where('is_refinancing', 2);
                    }
            }

            return $query->where('is_refinancing', true)->orWhereHas('extraCategories', function ($query) use ($slug) {
                $query->where('type_slug', $slug);
            });
        }
    }

    public function scopeMatchExtraCategoriesWithPledgeAndRealState($query, $category, $pledge)
    {
        return $query
            ->whereHas('extraCategories', function ($query) use ($category, $pledge) {
                $query->where('type_slug', $category)->whereIn('pledge_slug', [$pledge, 'realstate']);
            });
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
        ->select('id', 'creditor_id', 'title', 'advantages', 'percent','sum_min','sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'purpose_id', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsForUnions($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'percent', 'percent_min', 'percent_max', 'sum_min', 'sum_max', 'years_max', 'years_min', 'months_max', 'months_min', 'days_max', 'days_min', 'advantages', 'requirements', 'documents', 'created_at', 'type_slug', 'pledge_slug', 'is_refinancing', 'special', 'special_link', 'special_priority_id');
    }

    public function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y H:i');
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
        return $query->addSelect(DB::raw("'consumers' as type_product"));
    }

    public function regions()
    {
        return $this->hasMany('App\Interceptions\ConsumersRegionsInterception');
    }

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'genitive', 'dative', 'image', 'thumbnail', 'phone', 'type_slug', 'license_revoked', 'direct_access');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'consumers')->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }

    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'consumers')->select('id', 'creditor_id', 'item_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }

    public function histories()
    {
        return $this->hasMany('App\Interceptions\ConsumersHistoriesInterception')->select('credit_history_id');
    }

    public function proofs()
    {
        return $this->hasMany('App\Interceptions\ConsumersProofsInterception')->select('credit_proof_id');
    }
    public function occupations()
    {
        return $this->hasMany('App\Interceptions\ConsumersOccupationsInterception')->select('credit_occupation_id');
    }

    public function insurances()
    {
        return $this->hasMany('App\Interceptions\ConsumersInsurancesInterception');
    }

    public function rateTables()
    {
        return $this->hasMany('App\Models\Products\Credits\ConsumersRatesTable');
    }

    public function extraCategories()
    {
        return $this->hasMany('App\Models\Products\Credits\ConsumersExtraCategory');
    }

    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersRegionsInterception')->select('region_id AS value');
    }

    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersSettlementsInterception')->select('aoid');
    }

    public function historiesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersHistoriesInterception')->select('credit_history_id AS value');
    }

    public function proofsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersProofsInterception')->select('credit_proof_id AS value');
    }

    public function occupationsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersOccupationsInterception')->select('credit_occupation_id AS value');
    }
    public function insurancesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\ConsumersInsurancesInterception')->select('insurance_id AS value');
    }
}
