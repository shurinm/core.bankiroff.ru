<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Helpers\LogsHelper;
use App\Helpers\YandexHelper;

use Illuminate\Http\Request;
use App\Models\Seo\SeoText;
use App\Models\Seo\SeoVariables;
use App\Models\Seo\ReadyQuery;
use App\Models\Seo\ReadyQueriesDisplayPage;
use App\Models\Seo\ReadyQueriesDivision;


class SeoController extends Controller
{
    public function getSeoByUrl(Request $request)
    {
        $url = $request->url;
        error_log("URL FOR SEO: $url");
        $subdomain = $request->header('Subdomain');
        $seo_obj = SeoText::active()->matchUrl($url)->matchSubdomainLike($subdomain)->first();
    		if ($seo_obj!=null) $seo_obj = $this->replaceSeoVariables($seo_obj);
    		return $seo_obj;
    }

	public function replaceSeoVariables($seo_obj) {
        $subdomain = isset(getallheaders()['Subdomain'])?getallheaders()['Subdomain']:'msk';

        $seo_obj = json_decode($seo_obj, true);

        $seo_data = SeoHelper::getUniversalVariables();
        foreach ($seo_obj as $key => $val) {
          foreach ($seo_data as $var => $value) {
            $seo_obj[$key] = str_replace('${'.$var.'}', $seo_data[$var], $seo_obj[$key]);
          }
        }

        return $seo_obj;
    }

    public function getAboutPages(Request $request)
    {
        $subdomain = $request->header('Subdomain');
        return SeoText::active()->matchIsAboutPage(true)->matchSubdomain($subdomain)->get();
    }

    public function getReadyQueriesByUrl(Request $request)
    {
        $display_page_id = SeoHelper::getDisplayPageIdByUrl($request->url);
        $subdomain = $request->header('Subdomain');
        $ready_queries = ReadyQuery::active()->matchUrl($display_page_id ?? null)->matchSubdomain($subdomain)->get();
        SeoHelper::addDivision($ready_queries);
        return $ready_queries;
    }

    public function getReadyQueryDisplayPages(Request $request)
    {
        return ReadyQueriesDisplayPage::selectFields($request->isKeyValue)->get();
    }

    public function getReadyQueryDivisions(Request $request)
    {
        return ReadyQueriesDivision::selectFields($request->isKeyValue)->get();
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Functions for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function getAll(Request $request, $xnumber = 10)
    {
        $seo_text = SeoText::matchTitleLike($request->title ?? null)
            ->matchH1Like($request->h1 ?? null)
            ->matchSubdomainLike($request->subdomain ?? null)
            ->matchUrlLike($request->url ?? null)
            ->matchIsAboutPage($request->aboutPages ?? null)
            ->orderByDate()
            ->paginateOrGet($request->page, $xnumber);
        return $seo_text;
    }

    public function getByIdFull(Request $request, $id)
    {
        return SeoText::findOrFail($id);;
    }

    public function getAllVariables(Request $request)
    {
        $seo_variables = SeoVariables::
                        orderByDate()
                        ->paginateOrGet($request->page, null);
        return $seo_variables;
    }

    public function add(Request $request)
    {
        $seo_text = new SeoText();
        $seo_text->h1 = $request->h1 ?? null;
        $seo_text->subdomain = $request->subdomain;
        $seo_text->text = $request->text ?? null;
        $seo_text->title = $request->title;
        $seo_text->url = $request->url;
        $seo_text->page_title = $request->page_title ?? null;
        $seo_text->page_description = $request->page_description ?? null;
        $seo_text->keywords = $request->keywords ?? null;
        $seo_text->active = $request->active ? 1 : 0;
        $seo_text->is_about_page = $request->is_about_page ? 1 : 0;
        $seo_text->show_more = $request->show_more ? 1 : 0;
        $seo_text->title_description = $request->title_description ?? null;
        $seo_text->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "seo.seoTexts", "create", $seo_text);
        YandexHelper::reportChanges("seo.seoTexts", $seo_text );
    }

