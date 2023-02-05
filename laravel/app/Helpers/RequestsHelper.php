<?php

namespace App\Helpers;

use App\Interceptions\AdvertisementsRequestsTypesInterception;

class RequestsHelper
{

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

    public static function addRegion($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->region;
            }
        }
        return $items;
    }
    
    public static function fillAdvertisementsInterceptions($items, $id)
    {
        if (!$items) return false;
        if ($items && count($items) > 0) {
            foreach ($items as $key => $item) {
                $model =  new AdvertisementsRequestsTypesInterception();
                $model->advertisement_request_id = $id;
                $model->advertisement_type_id =  $item['value'];
                $model->save();
            }
        }
        return $items;
    }
}
