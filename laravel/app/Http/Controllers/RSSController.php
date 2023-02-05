<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\SubDomainHelper;
use App\Helpers\RSSHelper;
use View;
use File;
use Response;

class RSSController extends Controller
{
    public function getRSSByParams(Request $request, $type, $xDays)
    {
        /* $type: (articles or news) ; $xDays integer*/
        $subdomain = $request->header('Subdomain');
        $subdomain_id = SubDomainHelper::getSubdomainId($subdomain);
        if ($subdomain && !$subdomain_id) return abort(404, "Subdomain ($subdomain) does not exists in our database.");
        // if (!RSSHelper::getItemsAmountBySubdomain($subdomain_id, $type, $xDays)) return abort(404, "There are no items to be listed for this subdomain.");
        $subdomain_string = $subdomain ? $subdomain . '.' : '';
        $items = RSSHelper::getItemsBySubdomain($subdomain_id, $type, $xDays);
        $lastmod = RSSHelper::getLastModBySubdomain($subdomain_id, $type, $xDays);
        $content = View::make('rss.rss_template', ['items' => $items, 'subdomain' => $subdomain_string, 'lastmod' => $lastmod]);
        return Response::make($content)->header('Content-Type', 'text/xml;charset=utf-8');
    }
}
