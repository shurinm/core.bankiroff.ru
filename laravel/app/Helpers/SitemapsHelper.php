<?php

namespace App\Helpers;

use App\Models\Products\Cards\Card;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Microloan;
use App\Models\Products\Deposits\Deposit;
use App\Models\Creditors\Creditor;
use App\Models\Currencies\Currency;
use App\Models\News\News;
use App\Models\Reviews\Review;

use App\User;

use App\Helpers\SeoHelper;
use App\Helpers\ProductsHelper;
use App\Helpers\ReviewsHelper;
use Carbon\Carbon;
use DB;

class SitemapsHelper
{
  static $MAX_ITEMS_PER_SITEMAP = 50000;

  /*
    Default: 1000 * 60 * 15 (15 Minutes) -> This will happen  if you do not return a cacheTime parameter.
    What cacheTime does? It defines how frequently sitemap routes should be updated (value in milliseconds).

    CONSIDERATIONS:
    1. Setting a negative value will disable the cache.
    2. All cache logic happens on the client.
    */
  static $SITEMAPS_CACHE_TIME = -1;

  static $HOUR_STATIC_UPDATE = 8;

  static $CHANGE_FREQ_PRODUCTS = 'weekly';
  static $PRIORITY_PRODUCTS = 0.8;

  static $CHANGE_FREQ_CREDITORS = 'weekly';
  static $PRIORITY_CREDITORS = 0.7;

  static $CHANGE_FREQ_BLOG = 'always';
  static $PRIORITY_BLOG = 1;

  static $CHANGE_FREQ_REVIEWS = 'daily';
  static $PRIORITY_REVIEWS = 0.9;

