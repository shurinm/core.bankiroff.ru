<?php

namespace App\Helpers;

use App\Models\Creditors\Creditor;
use App\Models\Products\Cards\Card;
use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Credits\Microloan;
use App\Models\Products\Deposits\Deposit;

use App\Models\Products\Cards\CardsCategory;
use App\Models\Products\Cards\CardsOption;
use App\Models\Products\Cards\CardsBonus;

use App\Models\Currencies\Currency;

use App\Models\Products\Deposits\DepositsType;

use App\Models\Products\Deposits\DepositsCapitalization;
use App\Models\Products\Deposits\DepositsInterestPayment;
use App\Models\Products\Deposits\DepositsRatesTable;

use App\Models\Products\Credits\CreditsHistory;
use App\Models\Products\Credits\CreditsOccupation;
use App\Models\Products\Credits\CreditsProof;
use App\Models\Products\Credits\CreditsPledgesSlug;
use App\Models\Products\Credits\CreditsInsurance;
use App\Models\Products\Credits\CreditsRatesTable;
use App\Models\Products\Credits\CreditsExtraCategory;

use App\Models\Products\Credits\ConsumersPurpose;
use App\Models\Products\Credits\ConsumersRatesTable;
use App\Models\Products\Credits\ConsumersExtraCategory;

use App\Models\Products\Credits\MicroloansProvision;


use App\Models\Region;

use App\Interceptions\CardsBonusesInterception;
use App\Interceptions\CardsCategoriesInterception;
use App\Interceptions\CardsCurrenciesInterception;
use App\Interceptions\CardsOptionsInterception;
use App\Interceptions\CardsRegionsInterception;
use App\Interceptions\CardsSettlementsInterception;


use App\Interceptions\CreditsHistoriesInterception;
use App\Interceptions\CreditsOccupationsInterception;
use App\Interceptions\CreditsProofsInterception;
use App\Interceptions\CreditsRegionsInterception;
use App\Interceptions\CreditsSettlementsInterception;
use App\Interceptions\CreditsPledgesSlugsInterception;
use App\Interceptions\CreditsInsurancesInterception;

use App\Interceptions\ConsumersHistoriesInterception;
use App\Interceptions\ConsumersOccupationsInterception;
use App\Interceptions\ConsumersProofsInterception;
use App\Interceptions\ConsumersRegionsInterception;
use App\Interceptions\ConsumersSettlementsInterception;
use App\Interceptions\ConsumersInsurancesInterception;

use App\Interceptions\DepositsRegionsInterception;
use App\Interceptions\DepositsSettlementsInterception;
use App\Interceptions\DepositsCapitalizationsInterception;
use App\Interceptions\DepositsInterestPaymentsInterception;
use App\Interceptions\DepositsTypesInterception;

use App\Interceptions\MicroloansRegionsInterception;
use App\Interceptions\MicroloansSettlementsInterception;
use App\Interceptions\MicroloansProvisionsInterception;

use App\Helpers\AddressesHelper;

class ProductsHelper
{

