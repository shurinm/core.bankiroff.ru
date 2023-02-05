<?php

namespace App\Helpers;

use Carbon\Carbon;

use App\Models\Creditors\Creditor;
use App\Models\Creditors\CreditorsPhone;

use App\Interceptions\CreditorsRegionsInterception;
use App\Interceptions\CreditorsSettlementsInterception;

use Illuminate\Support\Facades\Log;
use App\Models\Region;

use App\Models\Products\Cards\Card;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Microloan;
use App\Models\Products\Deposits\Deposit;

use App\Helpers\AddressesHelper;

class CreditorsHelper
{

    public static function fillInterceptions($items, $type = null, $new_id = null, $old_id = null)
    {
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_id) {
                CreditorsHelper::deleteInterceptions($type, $old_id);
                $new_id = $old_id;
            }
            foreach ($items as $key => $item) {
                switch ($type) {
                    case 'creditors_regions':
                        $model =  new CreditorsRegionsInterception();
                        $model->creditor_id = $new_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'creditors_settlements':
                        $model =  new CreditorsSettlementsInterception();
                        $model->creditor_id = $new_id;
                        $model->aoid =  $item['aoid'];
                        $model->save();
                        break;
                    case 'creditors_phones':
                        if (!$item) break;
                        $model =  new CreditorsPhone();
                        $model->creditor_id = $new_id;
                        $model->phone =  $item;
                        $model->save();
                        break;
                }
            }
        }
        return $items;
    }

    public static function addCreditor($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->creditor;
            }
        }
        return $items;
    }

    public static function addUser($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->user;
            }
        }
        return $items;
    }


    public static function deleteInterceptions($type = null, $id = null)
    {
        switch ($type) {
            case 'creditors_regions':
                CreditorsRegionsInterception::where('creditor_id', $id)->delete();
                break;
            case 'creditors_settlements':
                CreditorsSettlementsInterception::where('creditor_id', $id)->delete();
                break;
            case 'creditors_phones':
                CreditorsPhone::where('creditor_id', $id)->delete();
                break;
        }
    }

    public static function addRegions($items)
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

    public static function addSettlements($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $settlements =  $item->settlementsAsKeyValue;
                $settlements = AddressesHelper::addTitleByAoId($settlements);
            }
        }
        return $items;
    }

    public static function addSettlementsCount($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->settlements_count =  $item->settlementsAsKeyValue->count();
            }
        }
        return $items;
    }

    public static function replicateActionToAllProducts($creditorId, $field, $newStatus, $creditorFieldCopy)
    {
        if ($creditorFieldCopy == $newStatus) return true;
        error_log("PASSED replicateActionToAllProducts FOR $field");
        $cards = Card::where('creditor_id', $creditorId)->get();
        CreditorsHelper::activateOrDesactivate($cards, $field, $newStatus);

        $credits = Credit::where('creditor_id', $creditorId)->get();
        CreditorsHelper::activateOrDesactivate($credits, $field, $newStatus);

        $consumers = Consumer::where('creditor_id', $creditorId)->get();
        CreditorsHelper::activateOrDesactivate($consumers, $field, $newStatus);

        $microloans = Microloan::where('creditor_id', $creditorId)->get();
        CreditorsHelper::activateOrDesactivate($microloans, $field, $newStatus);

        $deposits = Deposit::where('creditor_id', $creditorId)->get();
        CreditorsHelper::activateOrDesactivate($deposits, $field, $newStatus);
    }


    public static function activateOrDesactivate($items, $field, $newStatus)
    {
        $field_reverse = $field == 'active' ? 'direct_access' : 'active';

        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                switch ($field) {
                    case 'active':
                        if ($newStatus) {
                            /// error_log("INSIDE IF FOR $field");
                            $item[$field] =  $item[$field . "_prev_status"];
                            $item[$field_reverse] =  0;
                        } else {
                            // error_log("ON ELSE FOR $field");
                            $item[$field . "_prev_status"] = $item[$field];
                            $item[$field] = 0;
                        }
                        break;
                    case 'direct_access':
                        if ($newStatus) {
                            /// error_log("INSIDE IF FOR $field");
                            $item[$field . "_prev_status"] = $item[$field];
                            $item[$field] =  1;
                            $item[$field_reverse] =  0;
                        } else {
                            // error_log("ON ELSE FOR $field");
                            $item[$field] =  $item[$field . "_prev_status"];
                        }
                }
                $item->save();
            }
        }
    }
}
