<?php

namespace App\Http\Controllers\Products;

use App\Models\Products\Deposits\Deposit;
use App\Models\Products\Deposits\DepositsType;
use App\Models\Products\Deposits\DepositsCapitalization;
use App\Models\Products\Deposits\DepositsInterestPayment;

use App\Helpers\ProductsHelper;
use App\Helpers\SubDomainHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\ReviewsHelper;
use App\Helpers\LogsHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\YandexHelper;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepositsController extends Controller
{
    public function getById(Request $request, $id)
    {
        $deposit = Deposit::activeOrAccessibleByDirectLink()->countReviews()->findOrFail($id);
        $reviews = $deposit->preview_reviews;
        $reviews = ReviewsHelper::addTimestampsPublishedAt($reviews);
        foreach ($reviews as $key => $review) {
            $review->user;
            $review->creditor;
            $review->credit_types;
            $review->card_type;
        }
        $deposit->creditor;
        $deposit->currency;
        $deposit->periodHTML = ProductsHelper::addPeriodInRussian($deposit->years_min, $deposit->years_max, $deposit->months_min, $deposit->months_max, $deposit->days_min, $deposit->days_max);
        return $deposit;
    }

    public function getTypes(Request $request)
    {
        return DepositsType::selectFields($request->isKeyValue)->get();
    }

    public function getCapitalizations(Request $request)
    {
        return DepositsCapitalization::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }

    public function getInterestPayments(Request $request)
    {
        return DepositsInterestPayment::selectFields($request->isKeyValue)->orderBy('created_at')->get();
    }

    public function getMinMax(Request $request)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));

        $model = Deposit::active()->matchSubdomain($subdomain);
        $answer_obj = [
            "years_min" => $model->min('years_min'),
            "years_max" => $model->max('years_max'),
            "days_min" => $model->min('days_min'),
            "days_max" => $model->max('days_max'),
            "months_min" => $model->min('months_min'),
            "months_max" => $model->max('months_max'),
            "amount_min" => $model->min('sum_min'),
            "amount_max" => $model->max('sum_max'),
            "percent_min" => $model->where('percent_min', '>', 0)->min('percent_min'),
            "percent_max" => $model->max('percent_max'),
        ];
        
        return $answer_obj;
    }


    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    
    public function addAllRelationships($product)
    {
        AddressesHelper::addRegionMeaningAsKeyValue($product->regionsAsKeyValue);
        AddressesHelper::addTitleByAoId($product->settlementsAsKeyValue);
        ProductsHelper::addCapitalizationMeaningAsKeyValue($product->capitalizationAsKeyValue);
        ProductsHelper::addInterestPaymentMeaningAsKeyValue($product->interestPaymentsAsKeyValue);
        ProductsHelper::addTypesAsKeyValue($product->typesAsKeyValue);
        $product->creditor;
        $product->rateTables;
    }

    public function getAll(Request $request, $xnumber = 10)
    {
        $deposits = Deposit::matchTitleLike($request->title ?? null)
            ->matchMaxPercent($request->maxPercent ?? null)
            ->matchMinAmount($request->minAmount ?? null)
            ->matchMaxAmount($request->maxAmount ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchTypes($request->types ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        ProductsHelper::addTypesToProduct($deposits);
        $deposits =  ProductsHelper::addDepositTypeMeaning($deposits);
        ProductsHelper::addCreditorToProduct($deposits);
        // ProductsHelper::addRegionsToProduct($deposits);
        // ProductsHelper::addSettlementsToProduct($deposits);
        ProductsHelper::addSettlementsCountToProduct($deposits);

        return $deposits;
    }

    public function getByIdFull(Request $request, $id)
    {
        $product = Deposit::findOrFail($id);
        $this->addAllRelationships($product);
        return $product;
    }

    public function add(Request $request)
    {
        $product = new Deposit();
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
        $product->type_id = $request->type_id ?? 1;
        $product->currency_id = $request->currency_id ?? null;
        $product->expertise = $request->expertise ?? null;

        $product->save();
        ProductsHelper::fillProductInterceptions($request->productRegions, 'deposits_regions',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'deposits_settlements',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productTypes, 'deposits_types',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productCapitalizations, 'deposits_capitalizations',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productInterestPayments, 'deposits_interest_payments',  $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'deposits_rate_tables',  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "deposits", "create", $product);
        YandexHelper::reportChanges("deposits", $product);
    }

    public function updateById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Deposit::findOrFail($id);
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
        $product->type_id = $request->type_id ?? 1;
        $product->currency_id = $request->currency_id ?? null;
        $product->expertise = $request->expertise ?? null;

        $product->save();
        ProductsHelper::fillProductInterceptions($request->productRegions, 'deposits_regions', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'deposits_settlements', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productTypes, 'deposits_types', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productCapitalizations, 'deposits_capitalizations', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productInterestPayments, 'deposits_interest_payments', null, $product->id);
        ProductsHelper::fillProductTables($request->productRateTables, 'deposits_rate_tables', null,  $product->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "deposits", "update", $product, $old_product);
        YandexHelper::reportChanges("deposits", $product, $old_product);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("deposits", $product, $old_product);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $product = Deposit::findOrFail($id);
        $product->active =  $product->active ? 0 : 1;
        if ($product->active) $product->direct_access = 0;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($product);
        $this->addAllRelationships($old_product);
        LogsHelper::addLogEntry($request, "deposits", "update", $product, $old_product);
        YandexHelper::reportChanges("deposits", $product, $old_product);
    }

    public function toggleSpecialById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $product = Deposit::findOrFail($id);
        $product->special =  $product->special ? 0 : 1;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "deposits", "update", $product, $old_product);
    }

    public function updateSpecialPriorityById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $product = Deposit::findOrFail($id);
        $product->special_priority_id = $request->special_priority_id;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "deposits", "update", $product, $old_product);
    }

    public function updateSpecialLinkById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $product = Deposit::findOrFail($id);
        $product->special_link = $request->special_link;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "deposits", "update", $product, $old_product);
    }

    public function deleteById(Request $request, $id)
    {
        $old_product = Deposit::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Deposit::findOrFail($id);
        ReviewsHelper::reassignProductReviewsToCreditor($product->id, $product->creditor_id, 'deposits');
        $product->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "deposits", "delete", null, $old_product);
        YandexHelper::reportChanges("deposits", null, $old_product);
    }

    public function duplicateById(Request $request, $id)
    {
        $product_to_copy = Deposit::findOrFail($id);
        $new_product = $product_to_copy->replicate();
        $new_product->title = $new_product->title . ' КОПИЯ';
        $new_product->active = false;
        $new_product->save();
        ProductsHelper::duplicateProductInterceptions('deposits_regions', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('deposits_settlements', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('deposits_capitalizations', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('deposits_interest_payments', $id,  $new_product->id);
        ProductsHelper::duplicateProductTables('deposits_rate_tables', $id,  $new_product->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($new_product);
        LogsHelper::addLogEntry($request, "deposits", "duplicate", $new_product);
        YandexHelper::reportChanges("deposits", $new_product);
    }
}
