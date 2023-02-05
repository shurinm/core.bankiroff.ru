<?php

namespace App\Helpers;

use App\Models\Reviews\Review;


use App\Interceptions\ReviewsRegionsInterception;
use App\Models\Region;


class ReviewsHelper
{

    public static function addCountAndMarkReviews($items, $slug)
    {
        /*NOT USING; USE BETTER THE WAY OF CREDITORS */
        foreach ($items as $key => &$item) {
            $item->count_reviews = Review::where('active', 1)->where('type_slug', $slug)->where('item_id', $item->id)->count();
            $item->mark_reviews = round(Review::where('active', 1)->where('type_slug', $slug)->where('item_id', $item->id)->avg('stars'), 2);
        }
        return $items;
    }

    public static function addCreditorToReview($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->creditor;
            }
        }
        return $items;
    }

    public static function addUserToReview($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->user;
            }
        }
        return $items;
    }


    public static function addTimestampsPublishedAt($items, $is_sorting = false)
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
        if ($is_sorting) {
            return $items;
        }
        return collect($items)->sortByDesc('publication_date_full')->values();
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

    public static function reassignProductReviewsToCreditor($product_id, $creditor_id, $type)
    {
        $reviews = Review::where('creditor_id', $creditor_id)->where('type_slug', $type)->where('item_id', $product_id)->get();
        if (count($reviews) > 0) {
            foreach ($reviews as $key => &$review) {
                $review->type_slug = 'creditors';
                $review->item_id = null;
                $review->save();
            }
        }
    }

    public static function addRelations($reviews)
    {
        if (count($reviews) > 0) {
            foreach ($reviews as $key => &$review) {
                $review->user;
                $review->creditor;
                $review->credit_types;
                $review->card_type;
                switch ($review->type_slug) {
                    case 'credits':
                        if ($review->credit) {
                            $review->credit->creditor;
                        }
                        break;
                    case 'cards':
                        if ($review->card) {
                            $review->card->creditor;
                        }
                        break;
                    case 'consumers':
                        if ($review->consumer) {
                            $review->consumer->creditor;
                        }
                        break;
                    case 'deposits':
                        if ($review->deposit) {
                            $review->deposit->creditor;
                        }
                        break;
                    case 'microloans':
                        if ($review->microloan) {
                            $review->microloan->creditor;
                        }
                        break;
                }
            }
        }
    }

    public static function fillInterceptions($items, $type = null, $new_id = null, $old_id = null)
    {
        if ($items == "DELETE") ReviewsHelper::deleteInterceptions($type, $old_id);
        $items = json_decode($items, true);
        if (!$items) return false;
        if ($items && count($items) > 0) {
            if ($old_id) {
                ReviewsHelper::deleteInterceptions($type, $old_id);
                $new_id = $old_id;
            }
            foreach ($items as $key => $item) {
                switch ($type) {
                    case 'review_regions':
                        $model =  new ReviewsRegionsInterception();
                        $model->review_id = $new_id;
                        $model->region_id =  $item['value'];
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
            case 'review_regions':
                ReviewsRegionsInterception::where('review_id', $id)->delete();
                break;
        }
    }

    public static function addSubdomain($items)
    {
        /*
        If the material (review) has 1 region, we check if there is a subdomain for that region, if it has we send it to the frontend. 
        If the material does not have regions or if it has more than 1, in this case the subdomain will be null (Global material, not connected to any subdomain) 
        */
        if (count($items) > 0) {
            foreach ($items as $key => &$item) {
                if (count($item->regions) == 1 && $item->regions[0]) {
                    $item->subdomain = (Region::where('id', $item->regions[0]->region_id)->first()) ? (Region::where('id', $item->regions[0]->region_id)->first())->subdomain : null;
                } else {
                    $item->subdomain = null;
                }
            }
        }

        return $items;
    }
}
