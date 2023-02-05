<?php

namespace App\Helpers;

use App\Helpers\SeoHelper;
use App\Helpers\Helper;
use App\Models\Seo\SubdomainsKey;
use Illuminate\Support\Facades\Http;


class YandexHelper
{
    static $YANDEX_INDEX_ENDPOINT = "https://yandex.com/indexnow";
    static $DATA_TYPE = "index_yandex";

    public static function getUrlInfoForSeo($type, $info){
        $subdomain = $info->subdomain?$info->subdomain.".":"";
        if($info)
            return SeoHelper::$PROTOCOL.$subdomain.SeoHelper::$MAIN_DOMAIN.$info->url;
        return;
    }

    public static function reportChanges($type, $newInfo = null, $oldInfo = null, $isSingular = false)
    {
        $url_key_pairs = array();
        if($isSingular){
            $type = Helper::getWordSingular($type);
        }
        if($oldInfo){
            $url = YandexHelper::getUrlInfoByType($type, $oldInfo);
            $subdomain = Helper::getSubdomainFromURL($url);
            $key = YandexHelper::getKeyBySubdomain($subdomain);
            $url_key_pairs[] = (object) [
                'url' => $url, 
                'key' => $key,
            ];
        }
        if($newInfo){
            $url = YandexHelper::getUrlInfoByType($type, $newInfo);
            $subdomain = Helper::getSubdomainFromURL($url);
            $key = YandexHelper::getKeyBySubdomain($subdomain);
            $url_key_pairs[] = (object) [
                'url' => $url, 
                'key' => $key,
            ];
        }
        if(count($url_key_pairs) == 0)
            return;
        $host = SeoHelper::$MAIN_DOMAIN;
        YandexHelper::sendRequest($host, $url_key_pairs);

    }

    public static function getUrlInfoByType($type, $info = null){
        try{
            if($type === "seo.seoTexts"){
                $url = YandexHelper::getUrlInfoForSeo($type, $info);
            }else{
                $url = SeoHelper::getFullURL($type, $info);
            }
            return $url;
        } catch (Exception $e) {
            return null; //if there is no link for the page
        }
    }

    public static function getKeyBySubdomain($subdomain){
        $subdomainKeys = SubdomainsKey::matchSubdomain($subdomain)
            ->matchType(self::$DATA_TYPE)
            ->firstOrFail();
        return $subdomainKeys->data;
    }

    public static function sendRequest($host, $url_key_pairs){
        if(count($url_key_pairs) == 0 || !$host)
            return;
        foreach ($url_key_pairs as $url_key_pair) {
            $response = Http::post(YandexHelper::$YANDEX_INDEX_ENDPOINT, [
                'host' => $host,
                'key' => $url_key_pair->key,
                'urlList' => $url_key_pair->url,
            ]);
        }
        return $response;
    }
}