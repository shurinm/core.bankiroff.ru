<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\News\News;
use App\Models\News\NewsTheme;
use App\Models\News\NewsAdvicesSlug;
use App\Models\News\NewsTag;

use App\Helpers\LogsHelper;
use App\Helpers\ProductsHelper;
use App\Helpers\SubDomainHelper;
use App\Helpers\JwtHelper;
use App\Helpers\NewsHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\YandexHelper;

use Carbon\Carbon;


use Str;
use Storage;
use File;

class NewsController extends Controller
{
    /*                                          ACLARATIONS:
        For the methods getById, getByIdFull and others, the meaning of the variable (new) was choosen in order to get not confused,
        as far as in the english language the term 'News' is always plural.
    */
    /*---------------------Methods for slugs besides advices -----------------------------*/
    public function getXnumberBySlug(Request $request, $slug = 'all', $xnumber = null)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $news = News::publishedAndActive()
            ->whereThemeSlug($slug)
            ->matchSubdomain($subdomain)
            ->selectFields()
            ->paginateOrGet($request->page, $xnumber);
        /* OPTIMIZE HERE, WE NEED JUST THE COUNT OF COMMENTS!! */
        foreach ($news as $key => $new) {
            $new->comments;
            $new->comments_count = count($new->comments);
            $new->regions;
        }
        if ($request->page) {
            $news_data = $news->all();
            $news_with_timestamps = NewsHelper::addTimestampsPublishedAt($news_data);
            $news_with_subdomain = SubDomainHelper::addSubdomainToMany($news_with_timestamps);
            $news->data = $news_with_subdomain;
            return $news;
        }
        $news_with_timestamps = NewsHelper::addTimestampsPublishedAt($news);
        $news_with_subdomain = SubDomainHelper::addSubdomainToMany($news_with_timestamps);

