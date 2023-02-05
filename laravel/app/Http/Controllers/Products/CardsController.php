<?php

namespace App\Http\Controllers\Products;

use App\Helpers\ProductsHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\LogsHelper;
use App\Helpers\ReviewsHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\YandexHelper;
use App\Helpers\SubDomainHelper;

use App\Models\Products\Cards\Card;
use App\Models\Products\Cards\CardsBonus;
use App\Models\Products\Cards\CardsCategory;
use App\Models\Products\Cards\CardsOption;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Str;
use Storage;
use File;

class CardsController extends Controller
{
    public function getBonuses(Request $request)
    {
        return CardsBonus::selectFields($request->isKeyValue)->get();
    }
    public function getCategories(Request $request)
    {
        return CardsCategory::selectFields($request->isKeyValue)->get();
    }
    public function getOptionsBySlug(Request $request, $slug = null)
    {
        return CardsOption::whereSlug($slug)->selectFields($request->isKeyValue)->get();
    }

    public function getById(Request $request, $id)
    {
        $card = Card::activeOrAccessibleByDirectLink()->countReviews()->findOrFail($id);
        $reviews = $card->preview_reviews;
        $reviews = ReviewsHelper::addTimestampsPublishedAt($reviews);
        foreach ($reviews as $key => $review) {
            $review->user;
            $review->creditor;
            $review->credit_types;
            $review->card_type;
        }
        $card->creditor;
        return $card;
    }

    public function getCardTypeById(Request $request, $id)
    {
        return Card::active()->where('id', $id)->select('type')->first();
    }

