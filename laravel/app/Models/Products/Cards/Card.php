<?php

namespace App\Models\Products\Cards;

use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
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
        switch ($sort) {
            case 'creditor':
                return $query->orderBy('name', 'asc');
            case 'card_limit':
                return $query->orderBy('card_limit', 'desc');
            case 'grace_period':
                return $query->orderBy('grace_period', 'desc');
            case 'percent':
                return $query->orderBy('percent', 'asc');
            case 'cashback':
                return $query->orderBy('cash_back', 'desc');
            case 'year_price_max':
                return $query->orderBy('year_price_max', 'asc');
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
    public function scopeMatchMinPriceAmount($query, $amount)
    {
        if ($amount) {
            return $query
                ->where('year_price_min', '>=', (int)$amount);
        }
    }

    public function scopeMatchMaxPriceAmount($query, $amount)
    {
        if ($amount) {
            return $query
                ->where('year_price_max', '<=', (int)$amount);
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
            return  $query->whereIn('type', $types);
        }
    }

    public function scopeMatchMainCategories($query, $mainCategories)
    {
        if(!$mainCategories || count($mainCategories) == 0) return $query;
        // dd('here');
        foreach ($mainCategories as $key => $value) {
            switch ($value) {
                case 'visa':
                    $text_to_match = "Visa";
                    break;
                case 'mastercard':
                    $text_to_match = "MasterCard";
                    break;
                case 'mir':
                    $text_to_match = "МИР";
                    break;
                case 'americanExpress':
                    $text_to_match = "American Express";
                    break;
                case 'maestro':
                    $text_to_match = "Maestro";
                    break;
              
            }
            $query->whereHas('categories', function ($query) use ($text_to_match) {
                $query->whereHas('category', function ($query2) use ($text_to_match) {
                    $query2->where('title', 'like', "%{$text_to_match}%");
                });
            });
        }

        return $query;
    }

    public function scopeMatchHasPercent($query, $hasPercent)
    {
        if ($hasPercent && $hasPercent != 'default') {
            if($hasPercent == 'yes')  return $query->where('percent_min', '!=', 0)->where('percent_min', '!=', null)->orWhere('percent_max', '!=', 0)->where('percent_max', '!=', null)->orWhere('percent', '!=', 0)->where('percent', '!=', null);
            
            return  $query->where(function ($query2) {
                $query2->where('percent', '=', 0)
                    ->orWhereNull('percent');
            })->where(function ($query2) {
                $query2->where('percent_min', '=', 0)
                    ->orWhereNull('percent_min');
            })->where(function ($query2) {
                $query2->where('percent_max', '=', 0)
                    ->orWhereNull('percent_max');
            });
        }
    }

    public function scopeMatchHasCashback($query, $hasCashback)
    {
        if ($hasCashback) {
            if($hasCashback == 1)  return $query->where('cash_back', '!=', 0)->where('cash_back', '!=', null)->where('cash_back_min', '!=', 0)->where('cash_back_min', '!=', null)->where('cash_back_max', '!=', 0)->where('cash_back_max', '!=', null);
            return  $query;
        }
    }

    public function scopeMatchHasContactlessPayment($query, $hasContactlessPayment)
    {
        if ($hasContactlessPayment) {
            if($hasContactlessPayment == 1) {
                return  $query->whereHas('options', function ($query) {
                    $query->whereIn('card_option_id', [1, 2]);
                });;
            } 
            return  $query;
        }
    }

    public function scopeMatchHasInstantCardIssuance($query, $hasInstantCardIssuance)
    {
        if ($hasInstantCardIssuance) {
            if($hasInstantCardIssuance == 1) {
                return  $query->whereHas('options', function ($query) {
                    $query->whereIn('card_option_id', [12, 13]);
                });;
            } 
            return  $query;
        }
    }

    public function scopeMatchHasFreeMaintenance($query, $hasFreeMaintenance)
    {
        if ($hasFreeMaintenance) {
            if($hasFreeMaintenance == 1) {
               return $query->where(function ($query2) {
                    $query2->where('year_price', '=', 0)
                        ->orWhereNull('year_price');
                })->where(function ($query2) {
                    $query2->where('year_price_min', '=', 0)
                        ->orWhereNull('year_price_min');
                })->where(function ($query2) {
                    $query2->where('year_price_max', '=', 0)
                        ->orWhereNull('year_price_max');
                });
            }
            return  $query;
        }
    }

    
    public function scopeMatchIsMultiCurrency($query, $isMultiCurrency)
    {
        if ($isMultiCurrency) {
            if($isMultiCurrency == 1) {
                return  $query->has('currencies', ">", 1);
            } 
            return  $query;
        }
    }

    public function scopeMatchCategories($query, $request)
    {
        $categories_array = null;
        if ($request->category && $request->category != 'default') {
            $new_array = [];
            $new_array[0] = $request->category;
            $categories_array = $new_array;
        } else if ($request->categories) {
            $categories_array = $request->categories;
        }
        if ($categories_array) {
            $query
                ->whereHas('categories', function ($query) use ($categories_array) {
                    $query->whereIn('card_category_id', $categories_array);
                });
        }
    }

    public function scopeMatchBonuses($query, $request)
    {
        $bonuses_array = null;
        if ($request->bonus && $request->bonus != 'default') {
            $new_array = [];
            $new_array[0] = $request->bonus;
            $bonuses_array = $new_array;
        } else if ($request->bonuses) {
            $bonuses_array = $request->bonuses;
        }
        if ($bonuses_array) {
            $query
                ->whereHas('bonuses', function ($query) use ($bonuses_array) {
                    $query->whereIn('card_bonus_id', $bonuses_array);
                });
        }
    }
    public function scopeMatchCurrencies($query, $request)
    {

        $currencies_array = null;
        if ($request->currency && $request->currency != 'default') {
            $new_array = [];
            $new_array[0] = $request->currency;
            $currencies_array = $new_array;
        } else if ($request->currencies) {
            $currencies_array = $request->currencies;
        }
        if ($currencies_array) {
            $query
                ->whereHas('currencies', function ($query) use ($currencies_array) {
                    $query->whereIn('currency_id', $currencies_array);
                });
        }
    }
    
    public function scopeMatchOptions($query, $request)
    {
        $options_array = null;
        if ($request->option && $request->option != 'default') {
            $new_array = [];
            $new_array[0] = $request->option;
            $options_array = $new_array;
        } else if ($request->options && $request->options != 'default') {
            $options_array = $request->options;
        }

        if ($options_array) {
            $query
                ->whereHas('options', function ($query) use ($options_array) {
                    $query->whereIn('card_option_id', $options_array);
                });
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

    public function scopeMatchLimit($query, $cardLimit)
    {
        if ($cardLimit) {
            $query
                ->where('card_limit', '>=', (int)$cardLimit);
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

    public function scopeMatchGracePeriod($query, $gracePeriod)
    {
        
        if ($gracePeriod && $gracePeriod != 'default') {
            $query
                ->where('grace_period', '>=', (int)$gracePeriod);
        }
    }

    public function scopeMatchMinAge($query, $minAge)
    {
        if ($minAge) {
            return $query
                ->where('min_age', '<=', (int)$minAge)->orWhere('min_age', null);
        }
    }

    public function scopeMatchMaxAge($query, $maxAge)
    {
        if ($maxAge) {
            return $query
                ->where('max_age', '>=', (int)$maxAge)->orWhere('max_age', null);
        }
    }

    public function scopeMatchAge($query, $age)
    {
        if ($age) {
            $query
                ->where('min_age', '<=', (int)$age);
        }
    }

    public function scopeMatchCardType($query, $cardType)
    {
        if ($cardType) {
            return $query
                ->where('type', $cardType);
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
        // return $query;
        $query->select('id', 'creditor_id', 'title', 'advantages', 'requirements', 'conditions', 'maintenance', 'expertise','percent_min', 'percent_max',  'percent', 'cash_back_min', 'cash_back_max', 'cash_back', 'image', 'type', 'card_limit', 'grace_period_min', 'grace_period_max','grace_period', 'year_price_min', 'year_price_max', 'type', 'min_age', 'is_not_available', 'special', 'special_link', 'special_priority_id');
    }

    public function scopeSelectFieldsCommonUnion($query)
    {
        return $query
            ->select('id', 'creditor_id', 'title', 'special','special_priority_id', 'special_link', 'active', 'created_at');
    }

    public function scopeAddTypeProductColumn($query)
    {
        return $query->addSelect(DB::raw("'cards' as type_product"));
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
        return $this->hasMany('App\Interceptions\CardsRegionsInterception');
    }

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor')->select('id', 'name', 'image', 'thumbnail', 'genitive', 'dative', 'phone', 'type_slug', 'license_revoked', 'direct_access');
    }

    public function categories()
    {
        return $this->hasMany('App\Interceptions\CardsCategoriesInterception')->select('card_category_id');
    }

    public function bonuses()
    {
        return $this->hasMany('App\Interceptions\CardsBonusesInterception')->select('card_bonus_id');
    }
    public function currencies()
    {
        return $this->hasMany('App\Interceptions\CardsCurrenciesInterception')->select('currency_id');
    }
    public function options()
    {
        return $this->hasMany('App\Interceptions\CardsOptionsInterception')->select('card_option_id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'cards')->select('id', 'creditor_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at');
    }

    /*------------------------------------Getting relations as key value {value: xxx, key: yyyy}---------------------------------------------- */

    public function regionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsRegionsInterception')->select('region_id AS value');
    }

    public function settlementsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsSettlementsInterception')->select('aoid');
    }

    public function categoriesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsCategoriesInterception')->select('card_category_id AS value');
    }

    public function bonusesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsBonusesInterception')->select('card_bonus_id AS value');
    }

    public function currenciesAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsCurrenciesInterception')->select('currency_id AS value');
    }
    public function optionsAsKeyValue()
    {
        return $this->hasMany('App\Interceptions\CardsOptionsInterception')->select('card_option_id AS value');
    }

    public function preview_reviews()
    {
        return $this->hasMany('App\Models\Reviews\Review', 'item_id', 'id')->where('active', 1)->where('type_slug', 'cards')->select('id', 'creditor_id', 'item_id', 'user_id', 'user_type', 'views', 'text', 'author', 'stars', 'published_at', 'created_at', 'type_slug')->take(6)->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }
}
