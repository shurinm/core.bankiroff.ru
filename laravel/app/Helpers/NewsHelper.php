<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\News\NewsComment;
use App\Models\News\NewsTheme;
use App\Models\News\NewsAdvicesSlug;
use App\Models\News\NewsTag;

use App\Interceptions\NewsRegionsInterception;
use App\Interceptions\NewsTagsInterception;
use App\Interceptions\NewsProductsCreditorsInterception;

use Illuminate\Support\Facades\Log;
use App\Models\Region;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailToUser;

class NewsHelper
{

    public static function addCountComments($items)
    {
        /*NOT USING THIS, USING LOGIC OF CREDITORS */
        foreach ($items as $key => &$item) {
            $item->count_comments = NewsComment::where('news_id', $item->id)->count();
        }

        return $items;
    }

    public static function addDateAndTimePublished($new)
    {

        $new->time = Carbon::parse($new->published_at)->format('H:i');
        $new->published_at =  Carbon::parse($new->published_at)->format('d.m.Y');
        return $new;
    }

    public static function addTimestampsPublishedAt($items)
    {
        foreach ($items as $key => &$item) {
            if ($item->published_at) {
                $item->time =  $item->published_at->format('H:i');
                $item->publication_date = $item->published_at->format('d.m.Y');
                $item->publication_date_full = $item->published_at;
            } else {
                $item->time = $item->created_at->format('H:i');
                $item->publication_date = $item->created_at->format('d.m.Y');
                $item->publication_date_full = $item->created_at;
            }
        }
        return $items;
    }

    public static function addTimestampsPublishedAtObj($item)
    {
        if ($item->published_at) {
            $item->time =  $item->published_at->format('H:i');
            $item->publication_date = $item->published_at->format('d.m.Y');
        } else {
            $item->time = $item->created_at->format('H:i');
            $item->publication_date = $item->created_at->format('d.m.Y');
        }

        return $item;
    }


    public static function fillInterceptions($items, $type = null, $new_id = null, $old_id = null)
    {
        if ($items == "DELETE") NewsHelper::deleteInterceptions($type, $old_id);
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_id) {
                NewsHelper::deleteInterceptions($type, $old_id);
                $new_id = $old_id;
            }
            foreach ($items as $key => $item) {
                switch ($type) {
                    case 'news_regions':
                        $model =  new NewsRegionsInterception();
                        $model->news_id = $new_id;
                        $model->region_id =  $item['value'];
                        $model->save();
                        break;
                    case 'news_tags':
                        $model =  new NewsTagsInterception();
                        $model->news_id = $new_id;
                        $model->news_tag_id =  $item['value'];
                        $model->save();
                        break;
                    case 'news_products_creditors':
                        $model =  new NewsProductsCreditorsInterception();
                        $model->news_id = $new_id;
                        $model->item_id = $item['value'];
                        $model->type = $item['type_product'];
                        $model->save();
                        break;
                }
            }
        }
        return $items;
    }

    public static function deleteInterceptions($type = null, $id = null)
    {
        switch ($type) {
            case 'news_regions':
                NewsRegionsInterception::where('news_id', $id)->delete();
                break;
            case 'news_tags':
                NewsTagsInterception::where('news_id', $id)->delete();
                break;
            case 'news_products_creditors':
                NewsProductsCreditorsInterception::where('news_id', $id)->delete();
                break;
        }
    }

    public static function addTagsMeaningAsKeyValue($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->title = NewsTag::where('id', $item->value)->select('title')->first() ? (NewsTag::where('id', $item->value)->select('title')->first())->title : null;
                $item->value = strval($item->value);
            }
        }

        return $items;
    }

    public static function addThemeSlugMeaning($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->theme_slug_title = NewsTheme::where('slug', $item->theme_slug)->select('title')->first() ? (NewsTheme::where('slug', $item->theme_slug)->select('title')->first())->title : null;
            }
        }

        return $items;
    }
    public static function addAdviceSlugMeaning($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                $item->advice_slug_title = $item->advice_slug ? (NewsAdvicesSlug::where('slug', $item->advice_slug)->select('title')->first())->title :  null;
            }
        }

        return $items;
    }

    public static function sendEmailNotificationNewMaterial($new)
    {
        /* 
        The functions receives a $new collection.
        If the new material is moderated (active = 1), then
        we search all tags added to that material, then we search
        all users following those tags (subscripted users), and then we send them 1 email notification.
         */

        $tags = $new->tags;

        /* 
        $users_notified stores users which are being notified. 
        In order to not send spam (more than 1 email per material per user) 
        */
        $users_notified = [];
        if ($tags && count($tags) > 0 && $new->active) {
            foreach ($tags as &$tag) {
                /* We get the relationship. We get only users connected to the current tag in the foreach */
                $users = $tag->users;
                if ($users && count($users) > 0) {
                    foreach ($users as &$user) {
                        $user_email = $user->email;
                        if (!in_array($user->email, $users_notified)) {
                            /* The current user in the foreach has not been notified that there is a new material. */
                            // error_log("SENDING EMAIL TO USER $user_email FOR TAG $tag->news_tag_id");
                            Mail::to($user_email)->send(new SendMailToUser(null, 'NEW_MATERIAL_WHERE_USER_IS_SUBSCRIBED'));
                            array_push($users_notified, $user_email);

                        } else {
                            /* The current user in the foreach was already notified. No need to send again an email. */
                            // error_log("USER WAS ALREADY NOTIFIED");
                        }
                    }
                }
            }
        }

        return $new;
    }
}
