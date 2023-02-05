<?php

namespace App\Helpers;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use App\Models\Fias\FiasSocrbase;
use App\Models\Fias\FiasAddrObj;
use App\Models\Region;

class AddressesHelper
{

    public static function addTitle($items, $level)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $socr_base = FiasSocrBase::where('scname', $item->type)->where('level', $level)->select('socrname')->first();
                if ($socr_base) {
                    $socr_name =  $socr_base ? $socr_base->socrname : 'Не определено';
                    $item->area;
                    $item->title = $item->name . " ($socr_name) / {$item->area->name} {$item->area->type}.";
                }
            }
        }
        return $items;
    }

    public static function addTitleByAoId($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $fias_addr_obj = FiasAddrObj::where('aoid', $item->aoid)->selectFields(null)->first();
                if ($fias_addr_obj) {
                    $socr_base = FiasSocrBase::where('scname', $fias_addr_obj->type)->where('level', 6)->select('socrname')->first();
                    $socr_name =  $socr_base ? $socr_base->socrname : 'Не определено';
                    $fias_addr_obj->area;
                    $item->title = $fias_addr_obj->name . " ($socr_name) / {$fias_addr_obj->area->name} {$fias_addr_obj->area->type}.";
                }
            }
        }
        return $items;
    }
    public static function addOneTitleByAoId($aoid){
        if (!$aoid)
            return null;
        $item =  array(
            (object) [
              'aoid' => $aoid
            ]); 
        return  self::addTitleByAoId($item)[0];
    }

    public static function addRegionMeaningAsKeyValue($items)
    {
        if ($items && count($items) > 0) {
            foreach ($items as $key => &$item) {
                $region_obj = Region::where('id', $item->value)->select('name')->first();
                $item->title = $region_obj ?  $region_obj->name : 'Не определено';
                $item->value = strval($item->value);
            }
        }

        return $items;
    }
}
