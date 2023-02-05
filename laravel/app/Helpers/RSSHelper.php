<?php

namespace App\Helpers;

use Carbon\Carbon;

use App\Models\News\News;

use App\User;

use App\Helpers\Helper;
use App\Helpers\SeoHelper;

class RSSHelper
{
    static $SLUG_FOR_CNC = 'news';
    static $YANDEX_USUAL_MATERIAL_TYPE = 'message';
    static $YANDEX_MATERIAL_TYPE_ARTICLES = 'article';


    public static function getItemsAmountBySubdomain($subdomain_id, $type, $xDays = null)
    {
        return News::publishedAndActive()
            ->matchSubdomainRuleSitemaps($subdomain_id)
            ->matchPublishedAtFromXDays($xDays)
            ->matchArticlesOrNews($type)
            ->MatchNoSpecialCharacterForCNC()
            ->count();
    }

    public static function getItemsBySubdomain($subdomain_id, $type, $xDays = null)
    {
        $news = News::publishedAndActive()
            ->matchSubdomainRuleSitemaps($subdomain_id)
            ->matchPublishedAtFromXDays($xDays)
            ->matchArticlesOrNews($type)
            ->MatchNoSpecialCharacterForCNC()
            ->get();
        $elements = RSSHelper::fillItemsArrayWithCNC($news);

        return $elements;
    }

    public static function getLastModBySubdomain($subdomain_id, $type, $xDays = null)
    {
        $news = News::publishedAndActive()
            ->matchSubdomainRuleSitemaps($subdomain_id)
            ->matchPublishedAtFromXDays($xDays)
            ->matchArticlesOrNews($type)
            ->MatchNoSpecialCharacterForCNC()
            ->latest('updated_at')
            ->first();

        return $news ? date(DATE_RFC822, strtotime($news->updated_at)) : '';
    }

    public static function fillItemsArrayWithCNC($items)
    {

        if (!$items || count(array($items)) == 0) return [];
        $items_arr = [];
        $articles_theme_slugs = ['comparisons', 'analytics', 'advices'];
        foreach ($items as $key => $item) {
            $link = SeoHelper::getCustomUriWithCNC(self::$SLUG_FOR_CNC, $item);
            if ($link) {
                /*Commented for now */
                // $author_obj = User::find($item->user_id);
                $author_obj = User::find(86);
                $author_name = $author_obj ? $author_obj->full_name : 'Кухарёнок Марина Васильевна';
                $image_ext = '';
                if ($item->image) {
                    $file_parts = pathinfo("/images/news/" . $item->image);
                    $image_ext = $file_parts['extension'];
                    if ($image_ext == 'jpg') $image_ext = 'jpeg';
                }
                $rss_element = [
                    "title" => $item->title,
                    "author" => 'Кухарёнок Марина Васильевна',
                    "url" => $link,
                    "image" => $item->image,
                    "image_extension" => $image_ext,
                    "type" => in_array($item->theme_slug, $articles_theme_slugs) ? self::$YANDEX_MATERIAL_TYPE_ARTICLES : self::$YANDEX_USUAL_MATERIAL_TYPE,
                    "text_no_html" => Helper::deleteHTMLFromStr($item->text),
                    // "text_html" => preg_replace('/(<[^>]+) style=".*?"/i', '$1', $item->text), 
                    // <rambler:full-text>{{"<![CDATA[$item[text_html]]]>"}}</rambler:full-text>
                    "published_at_RFC822" => date(DATE_RFC822, strtotime($item->published_at)),
                ];
                array_push($items_arr, $rss_element);
            }
        }
        return $items_arr;
    }
}
