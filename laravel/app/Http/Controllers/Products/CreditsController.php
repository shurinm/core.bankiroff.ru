<?php

namespace App\Http\Controllers\Products;

use App\Helpers\SubDomainHelper;
use App\Helpers\ProductsHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\ReviewsHelper;
use App\Helpers\LogsHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\YandexHelper;


use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Credits\Microloan;

use App\Models\Products\Credits\ConsumersPurpose;

use App\Models\Products\Credits\MicroloansPurpose;
use App\Models\Products\Credits\MicroloansProvision;

use App\Models\Products\Credits\CreditsHistory;
use App\Models\Products\Credits\CreditsProof;
use App\Models\Products\Credits\CreditsOccupation;
use App\Models\Products\Credits\CreditsPledgesSlug;
use App\Models\Products\Credits\CreditsInsurance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditsController extends Controller
{
    public function getBySlugAndID(Request $request, $slug, $creditId)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        switch ($slug) {
            case 'realstate':
            case 'flats':
            case 'rooms':
            case 'shared':
            case 'house':
            case 'parcels':
            case 'apartments':
            case 'townhouse':
            case 'commercial':
            case 'auto':
            case 'pledge':
                // $credit = Credit::matchCreditTypeAndPledgeSlug('pledge', $slug);
                $credit = Credit::matchSubdomain($subdomain);
                break;
            case 'mortgage':
            case 'refinancing':
                $credit = Credit::matchSubdomain($subdomain);
                break;
            case 'consumers':
                $credit = Consumer::matchSubdomain($subdomain);
                break;
            case 'microloans':
                $credit = Microloan::matchSubdomain($subdomain);
                break;
        }
        $credit = $credit
            ->activeOrAccessibleByDirectLink()
            ->matchSubdomain($subdomain)
            ->countReviews()->findOrFail($creditId);

        $reviews = $credit->preview_reviews;
        $reviews = ReviewsHelper::addTimestampsPublishedAt($reviews);
        foreach ($reviews as $key => $review) {
            $review->user;
            $review->creditor;
            $review->credit_types;
            $review->card_type;
        }
        $credit->creditor;
        $credit->periodHTML = ProductsHelper::addPeriodInRussian($credit->years_min, $credit->years_max, $credit->months_min, $credit->months_max, $credit->days_min, $credit->days_max);
        return $credit;
    }
    
    public function getMinMaxBySlug(Request $request, $slug)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        switch ($slug) {
            case 'realstate':
                $model = Credit::matchSubdomain($subdomain)->matchCreditsRealState();
                break;
            case 'flats':
            case 'rooms': 
            case 'shared':
            case 'house':
            case 'parcels':
            case 'apartments':
            case 'townhouse':
            case 'commercial':
            case 'auto':
            case 'pledge':
            case 'mortgage':
                $slug_type = ($slug == 'refinancing' || $slug == 'mortgage' || $slug == 'pledge') ? 'type_slug' : 'pledge_slug';
                $model = Credit::matchSubdomain($subdomain)->where($slug_type, '=', $slug);
                break;
            case 'refinancing':
                $model = Credit::matchSubdomain($subdomain)->where('is_refinancing', '=', 1);
                break;
            case 'consumers':
                $model = Consumer::matchSubdomain($subdomain);
                break;
            case 'microloans':
                $model = Microloan::matchSubdomain($subdomain);
                break;
        }
        $model = $model->active();
        $answer_obj = [
            "years_min" => $model->min('years_min'),
            "years_max" => $model->max('years_max'),
            "days_min" => $model->min('days_min'),
            "days_max" => $model->max('days_max'),
            "amount_min" => $model->min('sum_min'),
            "amount_max" => $model->max('sum_max'),
            "percent_min" => $model->where('percent_min', '>', 0)->min('percent_min'),
            "percent_max" => $model->max('percent_max'),
        ];
        if($slug == 'mortgage') {
            $answer_obj["initial_payment_min"] = $model->where('initial_payment', '>', 0)->min('initial_payment');
            $answer_obj["initial_payment_max"] = $model->max('initial_payment') ;
        }
        
        return $answer_obj;
    }

    public function getByCreditorSlug(Request $request, $slug, $xnumber)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));

        if ($slug && $slug == 'mfo') {
            $credits = Microloan::active()->matchCreditorSlugs($slug ?? null);
        } else {
            $credits = Credit::active()->matchCreditorSlugs($slug ?? null)->matchTypeAndPledgeSlugAuto($request->type ?? null);
        }
        $sort_percent = null;
        if ($request->sort == 'percent_asc') {
            $sort_percent = 'asc';
        } else if ($request->sort == 'percent_desc') {
            $sort_percent = 'desc';
        }
        $sort_date = null;
        if ($request->sort == 'created_asc') {
            $sort_percent = 'asc';
        } else if ($request->sort == 'created_desc') {
            $sort_percent = 'desc';
        }

        // dd($sort_percent);
        $credits = $credits
            ->matchSubdomain($subdomain)
            ->matchCreditorsWithLicense()
            ->where('percent', '!=', null)->where('percent', '>', 0)
            ->orderByPercent($sort_percent)
            ->orderByDateConditional($sort_date)
            ->selectFields()
            ->paginateOrGet($request->page, $xnumber);

        foreach ($credits as $key => $credit) {
            $credit->creditor;
        }

        return $credits;
    }

    public function getBestPercentAndAverage(Request $request, $slug)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));

        if ($slug && $slug == 'mfo') {
            $credit_selected = Microloan::active()->matchCreditorSlugs($slug ?? null);
            $average = round($credit_selected
                ->where('percent', '!=', null)
                ->where('percent', '>', 0)
                ->avg('percent'), 2);
        } else {
            $credit_selected = Credit::active()->matchCreditorSlugs($slug ?? null)->matchTypeAndPledgeSlugAuto($request->type ?? null);
            $average = round($credit_selected
                ->where('percent', '!=', null)
                ->where('percent', '>', 0)
                ->matchTypeAndPledgeSlugAuto($request->type ?? null)
                ->avg('percent'), 2);
        }
        $credit = $credit_selected
            ->matchSubdomain($subdomain)
            ->matchCreditorsWithLicense()
            ->where('percent', '!=', null)->where('percent', '>', 0)
            ->orderByLessPercent()
            ->selectFields()
            ->first();
        if ($credit) {
            $credit->creditor;
        }

        $answer_obj = [
            "best_credit" => $credit,
            "average" => $average
        ];
        return $answer_obj;
    }

    public function getConsumersPurposes(Request $request)
    {
        return ConsumersPurpose::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }

    public function getCreditsHistories(Request $request)
    {
        return CreditsHistory::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }
    public function getCreditsProofs(Request $request)
    {
        return CreditsProof::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }
    public function getCreditsOccupations(Request $request)
    {
        return CreditsOccupation::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }
    public function getCreditsPledgeSlugs(Request $request)
    {
        return CreditsPledgesSlug::selectFields($request->isKeyValue)->get();
    }
    public function getCreditsInsurances(Request $request)
    {
        return CreditsInsurance::selectFields($request->isKeyValue)->get();
    }
    public function getMicroloansPurposes(Request $request)
    {
        return MicroloansPurpose::selectFields($request->isKeyValue)->get();
    }
    public function getMicroloansProvisions(Request $request)
    {
        return MicroloansProvision::selectFields($request->isKeyValue)->get();
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    /*For credits под залог, ипотека, все кроме Consumers (Потребительские кредиты) and Microloans (Микрозаймы)*/

    public function addAllRelationships($product)
    {
        AddressesHelper::addRegionMeaningAsKeyValue($product->regionsAsKeyValue);
        AddressesHelper::addTitleByAoId($product->settlementsAsKeyValue);
        ProductsHelper::addCreditsHistoriesMeaningAsKeyValue($product->historiesAsKeyValue);
        ProductsHelper::addCreditsProofMeaningAsKeyValue($product->proofsAsKeyValue);
        ProductsHelper::addCreditsOccupationMeaningAsKeyValue($product->occupationsAsKeyValue);
        ProductsHelper::addCreditsPledgeMeaningAsKeyValue($product->pledgesAsKeyValue);
        ProductsHelper::addCreditsInsuranceMeaningAsKeyValue($product->insurancesAsKeyValue);
        ProductsHelper::addProvisionMeaningAsKeyValue($product->provisionsAsKeyValue);
        $product->creditor;
        $product->rateTables;
        $product->extraCategories;
    }

    public function getAllCredits(Request $request, $slug, $xnumber = 10)
    {
        $products = Credit::matchType($slug)
            ->matchTitleLike($request->title ?? null)
            ->matchMaxPercent($request->maxPercent ?? null)
            ->matchMinAmount($request->minAmount ?? null)
            ->matchMaxAmount($request->maxAmount ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchTypes($request->types ?? null)
            ->matchPledgeSlugs($request->pledgeSlugs ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        ProductsHelper::addCreditorToProduct($products);
        // ProductsHelper::addRegionsToProduct($products);
        // ProductsHelper::addSettlementsToProduct($products);
        ProductsHelper::addSettlementsCountToProduct($products);

        return $products;
    }

    public function getByIdFull(Request $request, $id)
    {
        $credit = Credit::findOrFail($id);
        $this->addAllRelationships($credit);
        return $credit;
    }

    public function add(Request $request)
    {
        $product = new Credit();
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->type_slug = $request->type_slug;
        $product->pledge_slug = $request->pledge_slug;
        $product->expertise = $request->expertise;
        $product->initial_payment = $request->initial_payment;
        $product->is_refinancing = $request->is_refinancing;
        $product->save();
        ProductsHelper::fillProductInterceptions($request->productHistories, 'credits_histories', $product->id);
        ProductsHelper::fillProductInterceptions($request->productProofs, 'credits_proofs',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productOccupations, 'credits_occupations',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'credits_regions',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'credits_settlements',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productPledges, 'credits_pledges',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productInsurances, 'credits_insurances',  $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'credits_rate_tables',  $product->id);
        ProductsHelper::fillProductTables($request->productExtraCategories, 'credits_extra_categories',  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "credits", "create", $product);
        YandexHelper::reportChanges("credits", $product);
    }

    public function updateById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Credit::findOrFail($id);
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->type_slug = $request->type_slug;
        $product->pledge_slug = $request->pledge_slug;
        $product->expertise = $request->expertise;
        $product->initial_payment = $request->initial_payment;
        $product->is_refinancing = $request->is_refinancing;
        $product->save();

        ProductsHelper::fillProductInterceptions($request->productHistories, 'credits_histories', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productProofs, 'credits_proofs', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productOccupations, 'credits_occupations', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'credits_regions', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'credits_settlements', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productPledges, 'credits_pledges', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productInsurances, 'credits_insurances', null, $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'credits_rate_tables', null,  $product->id);
        ProductsHelper::fillProductTables($request->productExtraCategories, 'credits_extra_categories', null,  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "credits", "update", $product, $old_product);
        YandexHelper::reportChanges("credits", $product, $old_product);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("credits", $product, $old_product);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $product = Credit::findOrFail($id);
        $product->active =  $product->active ? 0 : 1;
        if ($product->active) $product->direct_access = 0;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        $this->addAllRelationships($old_product);
        LogsHelper::addLogEntry($request, "credits", "update", $product, $old_product);
        YandexHelper::reportChanges("credits", $product, $old_product);
    }

    public function toggleSpecialById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $product = Credit::findOrFail($id);
        $product->special =  $product->special ? 0 : 1;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "credits", "update", $product, $old_product);
    }

    public function updateSpecialPriorityById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $product = Credit::findOrFail($id);
        $product->special_priority_id = $request->special_priority_id;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "credits", "update", $product, $old_product);
    }

    public function updateSpecialLinkById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $product = Credit::findOrFail($id);
        $product->special_link = $request->special_link;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "credits", "update", $product, $old_product);
    }

    public function deleteById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Credit::findOrFail($id);
        ReviewsHelper::reassignProductReviewsToCreditor($product->id, $product->creditor_id, 'credits');
        $product->delete();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "credits", "delete", null, $old_product);
        YandexHelper::reportChanges("credits", null, $old_product);
    }

    public function duplicateById(Request $request, $id)
    {
        $product_to_copy = Credit::findOrFail($id);
        $new_product = $product_to_copy->replicate();
        $new_product->title = $new_product->title . ' КОПИЯ';
        $new_product->active = 0;
        $new_product->save();

        ProductsHelper::duplicateProductInterceptions('credits_histories', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_proofs', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_occupations', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_regions', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_settlements', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_pledges', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('credits_insurances', $id, $new_product->id);
        ProductsHelper::duplicateProductTables('credits_rate_tables', $id, $new_product->id);
        ProductsHelper::duplicateProductTables('credits_extra_categories', $id, $new_product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($new_product);
        LogsHelper::addLogEntry($request, "credits", "duplicate", $new_product);
        YandexHelper::reportChanges("credits", $new_product);
    }

    /*For Consumers (Потребительские кредиты)*/
    public function getAllConsumers(Request $request, $xnumber = 10)
    {
        $products = Consumer::matchTitleLike($request->title ?? null)
            ->matchMaxPercent($request->maxPercent ?? null)
            ->matchMinAmount($request->minAmount ?? null)
            ->matchMaxAmount($request->maxAmount ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        ProductsHelper::addCreditorToProduct($products);
        // ProductsHelper::addRegionsToProduct($products);
        // ProductsHelper::addSettlementsToProduct($products);
        ProductsHelper::addSettlementsCountToProduct($products);
        return $products;
    }

    public function getByIdFullConsumer(Request $request, $id)
    {
        $product = Consumer::findOrFail($id);
        $this->addAllRelationships($product);
        return $product;
    }

    public function addConsumer(Request $request)
    {
        $product = new Consumer();
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->issuance = $request->issuance;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->no_insurance = $request->no_insurance ?? 0;
        $product->no_pledge = $request->no_pledge ?? 0;
        $product->no_proof = $request->no_proof ?? 0;
        $product->three_day_review = $request->three_day_review ?? 0;
        $product->purpose_id = $request->purpose_id ?? 1;
        $product->age_min = $request->age_min ?? null;
        $product->age_max = $request->age_max ?? null;
        $product->acceptance_period_min = $request->acceptance_period_min ?? null;
        $product->acceptance_period_max = $request->acceptance_period_max ?? null;
        $product->is_refinancing = $request->is_refinancing;
        $product->save();
        ProductsHelper::fillProductInterceptions($request->productHistories, 'consumers_histories', $product->id);
        ProductsHelper::fillProductInterceptions($request->productProofs, 'consumers_proofs',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productOccupations, 'consumers_occupations',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'consumers_regions',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'consumers_settlements',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productInsurances, 'consumers_insurances',  $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'consumers_rate_tables',  $product->id);
        ProductsHelper::fillProductTables($request->productExtraCategories, 'consumers_extra_categories',  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "consumers", "create", $product);
        YandexHelper::reportChanges("consumers", $product);
    }

    public function updateConsumerById(Request $request, $id)
    {
        $old_product = Consumer::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Consumer::findOrFail($id);
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->issuance = $request->issuance;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->no_insurance = $request->no_insurance ?? 0;
        $product->no_pledge = $request->no_pledge ?? 0;
        $product->no_proof = $request->no_proof ?? 0;
        $product->three_day_review = $request->three_day_review ?? 0;
        $product->purpose_id = $request->purpose_id ?? 1;
        $product->age_min = $request->age_min ?? null;
        $product->age_max = $request->age_max ?? null;
        $product->acceptance_period_min = $request->acceptance_period_min ?? null;
        $product->acceptance_period_max = $request->acceptance_period_max ?? null;
        $product->is_refinancing = $request->is_refinancing;
        $product->save();
        ProductsHelper::fillProductInterceptions($request->productHistories, 'consumers_histories', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productProofs, 'consumers_proofs', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productOccupations, 'consumers_occupations', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'consumers_regions', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'consumers_settlements', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productInsurances, 'consumers_insurances', null, $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'consumers_rate_tables', null,  $product->id);
        ProductsHelper::fillProductTables($request->productExtraCategories, 'consumers_extra_categories', null,  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "consumers", "update", $product, $old_product);
        YandexHelper::reportChanges("consumers", $product, $old_product);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("consumers", $product, $old_product);
    }

    public function toggleSpecialByConsumerId(Request $request, $id)
    {
        $old_product = Consumer::findOrFail($id);
        $product = Consumer::findOrFail($id);
        $product->special =  $product->special ? 0 : 1;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "consumers", "update", $product, $old_product);
    }

    public function updateSpecialPriorityByConsumerId(Request $request, $id)
    {
        $old_product = Consumer::findOrFail($id);
        $product = Consumer::findOrFail($id);
        $product->special_priority_id = $request->special_priority_id;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "consumers", "update", $product, $old_product);
    }

    public function updateSpecialLinkByConsumerId(Request $request, $id)
    {
        $old_product = Consumer::findOrFail($id);
        $product = Consumer::findOrFail($id);
        $product->special_link = $request->special_link;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "consumers", "update", $product, $old_product);
    }

    public function deleteConsumerById(Request $request, $id)
    {
        $old_product = Consumer::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Consumer::findOrFail($id);
        ReviewsHelper::reassignProductReviewsToCreditor($product->id, $product->creditor_id, 'consumers');
        $product->delete();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "consumers", "delete", null, $old_product);
        YandexHelper::reportChanges("consumers", null, $old_product);
    }

    public function duplicateConsumerById(Request $request, $id)
    {
        $product_to_copy = Consumer::findOrFail($id);
        $new_product = $product_to_copy->replicate();
        $new_product->title = $new_product->title . ' КОПИЯ';
        $new_product->active = false;
        $new_product->save();
        ProductsHelper::duplicateProductInterceptions('consumers_histories', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('consumers_proofs', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('consumers_occupations', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('consumers_regions', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('consumers_settlements', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('consumers_insurances', $id, $new_product->id);
        ProductsHelper::duplicateProductTables('consumers_rate_tables', $id, $new_product->id);
        ProductsHelper::duplicateProductTables('consumers_extra_categories', $id, $new_product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($new_product);
        LogsHelper::addLogEntry($request, "consumers", "duplicate", $new_product);
        YandexHelper::reportChanges("consumers", $new_product);
    }

    /*For Microloans (Микрозаймы)*/

    public function getAllMicroloans(Request $request, $xnumber = 10)
    {
        $products = Microloan::matchTitleLike($request->title ?? null)
            ->matchMaxPercent($request->maxPercent ?? null)
            ->matchMinAmount($request->minAmount ?? null)
            ->matchMaxAmount($request->maxAmount ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        ProductsHelper::addCreditorToProduct($products);
        // ProductsHelper::addRegionsToProduct($products);
        // ProductsHelper::addSettlementsToProduct($products);
        ProductsHelper::addSettlementsCountToProduct($products);
        return $products;
    }

    public function getByIdFullMicroloan(Request $request, $id)
    {
        $product = Microloan::findOrFail($id);
        $this->addAllRelationships($product);
        return $product;
    }


    public function addMicroloan(Request $request)
    {
        $product = new Microloan();
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->issuance = $request->issuance;
        $product->purpose_id = $request->purpose_id;
        $product->provision = $request->provision;
        $product->commission = $request->commission;

        $product->save();
        ProductsHelper::fillProductInterceptions($request->productRegions, 'microloans_regions',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'microloans_settlements',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productProvisions, 'microloans_provisions',  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "microloans", "create", $product);
        YandexHelper::reportChanges("microloans", $product);
    }

    public function updateMicroloanById(Request $request, $id)
    {
        $old_product = Microloan::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Microloan::findOrFail($id);
        $product->title = $request->title;
        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;
        $product->sum_min = $request->sum_min ?? 0;
        $product->sum_max = $request->sum_max ?? 0;
        $product->requirements = $request->requirements;
        $product->documents = $request->documents;
        $product->advantages = $request->advantages;
        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ?? 0;
        $product->active = $request->active ?? 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->years_min = $request->years_min;
        $product->years_max = $request->years_max;
        $product->months_min = $request->months_min;
        $product->months_max = $request->months_max;
        $product->days_min = $request->days_min;
        $product->days_max = $request->days_max;
        $product->issuance = $request->issuance;
        $product->purpose_id = $request->purpose_id;
        $product->provision = $request->provision;
        $product->commission = $request->commission;

        $product->save();

        ProductsHelper::fillProductInterceptions($request->productRegions, 'microloans_regions', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'microloans_settlements', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productProvisions, 'microloans_provisions', null, $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "microloans", "update", $product, $old_product);
        YandexHelper::reportChanges("microloans", $product, $old_product);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("microloans", $product, $old_product);
    }

    public function toggleActiveByMicroloanId(Request $request, $id)
    {
        $old_product = Microloan::findOrFail($id);
        $product = Microloan::findOrFail($id);
        $product->active =  $product->active ? 0 : 1;
        if ($product->active) $product->direct_access = 0;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($product);
        $this->addAllRelationships($old_product);
        LogsHelper::addLogEntry($request, "microloans", "update", $product, $old_product);
        YandexHelper::reportChanges("microloans", $product, $old_product);
    }

    public function toggleSpecialByMicroloanId(Request $request, $id)
    {
        $old_product = Microloan::findOrFail($id);
        $product = Microloan::findOrFail($id);
        $product->special =  $product->special ? 0 : 1;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "microloans", "update", $product, $old_product);
    }

    public function updateSpecialPriorityByMicroloanId(Request $request, $id)
    {
        $old_product = Microloan::findOrFail($id);
        $product = Microloan::findOrFail($id);
        $product->special_priority_id = $request->special_priority_id;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "microloans", "update", $product, $old_product);
    }

    public function updateSpecialLinkByMicroloanId(Request $request, $id)
    {
        $old_product = Microloan::findOrFail($id);
        $product = Microloan::findOrFail($id);
        $product->special_link = $request->special_link;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "microloans", "update", $product, $old_product);
    }

    public function deleteMicroloanById(Request $request, $id)
    {
        $old_product = Credit::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Microloan::findOrFail($id);
        ReviewsHelper::reassignProductReviewsToCreditor($product->id, $product->creditor_id, 'consumers');
        $product->delete();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "microloans", "delete", null, $old_product);
        YandexHelper::reportChanges("microloans", null, $old_product);
    }


    public function duplicateMicroloanById(Request $request, $id)
    {
        $product_to_copy = Microloan::findOrFail($id);
        $new_product = $product_to_copy->replicate();
        $new_product->title = $new_product->title . ' КОПИЯ';
        $new_product->active = false;
        $new_product->save();
        ProductsHelper::duplicateProductInterceptions('microloans_regions', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('microloans_settlements', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('microloans_provisions', $id,  $new_product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($new_product);
        LogsHelper::addLogEntry($request, "microloans", "duplicate", $new_product);
        YandexHelper::reportChanges("microloans", $new_product);
    }
}
