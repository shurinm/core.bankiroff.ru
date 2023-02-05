<?php

namespace App\Helpers;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use App\Models\Region;

class CommentsHelper
{

    public static function addUserToComment($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->user;
            }
        }
        return $items;
    }
    public static function addReviewToComment($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $itemlocal = $item->review;
                if ($itemlocal) {
                    $itemlocal->creditor;
                    $itemlocal->user;
                    $itemlocal->credit_types;
                    $itemlocal->card_type;
                }
            }
        }
        return $items;
    }

    public static function addNewsToComment($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->news;
            }
        }
        return $items;
    }

    public static function addTimestampsPublishedAtReview($items, $is_sorting = false)
    {
        foreach ($items as $key => &$item) {
            if ($item->review && $item->review->published_at) {
                $item->review->time =  $item->review->published_at->format('H:i');
                $item->review->publication_date = $item->review->published_at->format('d.m.Y');
                $item->review->publication_date_full = $item->review->published_at;
            } else if ($item->review && $item->review->created_at) {
                $item->review->time = $item->review->created_at->format('H:i');
                $item->review->publication_date = $item->review->created_at->format('d.m.Y');
                $item->review->publication_date_full = $item->review->created_at;
            }
        }
        return $items;

        // return collect($items)->sortByDesc('publication_date_full')->values();
    }

    public static function addSubdomain($items)
    {
        /*
        If the material (news or article) has 1 region, we check if there is a subdomain for that region, if it has we send it to the frontend. 
        If the material does not have regions or if it has more than 1, in this case the subdomain will be null (Global material, not connected to any subdomain) 
        */
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                if (count($item->news->regions) == 1 && $item->news->regions[0]) {
                    $item->news->subdomain = (Region::where('id', $item->news->regions[0]->region_id)->first()) ? (Region::where('id', $item->news->regions[0]->region_id)->first())->subdomain : null;
                } else {
                    $item->news->subdomain = null;
                }
            }
        }

        return $items;
    }
}
