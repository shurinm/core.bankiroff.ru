<?php

namespace App\Http\Controllers;

use App\Models\Advertisements\Advertisement;
use App\Models\Advertisements\AdvertisementTypes;

use App\Helpers\SubDomainHelper;
use App\Helpers\RequestsHelper;
use App\Helpers\LogsHelper;
use Illuminate\Http\Request;

use App\User;
use stdClass;

class AdvertisementsController extends Controller
{
    public function getTypes(Request $request)
    {
        return AdvertisementTypes::selectFields($request->isKeyValue)->get();
    }
}
