<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SitemapsHelper;
use App\Helpers\SubDomainHelper;
use View;
use File;
use Response;

class SitemapsController extends Controller
{
    public function getIndex(Request $request)
    {
        // echo SitemapsHelper::getCreditorsIds(); exit();
        // echo SitemapsHelper::getListLinksByType('products', 831); exit();
        // echo json_encode(SitemapsHelper::getListLinksByType('reviews', 831)); exit();

        $subdomain = $request->header('Subdomain');
        $subdomain_id = SubDomainHelper::getSubdomainId($subdomain);
        if ($subdomain && !$subdomain_id) return abort(404, "Subdomain ($subdomain) does not exists in our database.");
        $subdomain_string = $subdomain ? $subdomain . '.' : '';
        $extras = [];
        /* Delete this when you want to activate all sitemaps for subdomains */
        if (!$subdomain_string) {
            $creditors_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('creditors', $subdomain_id);
            $reviews_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('reviews', $subdomain_id);
            $products_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('products', $subdomain_id);
            $extras = array_merge($creditors_divisions, $reviews_divisions, $products_divisions);
        } else  {
            $products_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('products', $subdomain_id);
            $reviews_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('reviews', $subdomain_id);
            $extras = array_merge($reviews_divisions, $products_divisions);
        }
        // $reviews_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('reviews', $subdomain_id);
        $statics_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('statics', $subdomain_id);
        $currencies_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('currencies', $subdomain_id);
        $blog_divisions = SitemapsHelper::buildDivisionsOfSitemapsByType('blog', $subdomain_id);
        $sitemaps = array_merge($statics_divisions, $currencies_divisions, $blog_divisions, $extras);
        // File::put('testmain.xml', $content->render());
        $content = View::make('sitemaps.index_template', ['items' => $sitemaps, 'subdomain' => $subdomain_string]);
        return Response::make($content)->header('Content-Type', 'text/xml;charset=utf-8');
        /*
        File::put('testmain.xml', $content->render());
        return response()->json($sitemaps);
        */
    }

    public function getLinksBySitemapTypeAndNumber(Request $request, $type, $number = 0)
    {
        $subdomain = $request->header('Subdomain');
        $subdomain_id = SubDomainHelper::getSubdomainId($subdomain);
        $subdomain_string = $subdomain ? $subdomain . '.' : '';
        if ($subdomain && !$subdomain_id) return abort(404, "Subdomain ($subdomain) does not exists in our database.");
        /* Delete this when you want to activate all sitemaps for subdomains */
        // if ($subdomain_id && ($type == 'creditors' || $type == 'products' || $type == 'reviews')) return abort(404, "For  ($subdomain) this sitemap is unactive");;
        if ($subdomain_id && ($type == 'creditors')) return abort(404, "For  ($subdomain) this sitemap is unactive");;
        if ($number && !is_numeric($number)) return abort(404, "Trying to open a sitemap with an non-numeric value.");
        if (!SitemapsHelper::getItemsAmountByType($type, $subdomain_id)) return abort(404, "There are no links to be listed for this sitemap.");
        if (SitemapsHelper::calculateNumberOfSitemaps(SitemapsHelper::getItemsAmountByType($type, $subdomain_id)) <= $number) return abort(404, "Trying to access to a sitemap that does not have any results.");
        $list_links = SitemapsHelper::getListLinksByType($type, $subdomain_id);

        if ($type != 'reviews' && $type != 'products') {
          $links = SitemapsHelper::getLinksByType($type, $subdomain_id, $number + 1);
        } else if ($subdomain_string == '' and ($type == 'reviews' || $type == 'products')) {
          $links = SitemapsHelper::getLinksByType($type, $subdomain_id, $number + 1);
          $links = array_merge($list_links, $links);
        } else {
          $links = $list_links;
        }
        // $links = SitemapsHelper::getLinksByType($type, $subdomain_id, $number + 1);

        $content = View::make('sitemaps.links_template', ['items' => $links, 'subdomain' => $subdomain_string]);
        return Response::make($content)->header('Content-Type', 'text/xml;charset=utf-8');
        /*
        File::put('test.xml', $content->render());
        return response()->json($links);
         */
    }
}
