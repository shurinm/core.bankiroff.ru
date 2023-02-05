<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\ReviewsHelper;
use App\Helpers\SubDomainHelper;
use App\Helpers\AddressesHelper;
use App\Helpers\LogsHelper;
use App\Helpers\RedirectsHelper;
use App\Helpers\SeoHelper;
use App\Helpers\YandexHelper;

use App\Models\Reviews\Review;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailToStaff;
use App\Mail\SendMailToUser;
use App\User;
use stdClass;

class ReviewsController extends Controller
{
    public function getByCreditorID(Request $request, $id, $xnumber)
    {
        $reviews = Review::active()->matchProductsFilters($request->sort, $id)->selectFields()->orderByDate(null)->getCountComments()->paginate($xnumber);
        ReviewsHelper::addRelations($reviews);
        $reviews_data = $reviews->all();
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data);
        $reviews->data = $reviews_with_timestamps;

        return $reviews;
    }


    public function getXnumberBySlugAndID(Request $request, $slug, $id, $xnumber = null)
    {
        $reviews = Review::publishedAndActive()
            // ->matchProductsFilters($request->sort, $id)
            ->matchSlugAndID($slug, $id)
            ->paginateOrGet($request->page, $xnumber);

        ReviewsHelper::addRelations($reviews);

        if ($request->page) {
            $reviews_data = $reviews->all();
            $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data);
            $reviews->data = $reviews_with_timestamps;
            return $reviews;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);

        return $reviews_with_timestamps;
    }

    public function getXnumberBySlug(Request $request, $slug, $xnumber)
    {
        $reviews = Review::publishedAndActive()
            ->matchType($slug ?? null)
            ->paginateOrGet($request->page, $xnumber);

        ReviewsHelper::addRelations($reviews);

        if ($request->page) {
            $reviews_data = $reviews->all();
            $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data);
            $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);
            $reviews->data = $reviews_with_subdomain;
            return $reviews;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);
        $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);

        return $reviews_with_subdomain;
    }

    public function getXnumberByProductSlug(Request $request, $slug,  $xnumber)
    {
        switch ($slug) {
            case 'cards_credit':
                $reviews_base = Review::publishedAndActive()->matchProductType('cards', 'credit');
                break;
            case 'cards_debit':
                $reviews_base = Review::publishedAndActive()->matchProductType('cards', 'debit');
                break;
            case 'flats':
            case 'rooms':
            case 'shared':
            case 'house':
            case 'parcels':
            case 'apartments':
            case 'townhouse':
            case 'commercial':
            case 'auto':
            case 'mortgage':
            case 'refinancing':
            case 'realstate':
                $reviews_base = Review::publishedAndActive()
                    ->matchProductType('credits', $slug);
                break;
            case 'consumers':
            case 'deposits':
            case 'microloans':
                $reviews_base = Review::publishedAndActive()->matchProductType($slug, null);
                break;
        }
        $reviews_base = $reviews_base
            ->orderByRating($request->raiting ?? null)
            ->orderByPopularity($request->populars ?? null)
            ->orderByDate($request->sort ?? null)
            ->paginateOrGet($request->page, $xnumber);

        ReviewsHelper::addRelations($reviews_base);

        $is_sorting = ($request->raiting && $request->raiting != 'default') || ($request->populars && $request->populars != 'default') || ($request->sort && $request->sort != 'default');
        if ($request->page) {
            $reviews_data = $reviews_base->all();
            $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data, $is_sorting);
            $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);
            $reviews_base->data = $reviews_with_subdomain;
            $reviews_base = SeoHelper::appendSeoDataToCollection("reviews", $reviews_base);
            return $reviews_base;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_base, $is_sorting);
        $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);

        return $reviews_with_subdomain;

        // return $reviews_base;

    }

    public function getXnumberByCreditorSlug(Request $request, $slug = null, $xnumber)
    {
        $reviews = Review::publishedAndActive()
            ->matchCreditorSlug($slug)
            ->paginateOrGet($request->page, $xnumber);

        ReviewsHelper::addRelations($reviews);

        if ($request->page) {
            $reviews_data = $reviews->all();
            $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data);
            $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);
            $reviews->data = $reviews_with_subdomain;
            return $reviews;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);
        $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);

        return $reviews_with_subdomain;
    }


    public function getById(Request $request, $id)
    {
        $incoming_subdomain = $request->header('Subdomain') ?? $request->subdomain;
        $subdomain_id = SubDomainHelper::getSubdomainId($incoming_subdomain);
        $review = Review::where('id', $id)->active()->matchSubdomain($subdomain_id)->selectFields()->getCountComments()->first();
        if (!$review) return abort(404, 'Review not found, is not moderated or subdomain does not match.');
        $review->views += 1;
        $review->save();
        $review = SubDomainHelper::addSubdomainToOne($review);
        if (($review->subdomain != $incoming_subdomain)) {
            abort(404, 'SUBDOMAIN_NOT_MATCHING_2');
        }
        $review->creditor;
        $review->user;
        $review->credit;
        $review->credit_types;
        $review->card_type;
        $review->comments;
        $comments = $review->comments;
        $review = ReviewsHelper::addTimestampsPublishedAtObj($review);
        foreach ($comments as $key => $comment) {
            $comment->user;
        }
        return $review;
    }


    public function getByUserId(Request $request, $id)
    {
        $reviews = Review::where('user_id', $id)->orderByDate(null)->get();

        ReviewsHelper::addRelations($reviews);

        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);

        return $reviews_with_timestamps;
    }


    public function addBySlug(Request $request)
    {
        $review = new Review();
        $review->creditor_id = $request->creditor_id;
        $review->user_id = $request->user_id;
        $review->user_type = $request->user_type;
        $review->text = $request->text;
        $review->stars = $request->stars;
        $review->type_slug = $request->type_slug;
        $review->item_id = $request->item_id;
        $review->save();
        $user = User::where('id', $request->user_id)->first();
        // $data_obj = (object) ["text" => $request->text,"stars" => $request->stars, "email" => $user->email];
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->text = $request->text;
        $data_obj->stars = $request->stars;
        Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_REVIEW'));
        return Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_REVIEW'));
    }

    public function getAllAsKeyValue(Request $request)
    {
        return Review::selectFieldsKV($request->isKeyValue)->get();
    }

    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    
    public function addAllRelationships($review)
    {
        AddressesHelper::addRegionMeaningAsKeyValue($review->regionsAsKeyValue);
        $review->creditor;
        $review->user;
    }
    
    public function getAll(Request $request, $xnumber = 10)
    {
        $reviews = Review::matchReviewTextLike($request->text ?? null)
            ->matchAuthorLike($request->author ?? null)
            ->matchUsersNicknameLike($request->nick ?? null)
            ->matchUsersTypes($request->types ?? null)
            ->matchProductId($request->productId ?? null)
            ->matchCreditorsIds($request->creditors ?? null)
            ->matchStars($request->stars ?? null)
            ->orderByCreationDate(null)
            ->paginateOrGet($request->page, $xnumber);
        ReviewsHelper::addCreditorToReview($reviews);
        ReviewsHelper::addUserToReview($reviews);
        foreach ($reviews as $key => $review) {
            $review->credit_types;
            $review->card_type;
        }

        if ($request->page) {
            $reviews_data = $reviews->all();
            $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews_data);
            $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);
            $reviews->data = $reviews_with_subdomain;
            return $reviews;
        }
        $reviews_with_timestamps = ReviewsHelper::addTimestampsPublishedAt($reviews);
        $reviews_with_subdomain = SubDomainHelper::addSubdomainToMany($reviews_with_timestamps);

        return $reviews_with_subdomain;
    }

    public function getByIdFull(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $this->addAllRelationships($review);
        return $review;
    }

    public function add(Request $request)
    {
        $review = new Review();
        $review->creditor_id = $request->creditor_id;
        $review->user_id = $request->user_id;
        $review->stars = $request->stars ?? 0;
        $review->views = $request->views ?? 0;
        $review->text = $request->text;
        $review->author = $request->author;
        $review->user_type = $request->user_type;
        $review->published_at = $request->published_at;
        $review->type_slug = $request->type_slug;
        $review->item_id = $request->item_id;
        $review->active = $request->active ?? 0;
        $review->save();
        ReviewsHelper::fillInterceptions($request->reviewRegions, 'review_regions',  $review->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($review);
        LogsHelper::addLogEntry($request, "reviews", "create", $review);
        YandexHelper::reportChanges("reviews_".$review->type_slug, $review, null, true);

    }

    public function updateById(Request $request, $id)
    {
        $old_review = Review::findOrFail($id);
        $this->addAllRelationships($old_review);

        $review = Review::findOrFail($id);
        $review->creditor_id = $request->creditor_id;
        $review->user_id = $request->user_id;
        $review->stars = $request->stars ?? 0;
        // $review->views = $request->views ?? 0;
        $review->text = $request->text;
        $review->author = $request->author;
        $review->user_type = $request->user_type;
        if ($request->published_at) {
            $review->published_at = $request->published_at;
        }
        // $review->type_slug = $request->type_slug;
        // $review->item_id = $request->item_id;
        $review->active = $request->active ?? 0;
        $review->save();
        ReviewsHelper::fillInterceptions($request->reviewRegions, 'review_regions', null, $review->id);

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($review);
        LogsHelper::addLogEntry($request, "reviews", "update", $review, $old_review);
        YandexHelper::reportChanges("reviews_".$review->type_slug, $review, $old_review, true);
        /* 
            We validate if the new URL is the same as it was before. 
            In case the new URL is different, we create an automatic redirect.
        */
        RedirectsHelper::validateUrlAndAddRedirect("specific_review", $review, $old_review);

    }

    public function toggleActiveById(Request $request, $id)
    {
        $old_review = Review::findOrFail($id);

        $review = Review::findOrFail($id);
        $review->active =  $review->active ? 0 : 1;
        $review->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($review);
        $this->addAllRelationships($old_review);
        LogsHelper::addLogEntry($request, "reviews", "update", $review, $old_review);
        YandexHelper::reportChanges("reviews_".$review->type_slug, $review, $old_review, true);

    }

    public function deleteById(Request $request, $id)
    {
        $old_review = Review::findOrFail($id);
        $this->addAllRelationships($old_review);

        Review::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "reviews", "delete", null, $old_review);
        YandexHelper::reportChanges("reviews_".$old_review->type_slug, null, $old_review, true);
    }

    public function getCountUnmoderated(Request $request)
    {
        return Review::where('active', 0)->count();
    }
}
