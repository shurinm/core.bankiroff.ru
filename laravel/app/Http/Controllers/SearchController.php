<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Models\UsersSearch;

use App\Models\Creditors\Creditor;

use App\Models\Products\Credits\Credit;
use App\Models\Products\Credits\Consumer;
use App\Models\Products\Credits\Microloan;
use App\Models\Products\Deposits\Deposit;
use App\Models\Products\Cards\Card;

use App\Models\News\News;
use DB;

use App\Helpers\Helper;
use App\Helpers\ProductsHelper;

use Carbon\Carbon;

class SearchController extends Controller
{

  public function search(Request $request, $xnumber = 10)
  {
    $text_searched = $request->search;
    $user = User::where('id', $request->user_id)->first();
    $results = [];
    if ($text_searched) {
      if ($user) {
        $user_search = new UsersSearch();
        $user_search->text = $text_searched;
        $user_search->user_id = $user->id;
        $user_search->save();
      }

      $creditors = Creditor::where('name', 'like', "%{$text_searched}%")
        ->orWhere('alternative', 'like', "%{$text_searched}%")
        ->where(function ($query) {
          $query->where('active', 1)->orWhere('direct_access', 1);
        })
        ->withCount(['reviews AS avg_rating' => function ($query) {
          $query->select(DB::raw("AVG(stars) as paidsum"))->where('active', 1);
        }])->select("id", "name", "type_slug", "image", "created_at")->get()->toArray();

      $creditors = ProductsHelper::addTypeProduct($creditors, 'creditors');

      $news = News::where('title', 'like', "%{$text_searched}%")
        ->where('published_at', '<=', Carbon::now())
        ->where('active', 1)
        ->select("id", "title", "text", "image", "theme_slug", "advice_slug", "created_at")
        ->get()->toArray();
      $news = ProductsHelper::addTypeProduct($news, 'news');


      $cards = Card::select("cards.id", "cards.title", "cards.creditor_id", "cards.advantages", "cards.image", "cards.type", "cards.created_at")->where('cards.active', 1)->orWhere('cards.direct_access', 1)
        ->where(function ($query) {
          $query->where('cards.active', 1)->orWhere('cards.direct_access', 1);
        })
        ->where(function ($query) use ($text_searched) {
          $query->orWhere('cards.title', 'like', "%{$text_searched}%")
            ->orWhere('creditors.name', 'like', "%{$text_searched}%");
        })->leftJoin('creditors', 'creditors.id', '=', 'cards.creditor_id')->with('creditor')->get()->toArray();


      $cards = ProductsHelper::addTypeProduct($cards, 'cards');

      $credits = Credit::select("credits.id", "credits.creditor_id", "credits.title", "credits.advantages", "credits.type_slug", "credits.pledge_slug", "credits.created_at")
        ->where(function ($query) {
          $query->where('credits.active', 1)->orWhere('credits.direct_access', 1);
        })
        ->where(function ($query) use ($text_searched) {
          $query->orWhere('credits.title', 'like', "%{$text_searched}%")
            ->orWhere('creditors.name', 'like', "%{$text_searched}%")
            ->orWhere('alternative', 'like', "%{$text_searched}%");
        })
        ->leftJoin('creditors', 'creditors.id', '=', 'credits.creditor_id')->with('creditor')->get()->toArray();
      $credits = ProductsHelper::addTypeProduct($credits, 'credits');


      $consumers = Consumer::select("consumers.id", "consumers.creditor_id", "consumers.title", "consumers.advantages", "consumers.created_at")
        ->where(function ($query) {
          $query->where('consumers.active', 1)->orWhere('consumers.direct_access', 1);
        })
        ->where(function ($query) use ($text_searched) {
          $query->orWhere('consumers.title', 'like', "%{$text_searched}%")
            ->orWhere('creditors.name', 'like', "%{$text_searched}%");
        })
        ->leftJoin('creditors', 'creditors.id', '=', 'consumers.creditor_id')->with('creditor')->get()->toArray();
      $consumers = ProductsHelper::addTypeProduct($consumers, 'credits_consumers');


      $deposits = Deposit::select("deposits.id", "deposits.creditor_id", "deposits.title", "deposits.advantages", "deposits.created_at")
        ->where(function ($query) {
          $query->where('deposits.active', 1)->orWhere('deposits.direct_access', 1);
        })
        ->where(function ($query) use ($text_searched) {
          $query->orWhere('deposits.title', 'like', "%{$text_searched}%")
            ->orWhere('creditors.name', 'like', "%{$text_searched}%");
        })
        ->leftJoin('creditors', 'creditors.id', '=', 'deposits.creditor_id')->with('creditor')->get()->toArray();

      $deposits = ProductsHelper::addTypeProduct($deposits, 'deposits');

      $microloans = Microloan::select("microloans.id", "microloans.creditor_id", "microloans.title", "microloans.advantages", "microloans.created_at")
        ->where(function ($query) {
          $query->where('microloans.active', 1)->orWhere('microloans.direct_access', 1);
        })
        ->where(function ($query) use ($text_searched) {
          $query->orWhere('microloans.title', 'like', "%{$text_searched}%")
            ->orWhere('creditors.name', 'like', "%{$text_searched}%");
        })
        ->leftJoin('creditors', 'creditors.id', '=', 'microloans.creditor_id')->with('creditor')->get()->toArray();
      $microloans = ProductsHelper::addTypeProduct($microloans, 'credits_microloans');

      $results = array_merge($creditors, $news, $credits, $cards, $consumers, $deposits, $microloans);
    }

    if (!$request->page) return $results;
    return Helper::customPaginate($results, $xnumber, $request->page);
  }

  public function searchProductsAndCreditorsAsKeyValue(Request $request, $xnumber = 10)
  {
    $text_searched = $request->search;
    $results = [];
    if ($text_searched) {
      $creditors = Creditor::where('name', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "name as title", "type_slug")
        ->get()->toArray();
      $creditors = ProductsHelper::addTypeProduct($creditors, 'creditors');

      $cards = Card::where('title', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "title", "type as type_slug")
        ->get()->toArray();
      $cards = ProductsHelper::addTypeProduct($cards, 'cards');

      $credits = Credit::where('title', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "title", "type_slug", "pledge_slug")
        ->get()->toArray();
      $credits = ProductsHelper::addTypeProduct($credits, 'credits');


      $consumers = Consumer::where('title', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "title")
        ->get()->toArray();
      $consumers = ProductsHelper::addTypeProduct($consumers, 'consumers');


      $deposits = Deposit::where('title', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "title")
        ->get()->toArray();

      $deposits = ProductsHelper::addTypeProduct($deposits, 'deposits');

      $microloans = Microloan::where('title', 'like', "%{$text_searched}%")
        ->active()
        ->select("id as value", "title")
        ->get()->toArray();
      $microloans = ProductsHelper::addTypeProduct($microloans, 'microloans');

      $results = array_merge($creditors, $credits, $cards, $consumers, $deposits, $microloans);
      $results = ProductsHelper::buildHumanReadableTypeProduct($results);
      $results = ProductsHelper::formatProductTitleAndProductType($results);
    }

    if (!$request->page) return $results;
    return Helper::customPaginate($results, $xnumber, $request->page);
  }
}