    public function getMinMaxBySlug(Request $request, $slug)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));

        $model = Card::active()->matchSubdomain($subdomain)->matchCardType($slug);
        $answer_obj = [
            "credit_limit_min" => $model->min('card_limit'),
            "credit_limit_max" => $model->max('card_limit'),
            "min_age" => $model->min('min_age'),
            "max_age" => $model->max('max_age'),
            "grace_period_min" => $model->min('grace_period_min'),
            "grace_period_max" => $model->max('grace_period_max'),
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
        ProductsHelper::addCardCategoriesMeaningAsKeyValue($product->categoriesAsKeyValue);
        ProductsHelper::addCurrenciesMeaningAsKeyValue($product->currenciesAsKeyValue);
        ProductsHelper::addCardOptionsMeaningAsKeyValue($product->optionsAsKeyValue);
        ProductsHelper::addCardBonusesMeaningAsKeyValue($product->bonusesAsKeyValue);
        $product->creditor;
    }

    public function getAll(Request $request, $xnumber = 10)
    {
        $products = Card::matchTitleLike($request->title ?? null)
            ->matchMinPriceAmount($request->minAmount ?? null)
            ->matchMaxPriceAmount($request->maxAmount ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorId($request->creditorId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchTypes($request->types ?? null)
            ->matchActiveState($request->moderated ?? null)
            ->matchSpecialState($request->special ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $request->paginatedBy ?? $xnumber);
        ProductsHelper::addCreditorToProduct($products);
        // ProductsHelper::addRegionsToProduct($products);
        // ProductsHelper::addSettlementsToProduct($products);
        ProductsHelper::addSettlementsCountToProduct($products);
        ProductsHelper::addCurrenciesToProduct($products);
        return $products;
    }

    public function getByIdFull(Request $request, $id)
    {
        $product = Card::findOrFail($id);
        $this->addAllRelationships($product);
        return $product;
    }

    public function add(Request $request)
    {
        $product = new Card();
        $product->title = $request->title;

        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;

        $product->year_price_min = $request->year_price_min ?? null;
        $product->year_price_max = $request->year_price_max ?? null;
        $product->year_price = $request->year_price ?? null;

        $product->cash_back_min = $request->cash_back_min ?? null;
        $product->cash_back_max = $request->cash_back_max ?? null;
        $product->cash_back = $request->cash_back ?? null;

        $product->grace_period_min = $request->grace_period_min ?? null;
        $product->grace_period_max = $request->grace_period_max ?? null;
        $product->grace_period = $request->grace_period ?? null;

        $product->min_age = $request->min_age;
        $product->max_age = $request->max_age;

        $product->card_limit = $request->limit;
        $product->is_individual_limit = $request->is_individual_limit ? 1 : 0;

        $product->card_issue = $request->card_issue;


        $product->requirements = $request->requirements;
        $product->maintenance = $request->maintenance;
        $product->advantages = $request->advantages;
        $product->conditions = $request->conditions;
        $product->expertise = $request->expertise;

        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ? 1 : 0;
        $product->active = $request->active ? 1 : 0;
        $product->direct_access = $request->direct_access ? 1 : 0;

        $product->is_not_available = $request->is_not_available ? 1 : 0;

        $product->type = $request->type;
        $product->referral_cpa_link = $request->referral_cpa_link;
        $product->save();
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $product->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/cards/' . $name, File::get($image));
            $product->image =  $name;
            $product->save();
        }

        ProductsHelper::fillProductInterceptions($request->productBonuses, 'cards_bonuses',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productCategories, 'cards_categories',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productCurrencies, 'cards_currencies',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productOptions, 'cards_options',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'cards_regions',  $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'cards_settlements',  $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "cards", "create", $product);
        YandexHelper::reportChanges("cards", $product );
    }

    public function updateById(Request $request, $id)
    {
        $old_product = Card::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Card::findOrFail($id);
        $product->title = $request->title;

        $product->percent = $request->percent ?? null;
        $product->percent_min = $request->percent_min ?? null;
        $product->percent_max = $request->percent_max ?? null;

        $product->year_price_min = $request->year_price_min ?? null;
        $product->year_price_max = $request->year_price_max ?? null;
        $product->year_price = $request->year_price ?? null;

        $product->cash_back_min = $request->cash_back_min ?? null;
        $product->cash_back_max = $request->cash_back_max ?? null;
        $product->cash_back = $request->cash_back ?? null;

        $product->grace_period_min = $request->grace_period_min ?? null;
        $product->grace_period_max = $request->grace_period_max ?? null;
        $product->grace_period = $request->grace_period ?? null;

        $product->min_age = $request->min_age;
        $product->max_age = $request->max_age;

        $product->card_limit = $request->limit;
        $product->is_individual_limit = $request->is_individual_limit ? 1 : 0;

        $product->card_issue = $request->card_issue;

        $product->requirements = $request->requirements;
        $product->maintenance = $request->maintenance;
        $product->advantages = $request->advantages;
        $product->conditions = $request->conditions;
        $product->expertise = $request->expertise;

        $product->creditor_id = $request->creditor_id;
        $product->special = $request->special ? 1 : 0;
        $product->active = $request->active ? 1 : 0;
        $product->direct_access = $request->direct_access ? 1 : 0;
        $product->is_not_available = $request->is_not_available ? 1 : 0;

        $product->type = $request->type;
        $product->referral_cpa_link = $request->referral_cpa_link;

        if ($request->image_deleted && $product->image) {
            File::delete('images/cards/' . $product->image);
            $product->image = null;
        }

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            if ($product->image) {
                File::delete('images/cards/' . $product->image);
            }
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $product->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/cards/' . $name, File::get($image));
            $product->image =  $name;
        }

        $product->save();

        ProductsHelper::fillProductInterceptions($request->productBonuses, 'cards_bonuses', null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productCategories, 'cards_categories', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productCurrencies, 'cards_currencies', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productOptions, 'cards_options', null,  $product->id);
        ProductsHelper::fillProductInterceptions($request->productRegions, 'cards_regions',  null, $product->id);
        ProductsHelper::fillProductInterceptions($request->productSettlements, 'cards_settlements',  null, $product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($product);
        LogsHelper::addLogEntry($request, "cards", "update", $product, $old_product);
        YandexHelper::reportChanges("cards", $product , $old_product);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("cards", $product, $old_product);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_product = Card::findOrFail($id);
        $product = Card::findOrFail($id);
        $product->active =  $product->active ? 0 : 1;
        if ($product->active) $product->direct_access = 0;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($product);
        $this->addAllRelationships($old_product);
        LogsHelper::addLogEntry($request, "cards", "update", $product, $old_product);
        YandexHelper::reportChanges("cards", $product , $old_product);
    }

    public function toggleSpecialById(Request $request, $id)
    {
        $old_product = Card::findOrFail($id);
        $product = Card::findOrFail($id);
        $product->special =  $product->special ? 0 : 1;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "cards", "update", $product, $old_product);
    }
    
    public function updateSpecialPriorityById(Request $request, $id)
    {
        $old_product = Card::findOrFail($id);
        $product = Card::findOrFail($id);
        $product->special_priority_id = $request->special_priority_id;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "cards", "update", $product, $old_product);
    }

    public function updateSpecialLinkById(Request $request, $id)
    {
        $old_product = Card::findOrFail($id);
        $product = Card::findOrFail($id);
        $product->special_link = $request->special_link;
        $product->save();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "cards", "update", $product, $old_product);
    }

    public function deleteById(Request $request, $id)
    {   
        $old_product = Card::findOrFail($id);
        $this->addAllRelationships($old_product);

        $product = Card::findOrFail($id);
        ReviewsHelper::reassignProductReviewsToCreditor($product->id, $product->creditor_id, 'cards');
        if ($product->image) {
            File::delete('images/cards/' . $product->image);
        }
        $product->delete();
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "cards", "delete", null, $old_product);
        YandexHelper::reportChanges("cards", null , $old_product);
    }

    public function duplicateById(Request $request, $id)
    {
        $product_to_copy = Card::findOrFail($id);
        $new_product = $product_to_copy->replicate();
        $new_product->title = $new_product->title . ' КОПИЯ';
        if ($product_to_copy->image) {
            $image_to_copy = $product_to_copy->image;
            $file_extension = File::extension($image_to_copy);
            $new_image_name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $product_to_copy->id . '.' . $file_extension;

            $root_path_cards = 'images/cards/';
            File::copy($root_path_cards . $image_to_copy, $root_path_cards . $new_image_name);
            $new_product->image = $new_image_name;
        } else {
            $new_product->image = null;
        }

        $new_product->active = false;
        $new_product->save();
        ProductsHelper::duplicateProductInterceptions('cards_bonuses', $id,  $new_product->id);
        ProductsHelper::duplicateProductInterceptions('cards_categories', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('cards_currencies', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('cards_options', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('cards_regions', $id, $new_product->id);
        ProductsHelper::duplicateProductInterceptions('cards_settlements', $id, $new_product->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($new_product);
        LogsHelper::addLogEntry($request, "cards", "duplicate", $new_product);
        YandexHelper::reportChanges("cards", $new_product );
    }
}