    public static function addCardCategoryMeaning($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $card_category = CardsCategory::where('id', $item->card_category_id)->select('title')->first();
                $item->category_title = $card_category ? $card_category->title : 'Не определено';
            }
        }

        return $items;
    }

    public static function addCurrencyMeaning($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $currency_obj = Currency::where('id', $item->currency_id)->select('title')->first();
                $item->currency_title =  $currency_obj ?  $currency_obj->title : 'Не определено';
            }
        }

        return $items;
    }

    public static function addCardCategoriesMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $category_obj = CardsCategory::where('id', $item->value)->select('title')->first();
                $item->title = $category_obj ?  $category_obj->title : 'Не определено';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCurrenciesMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $currency_obj = Currency::where('id', $item->value)->select('title')->first();
                $item->title = $currency_obj ?  $currency_obj->title : 'Не определено';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCardOptionsMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $option_obj = CardsOption::where('id', $item->value)->select('title')->first();
                $item->title = $option_obj ?  $option_obj->title : 'Не определено';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCardBonusesMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $bonus_obj = CardsBonus::where('id', $item->value)->select('title')->first();
                $item->title = $bonus_obj ?  $bonus_obj->title : 'Не определено';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addCapitalizationMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->title = (DepositsCapitalization::where('id', $item->value)->select('title')->first())->title;
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addInterestPaymentMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->title = (DepositsInterestPayment::where('id', $item->value)->select('title')->first())->title;
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addTypesAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $element = DepositsType::where('id', $item->value)->select('title')->first();
                $item->title = $element ? $element->title : 'Undefined';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addProvisionMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->title = (MicroloansProvision::where('id', $item->value)->select('title')->first())->title;
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addCreditsProofMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $element = CreditsProof::where('id', $item->value)->select('title')->first();
                $item->title = $element ? $element->title : 'Undefined';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCreditsOccupationMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $element = CreditsOccupation::where('id', $item->value)->select('title')->first();
                $item->title = $element ? $element->title : 'Undefined';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCreditsPledgeMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->title = (CreditsPledgesSlug::where('slug', $item->value)->select('title')->first())->title;
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addCreditsInsuranceMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $element = CreditsInsurance::where('id', $item->value)->select('title')->first();
                $item->title = $element ? $element->title : 'Undefined';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
    public static function addCreditsHistoriesMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $element = CreditsHistory::where('id', $item->value)->select('title')->first();
                $item->title = $element ? $element->title : 'Undefined';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addDepositTypeMeaning($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->type_title = DepositsType::where('id', $item->type_id)->select('title')->first() ? (DepositsType::where('id', $item->type_id)->select('title')->first())->title : 'Не определено';
            }
        }

        return $items;
    }

    public static function addCreditorToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->creditor;
            }
        }
        return $items;
    }

    public static function addRegionsToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $interceptions = $item->regions;
                foreach ($interceptions as $key => &$interception) {
                    $interception->region_title = (Region::where('id', $interception->region_id)->select('name')->first())->name;
                }
            }
        }
        return $items;
    }

    public static function addTypesToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $interceptions = $item->types;
                foreach ($interceptions as $key => &$interception) {
                    $interception->type_title = ( DepositsType::where('id', $interception->deposit_type_id)->select('title')->first())->title;
                }
            }
        }
        return $items;
    }

    public static function addSettlementsToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $settlements =  $item->settlementsAsKeyValue;
                $settlements = AddressesHelper::addTitleByAoId($settlements);
            }
        }
        return $items;
    }

    public static function addSettlementsCountToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->settlements_count = $item->settlementsAsKeyValue->count();
            }
        }
        return $items;
    }

    public static function addCurrenciesToProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $interceptions = $item->currencies;
                foreach ($interceptions as $key => &$interception) {
                    $interception->currency_title = (Currency::where('id', $interception->currency_id)->select('title')->first()) ? (Currency::where('id', $interception->currency_id)->first())->iso_name : 'Не определено';
                }
            }
        }
        return $items;
    }


    public static function addTypeProduct($items, $type = null)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item['type_product'] = $type;
            }
        }
        return $items;
    }

    public static function addProductByProductType($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                switch ($item['type_product']) {
                    case 'credits':
                        $product = Credit::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                    case 'consumers':
                        $product = Consumer::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                    case 'microloans':
                        $product = Microloan::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                    case 'cards':
                        $product = Card::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                    case 'deposits':
                        $product = Deposit::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                    case 'creditors':
                        $product = Creditor::active()->where('id', $item['value'])->first();
                        $product->creditor;
                        $item['product'] = $product;
                        break;
                }
            }
        }
        return $items;
    }

    public static function buildHumanReadableTypeProduct($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product'], $item['type_slug'] ?? null, $item['pledge_slug'] ?? null);
            }
        }
        return $items;
    }

    public static function formatProductTitleAndProductType($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item['display_title'] = $item['title'] . " / " . $item['type_human_readable'];
            }
        }
        return $items;
    }

    public static function addTitleByItemIdAndType($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                switch ($item['type_product']) {
                    case 'credits':
                        $product = Credit::active()->where('id', $item['value'])->select("id as value", "title", "type_slug", "pledge_slug")->first();
                        $item['title'] = $product->title;
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product'], $product->type_slug, $product->pledge_slug);
                        break;
                    case 'consumers':
                        $product = Consumer::active()->where('id', $item['value'])->select("id as value", "title")->first();
                        $item['title'] = $product->title;
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product']);
                        break;
                    case 'microloans':
                        $product = Microloan::active()->where('id', $item['value'])->select("id as value", "title")->first();
                        $item['title'] = $product->title;
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product']);
                        break;
                    case 'cards':
                        $product = Card::active()->where('id', $item['value'])->select("id as value", "title", "type as type_slug")->first();
                        $item['title'] = $product->title;
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product'], $product->type_slug);
                        break;
                    case 'deposits':
                        $product = Deposit::active()->where('id', $item['value'])->select("id as value", "title")->first();
                        $item['title'] = $product->title;
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product']);
                        break;
                    case 'creditors':
                        $product = Creditor::active()->where('id', $item['value'])->select("id as value", "name as title", "type_slug")->first();
                        $item['title'] = $product->title;
                        // dd($product->title);
                        $item['type_human_readable'] = ProductsHelper::getMeaningTypeProductByType($item['type_product'], $product->type_slug);
                        break;
                }
            }
        }
        $items = ProductsHelper::formatProductTitleAndProductType($items);
        return $items;
    }

    public static function getMeaningTypeProductByType($type, $typeSlug = null, $pledgeSlug = null)
    {
        switch ($type) {
            case 'credits':
                if ($typeSlug === 'mortgage') {
                    return 'Ипотечный кредит';
                } else if ($typeSlug === 'refinancing') {
                    return 'Рефинансирование';
                } else if ($pledgeSlug === 'rooms') {
                    return 'Кредит под залог комнаты';
                } else if ($pledgeSlug === 'shared') {
                    return 'Кредит под залог доли недвижимости';
                } else if ($pledgeSlug === 'house') {
                    return 'Кредит под залог дома';
                } else if ($pledgeSlug === 'townhouse') {
                    return 'Кредит под залог таунхауса';
                } else if ($pledgeSlug === 'parcels') {
                    return 'Кредит под залог участка';
                } else if ($pledgeSlug === 'apartments') {
                    return 'Кредит под залог апартаментов';
                } else if ($pledgeSlug === 'commercial') {
                    return 'Кредит под залог коммерческой недвижимости';
                } else if ($pledgeSlug === 'flats') {
                    return 'Кредит под залог квартиры';
                } else if ($pledgeSlug === 'auto') {
                    return 'Кредит под залог автомобиля';
                } else {
                    return 'Кредит';
                }
            case 'consumers':
                return 'Потребительский кредит';
                break;
            case 'microloans':
                return 'Микрозайм';
                break;
            case 'cards':
                if ($typeSlug === 'debit') {
                    return 'Дебетовая карта';
                }
                return 'Кредитная карта';
                break;
            case 'deposits':
                return 'Вклад';
                break;
            case 'creditors':
                if ($typeSlug === 'banks') {
                    return 'Банк';
                } else if ($typeSlug === 'mfo') {
                    return 'МФО';
                } else if ($typeSlug === 'pawnshops') {
                    return 'Ломбард';
                }
                break;
        }

        return '';
    }

    public static function fillProductInterceptions($items, $type = null, $new_product_id = null, $old_product_id = null)
    {
        if ($items == "DELETE") ProductsHelper::deleteProductInterceptions($type, $old_product_id);
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_product_id) {
                ProductsHelper::deleteProductInterceptions($type, $old_product_id);
                $new_product_id = $old_product_id;
            }

            foreach ($items as $key => $item) {
                switch ($type) {
                        /* Credits */
                    case 'credits_histories':
                        $model =  new CreditsHistoriesInterception();
                        $model->credit_id = $new_product_id;
                        $model->credit_history_id = $item['value'];
                        $model->save();
                        break;
                    case 'credits_proofs':
                        $model =  new CreditsProofsInterception();
                        $model->credit_id = $new_product_id;
                        $model->credit_proof_id = $item['value'];
                        $model->save();
                        break;
                    case 'credits_occupations':
                        $model =  new CreditsOccupationsInterception();
                        $model->credit_id = $new_product_id;
                        $model->credit_occupation_id = $item['value'];
                        $model->save();
                        break;
                    case 'credits_regions':
                        $model =  new CreditsRegionsInterception();
                        $model->credit_id = $new_product_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'credits_settlements':
                        $model =  new CreditsSettlementsInterception();
                        $model->credit_id = $new_product_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                    case 'credits_pledges':
                        $model = new CreditsPledgesSlugsInterception();
                        $model->credit_id = $new_product_id;
                        $model->credit_pledge_slug =  $item['value'];
                        $model->save();
                        break;
                    case 'credits_insurances':
                        $model = new CreditsInsurancesInterception();
                        $model->credit_id = $new_product_id;
                        $model->insurance_id =  $item['value'];
                        $model->save();
                        break;
                        /* Consumers */
                    case 'consumers_histories':
                        $model =  new ConsumersHistoriesInterception();
                        $model->consumer_id = $new_product_id;
                        $model->credit_history_id = $item['value'];
                        $model->save();
                        break;
                    case 'consumers_proofs':
                        $model =  new ConsumersProofsInterception();
                        $model->consumer_id = $new_product_id;
                        $model->credit_proof_id = $item['value'];
                        $model->save();
                        break;
                    case 'consumers_occupations':
                        $model =  new ConsumersOccupationsInterception();
                        $model->consumer_id = $new_product_id;
                        $model->credit_occupation_id = $item['value'];
                        $model->save();
                        break;
                    case 'consumers_insurances':
                        $model =  new ConsumersInsurancesInterception();
                        $model->consumer_id = $new_product_id;
                        $model->insurance_id =  $item['value'];
                        $model->save();
                        break;
                    case 'consumers_regions':
                        $model =  new ConsumersRegionsInterception();
                        $model->consumer_id = $new_product_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'consumers_settlements':
                        $model =  new ConsumersSettlementsInterception();
                        $model->consumer_id = $new_product_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                        /* Microloans */
                    case 'microloans_regions':
                        $model =  new MicroloansRegionsInterception();
                        $model->microloan_id = $new_product_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'microloans_settlements':
                        $model =  new MicroloansSettlementsInterception();
                        $model->microloan_id = $new_product_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                    case 'microloans_provisions':
                        $model =  new MicroloansProvisionsInterception();
                        $model->microloan_id = $new_product_id;
                        $model->provision_id =  $item['value'];
                        $model->save();
                        break;
                        /* Deposits */
                    case 'deposits_regions':
                        $model =  new DepositsRegionsInterception();
                        $model->deposit_id = $new_product_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'deposits_settlements':
                        $model =  new DepositsSettlementsInterception();
                        $model->deposit_id = $new_product_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                    case 'deposits_types':
                        $model =  new DepositsTypesInterception();
                        $model->deposit_id = $new_product_id;
                        $model->deposit_type_id =  $item['value'];
                        $model->save();
                        break;
                    case 'deposits_capitalizations':
                        $model =  new DepositsCapitalizationsInterception();
                        $model->deposit_id = $new_product_id;
                        $model->capitalization_id =  $item['value'];
                        $model->save();
                        break;
                    case 'deposits_interest_payments':
                        $model =  new DepositsInterestPaymentsInterception();
                        $model->deposit_id = $new_product_id;
                        $model->interest_payment_id =  $item['value'];
                        $model->save();
                        break;
                        /* Cards */
                    case 'cards_bonuses':
                        $model =  new CardsBonusesInterception();
                        $model->card_id = $new_product_id;
                        $model->card_bonus_id =  $item['value'];
                        $model->save();
                        break;
                    case 'cards_categories':
                        $model =  new CardsCategoriesInterception();
                        $model->card_id = $new_product_id;
                        $model->card_category_id =  $item['value'];
                        $model->save();
                        break;
                    case 'cards_currencies':
                        $model =  new CardsCurrenciesInterception();
                        $model->card_id = $new_product_id;
                        $model->currency_id =  $item['value'];
                        $model->save();
                        break;
                    case 'cards_options':
                        $model =  new CardsOptionsInterception();
                        $model->card_id = $new_product_id;
                        $model->card_option_id =  $item['value'];
                        $model->save();
                        break;
                    case 'cards_regions':
                        $model =  new CardsRegionsInterception();
                        $model->card_id = $new_product_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'cards_settlements':
                        $model =  new CardsSettlementsInterception();
                        $model->card_id = $new_product_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                }
            }
        }
        return $items;
    }


    public static function duplicateProductInterceptions($type, $id = null, $new_product_id)
    {

        switch ($type) {
                /* Credits */
            case 'credits_histories':
                $interceptions =  CreditsHistoriesInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_proofs':
                $interceptions =  CreditsProofsInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_occupations':
                $interceptions =  CreditsOccupationsInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_regions':
                $interceptions =  CreditsRegionsInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_settlements':
                $interceptions =  CreditsSettlementsInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_pledges':
                $interceptions =  CreditsPledgesSlugsInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_insurances':
                $interceptions =  CreditsInsurancesInterception::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
                /* Consumers */
            case 'consumers_histories':
                $interceptions =  ConsumersHistoriesInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_proofs':
                $interceptions =  ConsumersProofsInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_occupations':
                $interceptions =  ConsumersOccupationsInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_regions':
                $interceptions =  ConsumersRegionsInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_settlements':
                $interceptions =  ConsumersSettlementsInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_insurances':
                $interceptions =  ConsumersInsurancesInterception::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
                /* Microloans */
            case 'microloans_regions':
                $interceptions =  MicroloansRegionsInterception::where('microloan_id', $id)->get();
                $product_key = 'microloan_id';
                break;
            case 'microloans_settlements':
                $interceptions =  MicroloansSettlementsInterception::where('microloan_id', $id)->get();
                $product_key = 'microloan_id';
                break;
            case 'microloans_provisions':
                $interceptions =  MicroloansProvisionsInterception::where('microloan_id', $id)->get();
                $product_key = 'microloan_id';
                break;
                /* Deposits */
            case 'deposits_regions':
                $interceptions =  DepositsRegionsInterception::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
            case 'deposits_settlements':
                $interceptions =  DepositsSettlementsInterception::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
            case 'deposits_types':
                $interceptions =  DepositsTypesInterception::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
            case 'deposits_capitalizations':
                $interceptions =  DepositsCapitalizationsInterception::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
            case 'deposits_interest_payments':
                $interceptions =  DepositsInterestPaymentsInterception::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
                /* Cards */
            case 'cards_bonuses':
                $interceptions =  CardsBonusesInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
            case 'cards_categories':
                $interceptions =  CardsCategoriesInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
            case 'cards_currencies':
                $interceptions =  CardsCurrenciesInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
            case 'cards_options':
                $interceptions =  CardsOptionsInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
            case 'cards_regions':
                $interceptions =  CardsRegionsInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
            case 'cards_settlements':
                $interceptions =  CardsSettlementsInterception::where('card_id', $id)->get();
                $product_key = 'card_id';
                break;
        }
        if ($interceptions && count($interceptions) > 0) {
            foreach ($interceptions as $key => $element) {
                $new_element = $element->replicate();
                $new_element[$product_key] = $new_product_id;
                $new_element->save();
            }
        }
    }

    public static function deleteProductInterceptions($type = null, $id = null)
    {

        switch ($type) {
                /* Credits */
            case 'credits_histories':
                CreditsHistoriesInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_proofs':
                CreditsProofsInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_occupations':
                CreditsOccupationsInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_regions':
                CreditsRegionsInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_settlements':
                CreditsSettlementsInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_pledges':
                CreditsPledgesSlugsInterception::where('credit_id', $id)->delete();
                break;
            case 'credits_insurances':
                CreditsInsurancesInterception::where('credit_id', $id)->delete();
                break;
                /* Consumers */
            case 'consumers_histories':
                ConsumersHistoriesInterception::where('consumer_id', $id)->delete();
                break;
            case 'consumers_proofs':
                ConsumersProofsInterception::where('consumer_id', $id)->delete();
                break;
            case 'consumers_occupations':
                ConsumersOccupationsInterception::where('consumer_id', $id)->delete();
                break;
            case 'consumers_regions':
                ConsumersRegionsInterception::where('consumer_id', $id)->delete();
                break;
            case 'consumers_settlements':
                ConsumersSettlementsInterception::where('consumer_id', $id)->delete();
                break;
            case 'consumers_insurances':
                ConsumersInsurancesInterception::where('consumer_id', $id)->delete();
                break;
                /* Microloans */
            case 'microloans_regions':
                MicroloansRegionsInterception::where('microloan_id', $id)->delete();
                break;
            case 'microloans_settlements':
                MicroloansSettlementsInterception::where('microloan_id', $id)->delete();
                break;
            case 'microloans_provisions':
                MicroloansProvisionsInterception::where('microloan_id', $id)->delete();
                break;
                /* Deposits */
            case 'deposits_regions':
                DepositsRegionsInterception::where('deposit_id', $id)->delete();
                break;
            case 'deposits_settlements':
                DepositsSettlementsInterception::where('deposit_id', $id)->delete();
                break;
            case 'deposits_types':
                DepositsTypesInterception::where('deposit_id', $id)->delete();
                break;
            case 'deposits_capitalizations':
                DepositsCapitalizationsInterception::where('deposit_id', $id)->delete();
                break;
            case 'deposits_interest_payments':
                DepositsInterestPaymentsInterception::where('deposit_id', $id)->delete();
                break;
                /* Cards */
            case 'cards_bonuses':
                CardsBonusesInterception::where('card_id', $id)->delete();
                break;
            case 'cards_categories':
                CardsCategoriesInterception::where('card_id', $id)->delete();
                break;
            case 'cards_currencies':
                CardsCurrenciesInterception::where('card_id', $id)->delete();
                break;
            case 'cards_options':
                CardsOptionsInterception::where('card_id', $id)->delete();
                break;
            case 'cards_regions':
                CardsRegionsInterception::where('card_id', $id)->delete();
                break;
            case 'cards_settlements':
                CardsSettlementsInterception::where('card_id', $id)->delete();
                break;
        }
    }

    public static function fillProductTables($items, $type = null, $new_product_id = null, $old_product_id = null)
    {
        if ($items == "DELETE") ProductsHelper::deleteProductTables($type, $old_product_id);
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_product_id) {
                ProductsHelper::deleteProductTables($type, $old_product_id);
                $new_product_id = $old_product_id;
            }

            foreach ($items as $key => $item) {
                switch ($type) {
                        /* Credits */
                    case 'credits_rate_tables':
                        $model =  new CreditsRatesTable();
                        $model->credit_id = $new_product_id;
                        $model->sum_min = $item['sum_min'] ?? null;
                        $model->sum_max = $item['sum_max'] ?? null;
                        $model->years_min = $item['years_min'] ?? null;
                        $model->months_min = $item['months_min'] ?? null;
                        $model->days_min = $item['days_min'] ?? null;
                        $model->years_max = $item['years_max'] ?? null;
                        $model->months_max = $item['months_max'] ?? null;
                        $model->days_max = $item['days_max'] ?? null;
                        $model->percent_min = $item['percent_min'] ?? null;
                        $model->percent = $item['percent'] ?? null;
                        $model->percent_max = $item['percent_max'] ?? null;
                        $model->save();
                        break;
                    case 'credits_extra_categories':
                        $model =  new CreditsExtraCategory();
                        $model->credit_id = $new_product_id;
                        $model->type_slug = $item['type_slug'] ?? null;
                        $model->pledge_slug = $item['pledge_slug'] ?? null;
                        $model->save();
                        break;
                        /* Consumers */
                    case 'consumers_rate_tables':
                        $model =  new ConsumersRatesTable();
                        $model->consumer_id = $new_product_id;
                        $model->sum_min = $item['sum_min'] ?? null;
                        $model->sum_max = $item['sum_max'] ?? null;
                        $model->years_min = $item['years_min'] ?? null;
                        $model->months_min = $item['months_min'] ?? null;
                        $model->days_min = $item['days_min'] ?? null;
                        $model->years_max = $item['years_max'] ?? null;
                        $model->months_max = $item['months_max'] ?? null;
                        $model->days_max = $item['days_max'] ?? null;
                        $model->percent_min = $item['percent_min'] ?? null;
                        $model->percent = $item['percent'] ?? null;
                        $model->percent_max = $item['percent_max'] ?? null;
                        $model->save();
                        break;
                    case 'consumers_extra_categories':
                        $model =  new ConsumersExtraCategory();
                        $model->consumer_id = $new_product_id;
                        $model->type_slug = $item['type_slug'] ?? null;
                        $model->pledge_slug = $item['pledge_slug'] ?? null;
                        $model->save();
                        break;
                        /* Deposits */
                    case 'deposits_rate_tables':
                        $model =  new DepositsRatesTable();
                        $model->deposit_id = $new_product_id;
                        $model->sum_min = $item['sum_min'] ?? null;
                        $model->sum_max = $item['sum_max'] ?? null;
                        $model->years_min = $item['years_min'] ?? null;
                        $model->months_min = $item['months_min'] ?? null;
                        $model->days_min = $item['days_min'] ?? null;
                        $model->years_max = $item['years_max'] ?? null;
                        $model->months_max = $item['months_max'] ?? null;
                        $model->days_max = $item['days_max'] ?? null;
                        $model->percent_min = $item['percent_min'] ?? null;
                        $model->percent = $item['percent'] ?? null;
                        $model->percent_max = $item['percent_max'] ?? null;
                        $model->currency_id = $item['currency_id'] ? $item['currency_id']['value'] : null;
                        $model->save();
                        break;
                }
            }
        }
        return $items;
    }

    public static function duplicateProductTables($type, $id = null, $new_product_id)
    {

        switch ($type) {
                /* Credits */
            case 'credits_rate_tables':
                $tables =  CreditsRatesTable::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
            case 'credits_extra_categories':
                $tables =  CreditsExtraCategory::where('credit_id', $id)->get();
                $product_key = 'credit_id';
                break;
                /* Consumers */
            case 'consumers_rate_tables':
                $tables =  ConsumersRatesTable::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
            case 'consumers_extra_categories':
                $tables =  ConsumersExtraCategory::where('consumer_id', $id)->get();
                $product_key = 'consumer_id';
                break;
                /*Deposits */
            case 'deposits_rate_tables':
                $tables =  DepositsRatesTable::where('deposit_id', $id)->get();
                $product_key = 'deposit_id';
                break;
        }
        if ($tables && count($tables) > 0) {
            foreach ($tables as $key => $element) {
                $new_element = $element->replicate();
                $new_element[$product_key] = $new_product_id;
                $new_element->save();
            }
        }
    }

    public static function deleteProductTables($type = null, $id = null)
    {

        switch ($type) {
                /* Credits */
            case 'credits_rate_tables':
                CreditsRatesTable::where('credit_id', $id)->delete();
                break;
            case 'credits_extra_categories':
                CreditsExtraCategory::where('credit_id', $id)->delete();
                break;
                /* Consumers */
            case 'consumers_rate_tables':
                ConsumersRatesTable::where('consumer_id', $id)->delete();
                break;
            case 'consumers_extra_categories':
                ConsumersExtraCategory::where('consumer_id', $id)->delete();
                break;
                /* Deposits */
            case 'deposits_rate_tables':
                DepositsRatesTable::where('deposit_id', $id)->delete();
                break;
        }
    }

    public static function creditPaymentInMonths($period, $period_type, $amount, $percent)
    {
        $period = (int) $period;
        $percent = $percent && $percent != 0.00? $percent : 1;
        $amount = (int) $amount;
        $amount = $amount ? $amount : 100000;
        $p = $amount;
        $i = $percent / 100 / 12;
        $period = $period ? $period : 1;
        $n = $period ? $period : 1;

        if ($period_type == 'years') {
            $n = $period * 12;
        }

        if ($period_type == "days") {
            $total = $amount + $amount * $percent * $period / 100;
            $per_month = $total / round($period / 30);
        } else {
            $per_month = $p * $i * (pow(1 + $i, $n)) / (pow(1 + $i, $n) - 1);
        }
        $per_month = round($per_month);
        $per_month = is_finite($per_month) ? $per_month : 0;
        return $per_month;
    }

    public static function addPeriodInRussian($years_min, $years_max, $months_min, $months_max, $days_min, $days_max)
    {
        $years = (($years_min && $years_max) && ($years_min == $years_max)) ? $years_max : false;
        $months = (($months_min && $months_max) && ($months_min == $months_max)) ? $months_max : false;
        $days = (($days_min && $days_max) && ($days_min == $days_max)) ? $days_max : false;

        if ($years && $months && $days) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($years_min, 'year') . ProductsHelper::buildHTMLSecondCase($months_min, 'month') . ProductsHelper::buildHTMLSecondCase($days_min, 'days');
        } elseif ($years && $months && !$days_min && !$days_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($years_min, 'year') . ProductsHelper::buildHTMLSecondCase($months_min, 'month');
        } elseif ($years && $days && !$months_min && !$months_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($years_min, 'year') . ProductsHelper::buildHTMLSecondCase($days_min, 'days');
        } elseif ($years && !$months_min && !$months_max && !$days_min && !$days_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($years_min, 'year');
        } elseif ($months && $days &&  !$years_min && !$years_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($months_min, 'month') . ProductsHelper::buildHTMLSecondCase($days_min, 'days');
        } elseif ($months && !$years_min && !$years_max && !$days_min && !$days_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($months_min, 'month');
        } elseif ($days && !$years_min && !$years_max && !$months_min && !$months_max) {
            $periodHtml = ProductsHelper::buildHTMLSecondCase($days_min, 'days');
        } else {
            $periodHtml = "От " . ProductsHelper::buildHTML($years_min, 'year') . ProductsHelper::buildHTML($months_min, 'month') . ProductsHelper::buildHTML($days_min, 'days') . "<span>до " . ProductsHelper::buildHTML($years_max, 'year') . ProductsHelper::buildHTML($months_max, 'month') . ProductsHelper::buildHTML($days_max, 'days') . '</span>';
        }
        return $periodHtml;
    }

    public static function buildHTML($item, $type)
    {
        $item_string = strval($item);
        $last_charact = substr($item_string, -1);

        if ($item) {
            if ($item == 1 || $item > 20 && $last_charact == 1 && $item != 111) {
                return $type == 'year' ? $item . "&nbsp;года " : ($type == 'month' ? $item . '&nbsp;месяца ' : $item . "&nbsp;дня ");
            } else {
                return $type == 'year' ? $item . "&nbsp;лет " : ($type == 'month' ? $item . '&nbsp;месяцев ' : $item . "&nbsp;дней ");
            }
        }
        return '';
    }

    public static function buildHTMLSecondCase($item, $type)
    {
        $item_string = strval($item);
        $last_charact = substr($item_string, -1);

        if ($item) {
            if ($item == 1 || $item > 20 && $last_charact == 1 && $item != 111) {
                return $type == 'year' ? $item . "&nbsp;год " : ($type == 'month' ? $item . '&nbsp;месяц ' : $item . "&nbsp;день ");
            } else if ($item > 4) {
                return $type == 'year' ? $item . "&nbsp;лет " : ($type == 'month' ? $item . '&nbsp;месяцев ' : $item . "&nbsp;дней ");
            } else {
                return $type == 'year' ? $item . "&nbsp;года " : ($type == 'month' ? $item . '&nbsp;месяца ' : $item . "&nbsp;дня ");
            }
        }
        return '';
    }
}