    public function updateById(Request $request, $id)
    {
        $old_seo_text = SeoText::findOrFail($id);

        $seo_text = SeoText::findOrFail($id);
        $seo_text->h1 = $request->h1;
        $seo_text->subdomain = $request->subdomain;
        $seo_text->text = $request->text;
        $seo_text->title = $request->title ?? '';
        $seo_text->url = $request->url;
        $seo_text->page_title = $request->page_title;
        $seo_text->page_description = $request->page_description;
        $seo_text->keywords = $request->keywords;
        $seo_text->active = $request->active ? 1 : 0;
        $seo_text->is_about_page = $request->is_about_page ? 1 : 0;
        $seo_text->show_more = $request->show_more ? 1 : 0;
        $seo_text->title_description = $request->title_description ?? null;
        $seo_text->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        LogsHelper::addLogEntry($request, "seo.seoTexts", "update", $seo_text, $old_seo_text);
        YandexHelper::reportChanges("seo.seoTexts", $seo_text , $old_seo_text);
    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_seo_text = SeoText::findOrFail($id);

        $seo_text = SeoText::findOrFail($id);
        $seo_text->active = $seo_text->active ? 0 : 1;
        $seo_text->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "seo.seoTexts", "update", $seo_text, $old_seo_text);
        YandexHelper::reportChanges("seo.seoTexts", $seo_text , $old_seo_text);
    }

    public function deleteById(Request $request, $id)
    {
        $old_seo_text = SeoText::findOrFail($id);

        SeoText::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "seo.seoTexts", "delete", null, $old_seo_text);
        YandexHelper::reportChanges("seo.seoTexts", null , $old_seo_text);
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Functions For Ready queries */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */

    public function addAllRelationships($ready_query)
    {
        SeoHelper::addDisplayPageMeaningAsKeyValue($ready_query->displayPagesAsKeyValue);
    }

    public function getAllQueries(Request $request, $xnumber = 10)
    {
        $ready_queries = ReadyQuery::orderByDate()
            ->paginateOrGet($request->page, $xnumber);
        return $ready_queries;
    }

    public function getByIdFullQuery(Request $request, $id)
    {
        $ready_query =  ReadyQuery::findOrFail($id);
        $this->addAllRelationships($ready_query);
        return $ready_query;
    }

    public function addQuery(Request $request)
    {
        $ready_query = new ReadyQuery();
        $ready_query->title = $request->title;
        $ready_query->description = $request->description ?? null;
        $ready_query->division_id = $request->division_id ?? 1;
        $ready_query->url_redirect = $request->url_redirect;
        $ready_query->subdomain = $request->subdomain ?? null;
        $ready_query->active = $request->active ? 1 : 0;
        $ready_query->save();
        SeoHelper::fillProductInterceptions($request->productDisplayPages, 'ready_queries_display_pages',  $ready_query->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($ready_query);
        LogsHelper::addLogEntry($request, "seo.readyQueries", "create", $ready_query);
    }

    public function updateByQueryId(Request $request, $id)
    {
        $old_ready_query = ReadyQuery::findOrFail($id);
        $this->addAllRelationships($old_ready_query);

        $ready_query = ReadyQuery::findOrFail($id);
        $ready_query->title = $request->title;
        $ready_query->description = $request->description ?? null;
        $ready_query->division_id = $request->division_id ?? 1;
        $ready_query->url_redirect = $request->url_redirect;
        $ready_query->subdomain = $request->subdomain ?? null;
        $ready_query->active = $request->active ? 1 : 0;
        $ready_query->save();
        SeoHelper::fillProductInterceptions($request->productDisplayPages, 'ready_queries_display_pages', null, $ready_query->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($ready_query);
        LogsHelper::addLogEntry($request, "seo.readyQueries", "update", $ready_query, $old_ready_query);
    }

    public function toggleActiveByQueryId(Request $request, $id)
    {
        $old_ready_query = ReadyQuery::findOrFail($id);

        $ready_query = ReadyQuery::findOrFail($id);
        $ready_query->active = $ready_query->active ? 0 : 1;
        $ready_query->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($ready_query);
        $this->addAllRelationships($old_ready_query);
        LogsHelper::addLogEntry($request, "seo.readyQueries", "update", $ready_query, $old_ready_query);
        YandexHelper::reportChanges("seo.readyQueries", $ready_query , $old_ready_query);
    }

    public function deleteByQueryId(Request $request, $id)
    {
        $old_ready_query = ReadyQuery::findOrFail($id);
        $this->addAllRelationships($old_ready_query);

        ReadyQuery::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "seo.readyQueries", "delete", null, $old_ready_query);
        YandexHelper::reportChanges("seo.readyQueries", null , $old_ready_query);
    }
}
