<?php

namespace App\Helpers;

use App\Helpers\SeoHelper;
use App\Models\Redirects\Redirect;


class RedirectsHelper
{

    public static function validateUrlAndAddRedirect($type, $product, $old_product)
    {
        if(!$product || !$old_product) return;
        $new_url = SeoHelper::getFullURL($type, $product);
        $old_url = SeoHelper::getFullURL($type, $old_product);        
        RedirectsHelper::addRedirect($new_url, $old_url);
    }

    public static function addRedirect($new_url, $old_url)
    {
        if($new_url == $old_url) return;
        /* The url changed. That is why we create a redirect entry. */
        error_log("ADDING REDIRECT, NEW_URL: $new_url | OLD_URL: $old_url");
        $controversial_redirect = Redirect::where('old_url', $new_url)->where('new_url', $old_url)->first();
        if($controversial_redirect) {
            // error_log("DELETING CONTROVERSIAL REDIRECT");
            $controversial_redirect->delete();
        }
        RedirectsHelper::updateOldRedirectsWithLatestUrl($new_url, $old_url);
        $redirect = new Redirect();
        $redirect->active = 1;
        $redirect->new_url = $new_url;
        $redirect->old_url = $old_url;
        $redirect->save();
    }

    public static function updateOldRedirectsWithLatestUrl($new_url, $old_url)
    {
        if($new_url == $old_url) return;
        $old_redirects = Redirect::where('new_url', $old_url)->get();
        if(!$old_redirects || !count($old_redirects)) return;
        // error_log("updateOldRedirectsWithLatestUrl");
        foreach ($old_redirects as $key => $redirect) {
            $redirect->new_url = $new_url;
            $redirect->save();
        }
    }

}