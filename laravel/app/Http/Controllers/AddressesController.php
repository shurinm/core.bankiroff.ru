<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/*----------------------------------------------------------------------------------------------------------------------------------------------- */
/* AddressesController is used for the address system. We have the latest information with FIAS format https://fias.nalog.ru/ */
/*----------------------------------------------------------------------------------------------------------------------------------------------- */
use App\Models\Fias\FiasAddrObj;
use App\Helpers\AddressesHelper;


class AddressesController extends Controller
{
    /* 
    Area - for us, it means  Республика, обл, Край, etc. 
    All of them can be seen in the table fias_socrbases with level 1
    */
    static $AREAS_LEVEL = 1;
    static $SETTLEMENTS_LEVEL = 6;

    public function getAreas(Request $request, $xnumber = 100)
    {

        $data = FiasAddrObj::selectFields($request->isKeyValue)->matchAreasLevel()->take($xnumber)->get();
        return $data;
    }

    public function getSettlements(Request $request, $xnumber = 200)
    {
        $data = FiasAddrObj::active()
            ->selectFields($request->isKeyValue)
            ->matchSearch($request->search)
            ->matchType($request->type)
            ->matchSettlementsLevel()
            ->take($xnumber)->get();
        return AddressesHelper::addTitle($data, self::$SETTLEMENTS_LEVEL);
    }
}