        return  $news_with_subdomain;
    }


    /*---------------------Methods for advices -----------------------------*/
    public function getXnumberByAdviceSlug(Request $request, $slug, $xnumber = null)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $news = News::publishedAndActive()
            ->whereAdviceSlug($slug)
            ->matchSubdomain($subdomain)
            ->paginateOrGet($request->page, $xnumber);
        /* OPTIMIZE HERE, WE NEED JUST THE COUNT OF COMMENTS!! getCommentsCount */
        foreach ($news as $key => $new) {
            $new->comments;
            $new->comments_count =  count($new->comments);
        }
        if ($request->page) {
            $news_data = $news->all();
            $news_with_timestamps = NewsHelper::addTimestampsPublishedAt($news_data);
            $news->data = $news_with_timestamps;
            return $news;
        }
        return NewsHelper::addTimestampsPublishedAt($news);
    }

    public function getById(Request $request, $id)
    {
        $isAdmin = JwtHelper::isAdmin();
        $incoming_subdomain = $request->header('Subdomain') ?? $request->subdomain;
        $subdomain_id = SubDomainHelper::getSubdomainId($incoming_subdomain);
        $new = News::where('id', $id)->publishedAndActive($isAdmin)->matchSubdomain($subdomain_id)->first();
        if (!$new) abort(404, 'SUBDOMAIN_NOT_MATCHING');
        $new->views += 1;
        $new->save();
        $new = SubDomainHelper::addSubdomainToOne($new);
        if (($new->subdomain != $incoming_subdomain && $incoming_subdomain != 'new')) {
            abort(404, 'SUBDOMAIN_NOT_MATCHING_2');
        }
        $comments = $new->comments;
        $tags = $new->tags;
        foreach ($comments as $key => $comment) {
            $comment->user;
        }
        foreach ($tags as $key => $tag) {
            $tag->tag;
        }
        $product_creditors =  $new->productsCreditorsAsKeyValue;
        $product_creditors =  ProductsHelper::addProductByProductType($product_creditors);
        $new = NewsHelper::addTimestampsPublishedAtObj($new);
        $read_more = $this->getXnumberReadMore($request, 5, $new->id);
        $read_more_with_timestamps = NewsHelper::addTimestampsPublishedAt($read_more);
        $new->read_more = $read_more_with_timestamps;
        $new = NewsHelper::addDateAndTimePublished($new);
        return $new;
    }

    public function getForIndexPage(Request $request)
    {

        $news_day =  collect($this->getXnumberBySlug($request, 'day', 10))->sortBy('publication_date_full')->reverse()->toArray();
        $news_day =  array_slice($news_day, 0, 3);

        $news_week =  collect($this->getXnumberBySlug($request, 'week', 10))->sortBy('publication_date_full')->reverse()->toArray();
        $news_week = array_slice($news_week, 0, 3);

        $news_advices =  collect($this->getXnumberBySlug($request, 'advices', 10))->sortBy('publication_date_full')->reverse()->toArray();
        $news_advices = array_slice($news_advices, 0, 3);

        $news_comparisons =  collect($this->getXnumberBySlug($request, 'comparisons', 10))->sortBy('publication_date_full')->reverse()->toArray();
        $news_comparisons = array_slice($news_comparisons, 0, 3);

        $news_analytics =  collect($this->getXnumberBySlug($request, 'analytics', 10))->sortBy('publication_date_full')->reverse()->toArray();
        $news_analytics = array_slice($news_analytics, 0, 3);

        $data = [
            "day" =>  $news_day,
            "week" =>  $news_week,
            "news_feed" => $this->getXnumberBySlug($request, 'newsfeed', 20),
            "advices" =>  $news_advices,
            "comparisons" =>  $news_comparisons,
            "analytics" =>  $news_analytics,
        ];
        return $data;
    }

    /* Utilities */
    private function getXnumberReadMore(Request $request, $xnumber = null, $exclude_id)
    {
        $subdomain = SubDomainHelper::getSubdomainId($request->header('Subdomain'));
        $news = News::publishedAndActive()
            ->where('id', '!=', $exclude_id)
            ->matchSubdomain($subdomain)
            ->selectFields()
            ->take($xnumber)->get();
        /* OPTIMIZE HERE, WE NEED JUST THE COUNT OF COMMENTS!! getCommentsCount */
        foreach ($news as $key => $new) {
            $new->comments;
            $new->comments_count = count($new->comments);
        }

        return $news;
    }

    public function getByIdFull(Request $request, $id)
    {
        $new = News::findOrFail($id);
        $new->regions;
        $new->tags;
        return $new;
    }

    public function getThemes(Request $request)
    {
        return NewsTheme::selectFields($request->isKeyValue)->get();
    }

    public function getAdvicesSlugs(Request $request)
    {
        return NewsAdvicesSlug::selectFields($request->isKeyValue)->get();
    }
    public function getTags(Request $request)
    {
        return NewsTag::selectFields($request->isKeyValue)->get();
    }
    public function getAllAsKeyValue(Request $request)
    {
        return News::selectFieldsKV($request->isKeyValue)->get();
    }
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    
    public function addAllRelationships($new)
    {
        AddressesHelper::addRegionMeaningAsKeyValue($new->regionsAsKeyValue);
        NewsHelper::addTagsMeaningAsKeyValue($new->tagsAsKeyValue);
        ProductsHelper::addTitleByItemIdAndType($new->productsCreditorsAsKeyValue);
        $new->regions;
    }
    
    public function getAll(Request $request, $xnumber = 10)
    {
        $news = News::matchTitleLike($request->title ?? null)
            ->matchThemes($request->themes ?? null)
            ->matchAdvicesSlugs($request->advices_slugs ?? null)
            ->orderByDate(null)
            ->paginateOrGet($request->page, $xnumber);
        $news =  NewsHelper::addThemeSlugMeaning($news);
        $news =  NewsHelper::addAdviceSlugMeaning($news);
        if ($request->page) {
            $news_data = $news->all();
            $news_with_subdomain = SubDomainHelper::addSubdomainToMany($news_data);
            $news->data = $news_with_subdomain;
            return $news;
        }

        $news_with_subdomain = SubDomainHelper::addSubdomainToMany($news);

        return $news_with_subdomain;
    }

    public function getByIdFullAdmin(Request $request, $id)
    {
        $new = News::findOrFail($id);
        $this->addAllRelationships($new);
        return $new;
    }

    public function add(Request $request)
    {
        $new = new News();
        $new->title = $request->title;
        $new->text = $request->text;
        $new->image = $request->image;
        $new->published_at = $request->published_at ?? Carbon::now();
        $new->theme_slug = $request->theme_slug;
        $new->advice_slug = $request->advice_slug;
        $new->active = $request->active ? 1 : 0;
        $new->save();
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $new->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/news/' . $name, File::get($image));
            $new->image =  $name;
            $new->save();
        }

        NewsHelper::fillInterceptions($request->newsRegions, 'news_regions',  $new->id);
        NewsHelper::fillInterceptions($request->newsTags, 'news_tags',  $new->id);
        NewsHelper::fillInterceptions($request->newsProductsCreditors, 'news_products_creditors',  $new->id);
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($new);
        LogsHelper::addLogEntry($request, "news", "create", $new);
        YandexHelper::reportChanges("news", $new );
        NewsHelper::sendEmailNotificationNewMaterial($new);
    }

    public function updateById(Request $request, $id)
    {
        $old_new = News::findOrFail($id);
        $this->addAllRelationships($old_new);

        $new = News::findOrFail($id);
        $new->title = $request->title;
        $new->text = $request->text;
        $new->published_at = $request->published_at;
        $new->theme_slug = $request->theme_slug;
        $new->advice_slug = $request->advice_slug;
        $new->active = $request->active ? 1 : 0;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            if ($new->image) {
                File::delete('images/news/' . $new->image);
            }
            $name = time() . Str::random(5) . '_' . date("Y-m-d") . '_' . $new->id . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/news/' . $name, File::get($image));
            $new->image =  $name;
        }

        $new->save();

        NewsHelper::fillInterceptions($request->newsRegions, 'news_regions', null, $new->id);
        NewsHelper::fillInterceptions($request->newsTags, 'news_tags', null, $new->id);
        NewsHelper::fillInterceptions($request->newsProductsCreditors, 'news_products_creditors', null,  $new->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($new);
        LogsHelper::addLogEntry($request, "news", "update", $new, $old_new);
        YandexHelper::reportChanges("news", $new , $old_new);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("news", $new, $old_new);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_new = News::findOrFail($id);
        $new = News::findOrFail($id);
        $new->active =  $new->active ? 0 : 1;
        $new->save();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($new);
        $this->addAllRelationships($old_new);
        LogsHelper::addLogEntry($request, "news", "update", $new, $old_new);
        YandexHelper::reportChanges("news", $new , $old_new);
        NewsHelper::sendEmailNotificationNewMaterial($new);

    }

    public function deleteById(Request $request, $id)
    {
        $old_new= News::findOrFail($id);
        $this->addAllRelationships($old_new);

        $new = News::findOrFail($id);
        if ($new->image) {
            File::delete('images/news/' . $new->image);
        }
        $new->delete();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "news", "delete", null, $old_new);
        YandexHelper::reportChanges("news", null , $old_new);
    }
}
