<?php

namespace App\Http\Controllers\Products;

use App\Helpers\SubDomainHelper;
use App\Helpers\Helper;
use App\Helpers\SeoHelper;
use App\Helpers\ProductsHelper;
use App\Http\Controllers\Controller;
use App\Models\Products\Cards\Card;
use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Deposits\Deposit;
use App\Models\Products\Credits\Microloan;

use Illuminate\Http\Request;

/* Common controller for all the products. This controllers manages all GET for products, paginated, not paginated, by slug or not.*/

class ProductsController extends Controller
{

    public function getPopularsBySlug(Request $request, $slug, $xnumber = null)
    {
        $request->amount = 'none';
        $request->period = 'none';
        $request->creditorSlug = 'none';
        $products_base = $this->getModelBySlug($request, $slug, null);
        /*
        Commented ->orderByCountReviews as far as it was necessary to make Unions between tables.
        It is necessary to search a solution as far as the popular products should be, those which
        have the highest rating among products of its category.
        COMMENT_ID: COMMENT_1_POPULARS
        */
        $products = $products_base->orderByDate()->paginateOrGet($request->page, $xnumber);
        $this->addAdditionalMeanings($slug, $products, $request);
        return $products;
    }

    public function getModelForBestOfCreditors(Request $request, $slug, $isBOC = false)
    {
        switch ($slug) {
            case 'credits':
                $base_model = $this->getModelBySlug($request, $slug, null)->active()->where('special', 1)->where('is_refinancing', 0)->where('type_slug', '!=', 'mortgage')->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC);
                break;
            case 'mortgage':
                $base_model = $this->getModelBySlug($request, "credits", null)->active()->where('special', 1)->where('is_refinancing', 0)->where('type_slug', '=', 'mortgage')->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC);
                break;
            case 'consumers':
                $base_model = $this->getModelBySlug($request, $slug, null)->active()->where('special', 1)->where('is_refinancing', 0)->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC);
                break;
            case 'microloans':
                $base_model = $this->getModelBySlug($request, $slug, null)->active()->where('special', 1)->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC);
                break;
            case 'refinancing':
                $base_model = Consumer::matchDisplayBusinessLogic()->selectFieldsForUnions()->active()->where('special', 1)->where('is_refinancing', 1)->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC)->unionAll(Credit::matchDisplayBusinessLogic()->active()->where('special', 1)->where('is_refinancing', 1)->selectFieldsForUnions()->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC));
                break;
            case 'deposits':
            case 'cards_credit':
            case 'cards_debit':
                $base_model = $this->getModelBySlug($request, $slug, null)->active()->where('special', 1)->addTypeProductColumn()->orderByBOCLogic()->matchBOCLogic($isBOC);
                break;
        }

        return $base_model;
    }

    public function getSpecialTabs(Request $request)
    {
        $active_tabs = [];
        if ($this->getModelForBestOfCreditors($request, 'credits', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "credits", "title" => "Кредиты"]);
        if ($this->getModelForBestOfCreditors($request, 'mortgage', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "mortgage", "title" => "Ипотека"]);
        if ($this->getModelForBestOfCreditors($request, 'consumers', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "consumers", "title" => "Потребительские кредиты"]);
        if ($this->getModelForBestOfCreditors($request, 'microloans', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "microloans", "title" => "Микрозаймы"]);
        if ($this->getModelForBestOfCreditors($request, 'refinancing', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "refinancing", "title" => "Рефинансирование"]);
        if ($this->getModelForBestOfCreditors($request, 'deposits', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "deposits", "title" => "Вклады"]);
        if ($this->getModelForBestOfCreditors($request, 'cards_credit', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "cards_credit", "title" => "Кредитные карты"]);
        if ($this->getModelForBestOfCreditors($request, 'cards_debit', $request->isBOCLogic ?? false)->count() > 0) array_push($active_tabs, ["slug" => "cards_debit", "title" => "Дебетовые карты"]);
        return $active_tabs;
    }

    public function getSpecialBySlug(Request $request, $slug, $xnumber = 10)
    {
        return ProductsHelper::addCreditorToProduct($this->getModelForBestOfCreditors($request, $slug, $request->isBOCLogic ?? false)->paginateOrGet($request->page, $xnumber, $request->isBOCLogic ?? false));
    }

    public function getAllSpecial(Request $request, $xnumber = 10000)
    {
        $credits = $this->getSpecialBySlug($request, 'credits', $xnumber)->toArray();
        $mortgage = $this->getSpecialBySlug($request, 'mortgage', $xnumber)->toArray();
        $refinancing = $this->getSpecialBySlug($request, 'refinancing', $xnumber)->toArray();
        $consumers = $this->getSpecialBySlug($request, 'consumers', $xnumber)->toArray();
        $microloans = $this->getSpecialBySlug($request, 'microloans', $xnumber)->toArray();
        $deposits = $this->getSpecialBySlug($request, 'deposits', $xnumber)->toArray();
        $cards_credit = $this->getSpecialBySlug($request, 'cards_credit', $xnumber)->toArray();
        $cards_debit = $this->getSpecialBySlug($request, 'cards_debit', $xnumber)->toArray();
        $products = array_merge($credits, $mortgage, $refinancing, $consumers, $microloans, $deposits, $cards_credit, $cards_debit);
        // return Helper::customPaginate($products, $xnumber, $request->page);
        return $products;
    }


    public function getBySlugAndCreditorIDExtra(Request $request, $slug, $creditorId, $xnumber = null)
    {
        $disallowDisplayBusinessLogic = true;
        return $this->getBySlugAndCreditorID($request, $slug, $creditorId, $xnumber, $disallowDisplayBusinessLogic);
    }

    public function getBySlug(Request $request, $slug, $xnumber = null, $disallowDisplayBusinessLogic = false)
    {
        return $this->getBySlugAndCreditorID($request, $slug, null, $xnumber, $disallowDisplayBusinessLogic);
    }

    public function getBySlugAndCreditorID(Request $request, $slug, $creditorId, $xnumber = null, $disallowDisplayBusinessLogic = false)
    {
        $products_base = $this->getModelBySlug($request, $slug, $creditorId, $disallowDisplayBusinessLogic);
        $collection_before_paginate = clone $products_base;
        if ($request->sort != null) {
            $products = $products_base
                ->orderByFilters($request->sort, $request->isKeyValue)
                ->paginateOrGet($request->page, $xnumber);
        } else {
            if ($request->isCreditSelection && $slug != 'cards_credit') {
                $products = $products_base->orderByLessPercent()->paginateOrGet($request->page, $xnumber);
            } else {
                $products = $products_base->orderByDate()->paginateOrGet($request->page, $xnumber);
            }
        }

        $this->addAdditionalMeanings($slug, $products, $request);
        $products = SeoHelper::appendSeoDataToCollection("products", $products, $collection_before_paginate);
        return response()->json($products);
    }

    public function getMarketingProducts(Request $request, $slug)
    {
        $marketing_products_ids = [543, 42, 559, 1286, 831, 988, 1167, 1038, 929, 338];
        $marketing_products = Credit::whereIn('id', $marketing_products_ids)->orderBy('created_at', 'desc')->get();
        $this->addAdditionalMeanings($slug, $marketing_products, $request);
        return $marketing_products;
    }

    /*Local functions as utilities*/
    public function getModelBySlug($request, $slug, $creditorId, $disallowDisplayBusinessLogic = false)
    {
        error_log("disallowDisplayBusinessLogic: $disallowDisplayBusinessLogic");
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        switch ($slug) {
            case 'cards':
                $products_base = Card::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->selectFields();
                break;
            case 'cards_credit':
                $products_base = Card::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCardType('credit')
                    ->matchCategories($request)

                    ->matchGracePeriod($request->gracePeriod)
                    ->matchCurrencies($request)
                    ->matchMainCategories($request->mainCategories ?? null)
                    ->matchHasPercent($request->hasPercent ?? null)
                    ->matchHasCashback($request->hasCashback ?? null)
                    ->matchHasContactlessPayment($request->hasContactlessPayment ?? null)
                    ->matchHasInstantCardIssuance($request->hasInstantCardIssuance ?? null)
                    ->matchHasFreeMaintenance($request->hasFreeMaintenance ?? null)
                    ->matchIsMultiCurrency($request->isMultiCurrency ?? null)
                    ->matchMinAge($request->minAge ?? null)
                    ->matchMaxAge($request->maxAge ?? null)

                    ->matchBonuses($request)
                    ->matchOptions($request)
                    ->matchLimit($request->limit ?? null)
                    ->matchAge($request->age ?? null)
                    ->selectFields()
                    ->matchFilters($request->sort, $request->isKeyValue);
                break;
            case 'cards_debit':
                $products_base = Card::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCardType('debit')
                    ->matchCreditors($request)
                    ->matchCategories($request)

                    ->matchMainCategories($request->mainCategories ?? null)
                    ->matchCurrencies($request)
                    ->matchBonuses($request)
                    ->matchHasPercent($request->hasPercent ?? null)
                    ->matchHasCashback($request->hasCashback ?? null)
                    ->matchHasContactlessPayment($request->hasContactlessPayment ?? null)
                    ->matchHasInstantCardIssuance($request->hasInstantCardIssuance ?? null)
                    ->matchHasFreeMaintenance($request->hasFreeMaintenance ?? null)
                    ->matchIsMultiCurrency($request->isMultiCurrency ?? null)
                    ->matchMinAge($request->minAge ?? null)
                    ->matchMaxAge($request->maxAge ?? null)

                    ->matchOptions($request)
                    ->selectFields()
                    ->matchFilters($request->sort, $request->isKeyValue);
                break;
            case 'cards_all':
                $products_base = Card::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCreditors($request)
                    ->matchCategories($request)
                    ->matchBonuses($request)
                    ->matchOptions($request)
                    ->matchCurrencies($request)
                    ->selectFields()
                    ->matchFilters($request->sort, $request->isKeyValue);
                break;
            case 'credits':
                $products_base = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCreditTypeSlug($request->credit_type ?? null)
                    ->selectFields();
                break;
            case 'consumers':
                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->selectFields();
                break;
            case 'credits_flats':
            case 'credits_rooms':
            case 'credits_shared':
            case 'credits_house':
            case 'credits_parcels':
            case 'credits_apartments':
            case 'credits_townhouse':
            case 'credits_commercial':
            case 'credits_auto':
                $pieces = explode("_", $slug);
                $type = $pieces[0];
                $pledge_slug = $pieces[1];
                $products_base = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCreditTypeAndPledgeSlug('pledge', $pledge_slug)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)

                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchNoInsurance($request->noInsurance ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->matchOccupations($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->orderByLessPercent();

                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)

                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchNoInsurance($request->noInsurance ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->matchOccupations($request)
                    ->matchExtraCategoriesWithPledgeAndRealState('pledge', $pledge_slug)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->unionAll($products_base);
                break;
            case 'credits_realstate':
                // error_log("PLEDGE", $request->pledgeSlugs);
                $products_base = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCreditsRealState()
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)

                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchPledgeSlugs($request->pledgeSlugs ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->matchOccupations($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue);

                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchExtraCategoryWithPledge('pledge', 'realstate')
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)

                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->matchOccupations($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->unionAll($products_base);
                break;
            case 'credits_mortgage':
            case 'credits_refinancing':
            case 'credits_pledge':
                $pieces = explode("_", $slug);
                $type = $pieces[0];
                $slug = $pieces[1];
                //matchFilters must be called before the union function in order to work.
                $products_base = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchCreditTypeSlug($slug)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null, $slug ?? null)


                    ->matchCreditRefinancingAndExtraSlugs($slug, $request->extraSlugs ?? null)
                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchPledgeSlugs($request->pledgeSlugs ?? null)
                    ->matchInitialPaymentMin($request->initialPaymentMin)
                    ->matchInitialPaymentMax($request->initialPaymentMax)
                    ->matchOccupations($request)
                    ->matchCreditorsIds($request->creditors ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue);

                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchExtraCategory($slug)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)

                    ->matchCreditRefinancingAndExtraSlugs($slug, $request->extraSlugs ?? null)
                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchOccupations($request)
                    ->matchCreditorsIds($request->creditors ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->unionAll($products_base);
                break;
            case 'credits_consumers':
                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)
                    ->matchPercent($request->percent ?? null)

                    ->matchCreditorsIds($request->creditors ?? null)
                    ->matchCreditorSlugs($request->creditorSlugs ? $request->creditorSlugs : ($request->creditorSlug ? $request->creditorSlug : null))
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchPurposes($request->purposes ? $request->purposes : ($request->purpose ? $request->purpose : null))

                    ->matchNoInsurance($request->noInsurance ?? null)
                    ->matchNoPledge($request->noPledge ?? null)
                    ->matchNoProof($request->noProof ?? null)
                    ->matchThreeDayReview($request->threeDay ?? null)
                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue);

                $products_base = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchExtraCategory('consumers')
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)
                    ->matchPercent($request->percent ?? null)

                    ->matchCreditorsIds($request->creditors ?? null)
                    ->matchPercentMin($request->percentMin)
                    ->matchPercentMax($request->percentMax)
                    ->matchNoInsurance($request->noInsurance ?? null)

                    ->matchHistories($request)
                    ->matchProofs($request)
                    ->orderByLessPercent()
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->unionAll($products_base);

                break;
            case 'deposits':
                $products_base = Deposit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchTypes($request->types ?? null)

                    ->matchCapitalizations($request->capitalizations ?? null)
                    ->matchInterestPayments($request->interestPayments ?? null)
                    ->matchCurrencies($request->currencies ?? null)

                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)
                    ->selectFields()
                    ->matchFilters($request->sort, $request->isKeyValue);
                break;
            case 'credits_microloans':
            case 'microloans':
                $products_base = Microloan::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->matchAmount($request->amount ?? null)
                    ->matchPeriod($request->period ?? null)
                    ->selectFields()
                    ->matchFilters($request->sort, $request->isKeyValue);
                break;
            case 'credits_all':
                $products_base1 = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    //->matchAmount($request->amount ?? null)
                    //->matchPeriod($request->period ?? null)
                    ->selectFieldsForUnionWithMicroloans()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->addTypeProductColumn();

                $products_base2 = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->selectFieldsForUnionWithMicroloans()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->addTypeProductColumn();

                $products_base = Microloan::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    //->matchAmount($request->amount ?? null)
                    //->matchPeriod($request->period ?? null)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->unionAll($products_base1)
                    ->unionAll($products_base2)
                    ->addTypeProductColumn();
                break;
            case 'credits_all_no_microloans':
                $products_base1 = Credit::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->addTypeProductColumn();

                $products_base = Consumer::matchDisplayBusinessLogic($disallowDisplayBusinessLogic)
                    ->selectFieldsForUnions()
                    ->matchFilters($request->sort, $request->isKeyValue)
                    ->addTypeProductColumn()
                    ->unionAll($products_base1);
                break;
        }

        $products_base = $products_base
            ->matchCreditorId($creditorId ?? null)
            ->matchSubdomain($subdomain);

        return $products_base;
    }

    public function addAdditionalMeanings($slug, $products, $request)
    {
        switch ($slug) {
            case 'cards':
            case 'cards_credit':
            case 'cards_debit':
            case 'cards_all':
                foreach ($products as $key => $product) {
                    $product->creditor;
                    $product->categories = ProductsHelper::addCardCategoryMeaning($product->categories);
                }
                break;
            case 'credits_flats':
            case 'credits_rooms':
            case 'credits_shared':
            case 'credits_house':
            case 'credits_parcels':
            case 'credits_apartments':
            case 'credits_townhouse':
            case 'credits_commercial':
            case 'credits_auto':
            case 'credits_mortgage':
            case 'credits_refinancing':
            case 'credits_pledge':
            case 'credits_consumers':
            case 'consumers':
            case 'credits_realstate':
            case 'credits':
            case 'credits_all':
            case 'credits_all_no_microloans':
                $period = $request->period ?? null;
                $period_type = $request->periodType ?? 'years';
                $amount_requested = $request->amount ?? null;
                foreach ($products as $key => &$product) {
                    $product->creditor;
                    $product->occupations;
                    $product->proofs;
                    $product->histories;
                    $product->monthly_payment = ProductsHelper::creditPaymentInMonths($period, $period_type, $amount_requested, $product->percent);
                }
                break;
            case 'deposits':
                foreach ($products as $key => &$product) {
                    $product->creditor;
                }
                break;
            case 'credits_microloans':
                foreach ($products as $key => &$product) {
                    $product->creditor;
                }
                break;
        }
    }



    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    public function getAll(Request $request, $xnumber = 10)
    {

        $products_base1 = Credit::selectFieldsCommonUnion()
            ->matchTitleLike($request->title ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->addTypeProductColumn();

        $products_base2 = Consumer::selectFieldsCommonUnion()
            ->matchTitleLike($request->title ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->addTypeProductColumn();

        $products_base3 = Microloan::selectFieldsCommonUnion()
            ->matchTitleLike($request->title ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->addTypeProductColumn();

        $products_base4 = Card::selectFieldsCommonUnion()
            ->matchTitleLike($request->title ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->addTypeProductColumn();

        $products = Deposit::selectFieldsCommonUnion()
            ->matchTitleLike($request->title ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->addTypeProductColumn()
            ->unionAll($products_base1)
            ->unionAll($products_base2)
            ->unionAll($products_base3)
            ->unionAll($products_base4)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);

        ProductsHelper::addCreditorToProduct($products);

        return $products;
    }
}