  public static function buildDivisionsOfSitemapsByType($type, $subdomain_id)
  {
    switch ($type) {
      case 'statics':
        $local_type = 'statics';
        $items = SitemapsHelper::getItemsAmountByType('statics', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
      case 'products':
        $local_type = 'products';
        $items = SitemapsHelper::getItemsAmountByType('products', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
      case 'creditors':
        $local_type = 'creditors';
        $items = SitemapsHelper::getItemsAmountByType('creditors', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
      case 'blog':
        $local_type = 'blog';
        $items = SitemapsHelper::getItemsAmountByType('blog', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
      case 'reviews':
        $local_type = 'reviews';
        $items = SitemapsHelper::getItemsAmountByType('reviews', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
      case 'currencies':
        $local_type = 'currencies';
        $items = SitemapsHelper::getItemsAmountByType('currencies', $subdomain_id);
        $number_of_sitemaps = SitemapsHelper::calculateNumberOfSitemaps($items);
        break;
    }
    $arr = [];
    for ($x = 0; $x < $number_of_sitemaps; $x++) {
      if ($x == 0) {
        /*
              $routes_for_division =  SitemapsHelper::getLinksByType($local_type, $subdomain_id, 1);
              If you need to send an array of routes with should not be updated, add the following parameter.
              "routes" => $routes_for_division
              */
        $lastmod = SitemapsHelper::getLastestUpdatedDateByType($local_type, $subdomain_id);
        $sitemap_element = array("path" => 'sitemap-' . $local_type, "type" => $local_type, "lastmod" => $lastmod);
        array_push($arr, $sitemap_element);
      } else {
        /*
              $routes_for_division = SitemapsHelper::getLinksByType($local_type, $subdomain_id, $x + 1);
              If you need to send an array of routes with should not be updated, add the following parameter.
              "routes" => $routes_for_division
              */
        $lastmod = SitemapsHelper::getLastestUpdatedDateByType($local_type, $subdomain_id);
        $sitemap_element = array("path" => 'sitemap-' . $local_type . "_" . $x, "type" => $local_type, "lastmod" => $lastmod);
        array_push($arr, $sitemap_element);
      }
    }
    return $arr;
  }

  public static function getItemsAmountByType($type, $subdomain_id)
  {
    $count = 0;

    switch ($type) {
      case 'statics':
        $count = count(SitemapsHelper::getStaticRoutes());
        break;
      case 'products':
        $count += Credit::active()->matchSubdomain($subdomain_id)->count();
        $count += Consumer::active()->matchSubdomain($subdomain_id)->count();
        $count += Microloan::active()->matchSubdomain($subdomain_id)->count();
        $count += Deposit::active()->matchSubdomain($subdomain_id)->count();
        $count += Card::active()->matchSubdomain($subdomain_id)->count();
        break;
      case 'creditors':
        $count = Creditor::active()->matchSubdomain($subdomain_id)->count();
        break;
      case 'blog':
        $count = News::publishedAndActive()->matchSubdomainRuleSitemaps($subdomain_id)->count();
        break;
      case 'reviews':
        $count = Review::active()->count();
        break;
      case 'currencies':
        $count = count(SitemapsHelper::getCurrenciesRoutes());
        break;
    }
    return $count;
  }

  public static function getLinksByType($type, $subdomain_id, $page)
  {
    switch ($type) {
      case 'products':
        $credits = Credit::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate()
          ->get();
        $credits = ProductsHelper::addCreditorToProduct($credits);
        $credits_links = SitemapsHelper::fillLinksArrayWithCNC('credits', $credits, self::$CHANGE_FREQ_PRODUCTS,  self::$PRIORITY_PRODUCTS);
        $consumers = Consumer::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate()
          ->get();

        $consumers_links = SitemapsHelper::fillLinksArrayWithCNC('consumers', $consumers, self::$CHANGE_FREQ_PRODUCTS,  self::$PRIORITY_PRODUCTS);

        $microloans = Microloan::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate()
          ->get();

        $microloans = ProductsHelper::addCreditorToProduct($microloans);
        $microloans_links = SitemapsHelper::fillLinksArrayWithCNC('microloans', $microloans, self::$CHANGE_FREQ_PRODUCTS,  self::$PRIORITY_PRODUCTS);

        $deposits = Deposit::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate()
          ->get();

        $deposits = ProductsHelper::addCreditorToProduct($deposits);
        $deposits_links = SitemapsHelper::fillLinksArrayWithCNC('deposits', $deposits, self::$CHANGE_FREQ_PRODUCTS,  self::$PRIORITY_PRODUCTS);

        $cards = Card::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate()
          ->get();

        $cards = ProductsHelper::addCreditorToProduct($cards);
        $cards_links = SitemapsHelper::fillLinksArrayWithCNC('cards', $cards, self::$CHANGE_FREQ_PRODUCTS,  self::$PRIORITY_PRODUCTS);
        $elements = array_merge($credits_links, $consumers_links, $microloans_links, $deposits_links, $cards_links);
        break;
      case 'statics':
        $elements = SitemapsHelper::getStaticRoutes();
        break;
      case 'creditors':
        $creditors = Creditor::active()
          ->matchSubdomain($subdomain_id)
          ->orderByDate(null)
          ->get();
        $elements = SitemapsHelper::fillLinksArrayWithCNC('creditors', $creditors, self::$CHANGE_FREQ_CREDITORS,  self::$PRIORITY_CREDITORS);
        break;
      case 'blog':
        $news = News::publishedAndActive()
          ->matchSubdomainRuleSitemaps($subdomain_id)
          ->get();
        $elements = SitemapsHelper::fillLinksArrayWithCNC('news', $news, self::$CHANGE_FREQ_BLOG,  self::$PRIORITY_BLOG);
        break;
      case 'reviews':
        $reviews = Review::active()->get();
        foreach ($reviews as $key => $review) {
          $review->user;
          $review->creditor;
          $review->credit_types;
          $review->card_type;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);
        $elements = SitemapsHelper::fillLinksArrayWithCNC('specific_review', $reviews_with_timestamps, self::$CHANGE_FREQ_BLOG,  self::$PRIORITY_BLOG);
        break;
      case 'currencies':
        $elements = SitemapsHelper::getCurrenciesRoutes();
        break;
      default:
        return abort(404, 'Sitemap type requested is not defined.');
    }

    $links = (Helper::customPaginate($elements, self::$MAX_ITEMS_PER_SITEMAP, $page))->all();
    return $links;
  }

  public static function getListLinksByType($type, $subdomain_id, $page=1) {
    $links = [];
    switch ($type) {
      case 'statics':
        $elements = SitemapsHelper::getStaticRoutes();
        break;
      case 'products':
        $consumers_ids = Consumer::active()->groupBy('creditor_id')->pluck('creditor_id');
        $creditors_consumers = Creditor::active()
            // ->matchSubdomain($subdomain_id)
            ->orderByDate(null)
            ->whereIn('id', $consumers_ids)
            ->get();
        $elements = SitemapsHelper::fillLinksArrayWithCNC('creditors', $creditors_consumers, self::$CHANGE_FREQ_CREDITORS,  self::$PRIORITY_CREDITORS);
        $links_consumers = (Helper::customPaginate($elements, self::$MAX_ITEMS_PER_SITEMAP, $page))->all();
        foreach ($links_consumers as $key => $value) {
          $links_consumers[$key]['url'] = $links_consumers[$key]['url'].'/consumers';
        }


        $deposits_ids = Deposit::active()->groupBy('creditor_id')->pluck('creditor_id');
        $creditors_deposits = Creditor::active()
            // ->matchSubdomain($subdomain_id)
            ->orderByDate(null)
            ->whereIn('id', $deposits_ids)
            ->get();
        $elements = SitemapsHelper::fillLinksArrayWithCNC('creditors', $creditors_deposits, self::$CHANGE_FREQ_CREDITORS,  self::$PRIORITY_CREDITORS);
        $links_deposits = (Helper::customPaginate($elements, self::$MAX_ITEMS_PER_SITEMAP, $page))->all();
        foreach ($links_deposits as $key => $value) {
          $links_deposits[$key]['url'] = $links_deposits[$key]['url'].'/deposits';
        }


        $links = array_merge($links_consumers, $links_deposits);
        break;
      case 'reviews':
      $ids = Review::active()->groupBy('creditor_id')->pluck('creditor_id');
      $creditors = Creditor::active()
          // ->matchSubdomain($subdomain_id)
          ->orderByDate(null)
          ->whereIn('id', $ids)
          ->get();
        $elements = SitemapsHelper::fillLinksArrayWithCNC('creditors', $creditors, self::$CHANGE_FREQ_CREDITORS,  self::$PRIORITY_CREDITORS);
        $links = (Helper::customPaginate($elements, self::$MAX_ITEMS_PER_SITEMAP, $page))->all();
        foreach ($links as $key => $value) {
          $links[$key]['url'] = $links[$key]['url'].'/reviews';
        }
        break;
    }
    return $links;
  }



  public static function getLastestUpdatedDateByType($type, $subdomain_id)
  {
    switch ($type) {
      case 'products':
        $latest_updated_credit = Credit::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();
        $lastmod = $latest_updated_credit->updated_at;


        $latest_updated_consumer = Consumer::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();
        $lastmod = $latest_updated_credit->updated_at;

        $lastmod = SitemapsHelper::getLastestDateFromComparison($latest_updated_consumer->updated_at, $lastmod);

        $latest_updated_microloan = Microloan::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();

        $lastmod = SitemapsHelper::getLastestDateFromComparison($latest_updated_microloan->updated_at, $lastmod);

        $latest_updated_deposit = Deposit::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();

        $lastmod = SitemapsHelper::getLastestDateFromComparison($latest_updated_deposit->updated_at, $lastmod);

        $latest_updated_card = Card::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();

        $lastmod = SitemapsHelper::getLastestDateFromComparison($latest_updated_card->updated_at, $lastmod);
        break;
      case 'statics':
        $lastmod = SitemapsHelper::getLastDateSitemapsUpdated();
        break;
      case 'creditors':
        $latest_updated_creditor = Creditor::active()
          ->matchSubdomain($subdomain_id)
          ->latest('updated_at')
          ->first();
        $lastmod = $latest_updated_creditor->updated_at;
        break;
      case 'blog':
        $latest_updated_new = News::publishedAndActive()
          ->matchSubdomainRuleSitemaps($subdomain_id)
          ->latest('updated_at')
          ->first();
        $lastmod = $latest_updated_new->updated_at;
        break;
      case 'reviews':
        $latest_updated_review = Review::active()
          ->latest('updated_at')
          ->first();
        $lastmod = $latest_updated_review->updated_at;
        break;
      case 'currencies':
        $lastmod = SitemapsHelper::getLastDateSitemapsUpdated();
        break;
      default:
        return abort(404, 'Sitemap type requested is not defined.');
    }

    return Carbon::parse($lastmod)->toIso8601String();
  }

  public static function getLastestDateFromComparison($a, $b)
  {
    return strtotime($a) - strtotime($b) > 0 ? $a : $b;
  }

  public static function getLastDateSitemapsUpdated()
  {
    $today = Carbon::now();
    $todays_hour = $today->format('H');
    $todays_days = $today->format('d');
    $todays_months = $today->format('m');
    $todays_year = $today->format('Y');

    $date_updated = Carbon::createFromFormat('d/m/Y H:i:s',  "$todays_days/$todays_months/$todays_year 08:00:00");
    if ($todays_hour >= self::$HOUR_STATIC_UPDATE) {
      $date_updated = $date_updated->toIso8601String();
    } else {
      $date_updated = $date_updated->subDays(1)->toIso8601String();
    }
    return $date_updated;
  }

  public static function fillLinksArrayWithCNC($slug, $items, $changefreq = null, $priority = null)
  {

    if (!$items || count(array($items)) == 0) return [];
    $links_arr = [];
    foreach ($items as $key => $item) {
      $link = SeoHelper::getCustomUriWithCNC($slug, $item);
      if ($link) {
        $sitemap_element = [

          "url" => $link,
          "changefreq" => $changefreq,
          "priority" => $priority,
          "lastmod" => Carbon::parse($item->updated_at)->toIso8601String(),
        ];
        array_push($links_arr, $sitemap_element);
      }
    }
    return $links_arr;
  }

  public static function calculateNumberOfSitemaps($items)
  {
    $max_items_per_sitemap = self::$MAX_ITEMS_PER_SITEMAP;
    return (int)ceil($items / $max_items_per_sitemap);
  }


  public static function getCreditorsIds() {
    $ids = Review::active()->groupBy('creditor_id')->pluck('creditor_id');
    return $ids;
  }


  public static function getStaticRoutes()
  {

    $change_freq = 'daily';
    $priority = 0.9;
    $lastmod = SitemapsHelper::getLastDateSitemapsUpdated();
    $static_routes = [
      [
        "url" => '',
        "changefreq" => 'always',
        "priority" => 1,
        "lastmod" => $lastmod,
      ],
      /* PRODUCTS */
      [
        "url" => '/products',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/deposits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CARDS */
      [
        "url" => '/products/cards',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/cards/debit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/cards/credit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CREDITS */
      [
        "url" => '/products/credits/flats',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/consumers',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/rooms',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/shared',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/commercial',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/house',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/townhouse',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/parcels',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/apartments',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/realstate',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/pledge',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/auto',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/refinancing',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/mortgage',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/microloans',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],

      /* PRODUCTS SPECIAL */
      [
        "url" => '/products/special',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/special/credits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/special/deposits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/special/microloans',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/special/credit_cards',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/special/debit_cards',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CREDIT CALCULATORS */
      [
        "url" => '/products/credits/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/mortgage/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/refinancing/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/mortgage/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/flats/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/rooms/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/shared/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/house/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/townhouse/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/parcels/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/apartments/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/commercial/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/products/credits/parcels/calculator',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CREDITORS */
      [
        "url" => '/creditors',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/creditors/banks',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/creditors/pawnshops',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/creditors/mfo',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* NEWS */
      [
        "url" => '/news',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/news/day',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/news/week',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/news/all',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* ARTICLES */
      [
        "url" => '/articles/analytics',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/comparisons',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/advices/deposits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/advices/credits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/advices/mortgage',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/advices/services',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/articles/advices/investments',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* RATINGS */
      [
        "url" => '/ratings',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/unofficial',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/official',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/official/10',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/official/20',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/official/30',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/official/100',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/unofficial/10',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/unofficial/20',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/unofficial/30',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/ratings/unofficial/100',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* SERVICES */
      [
        "url" => '/services',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/selection',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/history',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/average',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/history',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CREDITS COMPARISONS */
      [
        "url" => '/services/credits/compare',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/compare/credits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/compare/microloans',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/compare/credit_cards',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/compare/debit_cards',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/compare/deposits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CREDITS AVERAGE */
      [
        "url" => '/services/credits/average/banks',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/average/mfo',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/services/credits/average/pawnshops',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* CURRENCIES */
      [
        "url" => '/currencies',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* BLACKLISTS */
      [
        "url" => '/blacklists',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/blacklists',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* REVIEWS */
      [
        "url" => '/reviews',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/creditors/banks',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/creditors/pawnshops',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/creditors/mfo',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/consumers',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/microloans',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/flats',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/rooms',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/commercial',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/house',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/townhouse',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/parcels',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/apartments',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/shared',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/mortgage',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/credits/refinancing',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],

      [
        "url" => '/reviews/products/deposits',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/cards/credit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/cards/debit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/cards/credit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/reviews/products/cards/credit',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* SITEMAP */
      [
        "url" => '/sitemap',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* ABOUT PAGES */
      [
        "url" => '/about',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/advertising',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/us',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/contacts',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/terms',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/contacts',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/about/privacy',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      /* OTHERS */
      [
        "url" => '/support',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/search',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/support',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
    ];


    return $static_routes;
  }
  public static function getCurrenciesRoutes()
  {

    $change_freq = 'daily';
    $priority = 0.5;
    $lastmod = SitemapsHelper::getLastDateSitemapsUpdated();
    $currencies_routes = [
      [
        "url" => '/currencies/aud',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/azn',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/gbp',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/amd',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/byn',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/bgn',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/brl',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/huf',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/hkd',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/dkk',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/usd',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/eur',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/inr',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/kzt',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/cad',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/kgs',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/cny',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/mdl',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/nok',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/pln',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/ron',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/xdr',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/sgd',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/tjs',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/try',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/tmt',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/uzs',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/uah',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/czk',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/sek',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/chf',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/zar',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/krw',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],
      [
        "url" => '/currencies/jpy',
        "changefreq" => $change_freq,
        "priority" => $priority,
        "lastmod" => $lastmod,
      ],

    ];


    return $currencies_routes;
  }
}
