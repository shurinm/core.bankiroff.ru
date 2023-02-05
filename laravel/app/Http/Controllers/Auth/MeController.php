<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Interceptions\Users\UsersCreditorsInterception;
// use App\Models\UsersSearch;

use App\Interceptions\RolesPermissionsInterception;
use App\Interceptions\UsersNewsTagsInterception;
use App\Models\Roles\Permission;

class MeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $interceptions = $user->my_creditors;
        foreach ($interceptions as $key => $interception) {
            $interception->creditor;
        };
        $user->my_creditors = $interceptions;
        $interceptions = $user->my_news_tags;
        foreach ($interceptions as $key => $interception) {
            $interception->newsTag;
        };
        $user->my_news_tags = $interceptions;
        $user->my_searches;
        $credits_requests = $user->my_credits_requests;
        foreach ($credits_requests as $key => $request) {
            $request->creditor;

            $request["product_type"] = $request["type_slug"];

            switch ($request["product_type"]) {
                case 'credits':
                    $request["product"] = $request->credit;
                    break;
                case 'microloans':
                    $request["product"] = $request->microloan;
                    break;
                case 'consumers':
                    $request["product"] = $request->consumer;
                    break;
            }

            if ($request->product) $request->product->creditor;
        };
        $cards_requests = $user->my_cards_requests;
        foreach ($cards_requests as $key => $request) {
            $request->creditor;
            if ($request->product) $request->product->creditor;
            $request["product_type"] = "cards";
        };
        $deposits_requests = $user->my_deposits_requests;
        foreach ($deposits_requests as $key => $request) {
            $request->creditor;
            $request->product;
            if ($request->product) $request->product->creditor;
            $request["product_type"] = "deposits";
        };

        if ($user->role_id != 2) {
            $permissions = RolesPermissionsInterception::where('role_id', $user->role_id)->get();
            foreach ($permissions as $key => $element) {
                $element->permission;
            }
            $user->my_permissions = $permissions;
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
