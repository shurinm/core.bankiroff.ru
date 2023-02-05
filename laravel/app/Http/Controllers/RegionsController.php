<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Region;

use App\Helpers\LogsHelper;

/*----------------------------------------------------------------------------------------------------------------------------------------------- */
/* Controller for Regions (SEO and News) */
/*----------------------------------------------------------------------------------------------------------------------------------------------- */

class RegionsController extends Controller
{
    public function getRegions(Request $request)
    {
        return Region::selectFields($request->isKeyValue)->get();
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function getAll(Request $request, $xnumber = 10)
    {
        $regions = Region::matchNameLike($request->name ?? null)
            ->matchSubdomainLike($request->subdomain ?? null)
            ->matchActiveSubdomainState($request->activeSubdomain ?? null)
            ->paginateOrGet($request->page, $xnumber);
        return  $regions;
    }

    public function getByIdFull(Request $request, $id)
    {
        return Region::findOrFail($id);
    }

    public function add(Request $request)
    {
        $product = new Region();
        $product->name = $request->name;
        $product->genitive = $request->genitive;
        $product->dative = $request->dative;
        $product->prepositional = $request->prepositional;
        $product->subdomain = $request->subdomain;
        $product->active = $request->active ? 1 : 0;

        $product->area_name = $request->area_name;
        $product->area_genitive = $request->area_genitive;
        $product->area_dative = $request->area_dative;
        $product->area_prepositional = $request->area_prepositional;
        $product->is_active_subdomain = $request->is_active_subdomain ? 1 : 0;
        $product->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "seo.regions", "create", $product);
    }

    public function updateById(Request $request, $id)
    {
        $old_product = Region::findOrFail($id);

        $product = Region::findOrFail($id);
        $product->name = $request->name;
        $product->genitive = $request->genitive;
        $product->dative = $request->dative;
        $product->prepositional = $request->prepositional;
        $product->subdomain = $request->subdomain;
        $product->active = $request->active ? 1 : 0;

        $product->area_name = $request->area_name;
        $product->area_genitive = $request->area_genitive;
        $product->area_dative = $request->area_dative;
        $product->area_prepositional = $request->area_prepositional;
        $product->is_active_subdomain = $request->is_active_subdomain ? 1 : 0;
        $product->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "seo.regions", "update", $product, $old_product);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_product = Region::findOrFail($id);

        $product = Region::findOrFail($id);
        $product->active =  $product->active ? 0 : 1;
        $product->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "seo.regions", "update", $product, $old_product);
    }

    public function deleteById(Request $request, $id)
    {
        $old_product = Region::findOrFail($id);

        Region::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "seo.regions", "delete", null, $old_product);
    }
}
