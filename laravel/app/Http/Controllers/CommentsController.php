<?php

namespace App\Http\Controllers;

use App\Helpers\CommentsHelper;
use App\Helpers\LogsHelper;

use Illuminate\Http\Request;

use App\Models\News\NewsComment;
use App\Models\Reviews\ReviewsComment;
use App\Models\Reviews\Review;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailToStaff;
use App\Mail\SendMailToUser;
use App\User;
use stdClass;

class CommentsController extends Controller
{

    public function getNewsCommentsByUserId(Request $request, $id)
    {
        $comments = NewsComment::where('user_id', $id)->get();

        foreach ($comments as $key => $comment) {
            $comment->news;
        }
        return $comments;
    }


    public function addForReview(Request $request)
    {
        $comment_for_review = new ReviewsComment();
        $comment_for_review->review_id = $request->review_id;
        $comment_for_review->user_id = $request->user_id;
        $comment_for_review->text = $request->text;
        $comment_for_review->save();
        $user = User::where('id', $request->user_id)->first();
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->text = $request->text;
        Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_REVIEW_COMMENT'));
        return Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_REVIEW_COMMENT'));
    }

    public function addForNews(Request $request)
    {
        $comment_for_news = new NewsComment();
        $comment_for_news->news_id = $request->news_id;
        $comment_for_news->user_id = $request->user_id;
        $comment_for_news->text = $request->text;
        $comment_for_news->save();
        $user = User::where('id', $request->user_id)->first();
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->text = $request->text;
        Mail::to(env('MAIL_STAFF_INFO'))->send(new SendMailToStaff($data_obj, 'NEW_NEWS_COMMENT'));
        return Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_NEWS_COMMENT'));
    }

    public function updateForNewsByUser(Request $request, $id)
    {
        $comment = NewsComment::findOrFail($id);
        $comment->active = 0;
        $comment->text = $request->text;
        $comment->save();
        $user = User::where('id', $comment->user_id)->first();
        $data_obj = new stdClass();
        $data_obj->email = $user->email;
        $data_obj->text = $request->text;
        return Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEWS_COMMENT_UPDATED'));
    }


    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    /* Routes for ADMIN, protected by JWT and double verification with email, the verification happens on the middleware EnsureHasPermissions */
    /*----------------------------------------------------------------------------------------------------------------------------------------------- */
    public function addAllRelationships($comment)
    {
        $comment->user;
        $comment->news;
        $comment->review;
    }

    /* For news */
    public function getAllForNews(Request $request, $xnumber = 10)
    {
        $comments = NewsComment::matchCommentTextLike($request->commentText ?? null)
            ->matchTitleNewsLike($request->newsTitle ?? null)
            ->matchUsersNicknameLike($request->nick ?? null)
            ->orderByDate(null)
            ->paginateOrGet($request->page, $xnumber);
        CommentsHelper::addUserToComment($comments);
        CommentsHelper::addNewsToComment($comments);
        if ($request->page) {
            $comments_data = $comments->all();
            $comments_with_subdomain = CommentsHelper::addSubdomain($comments_data);
            $comments->data = $comments_with_subdomain;
            return $comments;
        }

        $comments_with_subdomain = CommentsHelper::addSubdomain($comments);
        return $comments_with_subdomain;
    }

    public function getByIdFullForNews(Request $request, $id)
    {
        $comment = NewsComment::findOrFail($id);
        $this->addAllRelationships($comment);
        return $comment;
    }

    public function addForNewsByAdmin(Request $request)
    {
        $comment = new NewsComment();
        $comment->news_id = $request->news_id;
        $comment->user_id = $request->user_id;
        $comment->active = $request->active ? 1 : 0;
        $comment->text = $request->text;
        $comment->save();

        if ($comment->active) {
            $comments = NewsComment::where('news_id', $comment->news_id)->where('active', 1)->get();
            if (count($comments) > 0) {
                $data_obj = new stdClass();
                $data_obj->text =  $comment->text;
                foreach ($comments as $key => $element) {
                    $user = User::findOrFail($element->user_id);;
                    Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_COMMENT_TO_NEWS'));
                }
            }
        }
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($comment);
        LogsHelper::addLogEntry($request, "news.comments", "create", $comment);
    }

    public function updateForNews(Request $request, $id)
    {

        $old_comment = NewsComment::findOrFail($id);
        $this->addAllRelationships($old_comment);
        $comment = NewsComment::findOrFail($id);
        $comment->news_id = $request->news_id;
        $comment->user_id = $request->user_id;
        $comment->active = $request->active ? 1 : 0;
        $comment->text = $request->text;
        $comment->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($comment);
        LogsHelper::addLogEntry($request, "news.comments", "update", $comment, $old_comment);
    }

    public function toggleActiveByIdForNews(Request $request, $id)
    {
        $old_comment = NewsComment::findOrFail($id);
        $comment = NewsComment::findOrFail($id);
        $comment->active =  $comment->active ? 0 : 1;
        $comment->save();

        if ($comment->active) {
            $comments = NewsComment::where('news_id', $comment->news_id)->where('active', 1)->get();
            if (count($comments) > 0) {
                $data_obj = new stdClass();
                $data_obj->text =  $comment->text;
                foreach ($comments as $key => $element) {
                    $user = User::findOrFail($element->user_id);;
                    Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_COMMENT_TO_NEWS'));
                }
            }
        }

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($comment);
        $this->addAllRelationships($old_comment);
        LogsHelper::addLogEntry($request, "news.comments", "update", $comment, $old_comment);
    }

    public function deleteByIdForNews(Request $request, $id)
    {

        $old_comment = NewsComment::findOrFail($id);
        $this->addAllRelationships($old_comment);

        NewsComment::findOrFail($id)->delete();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "news.comments", "delete", null, $old_comment);
    }

    public function getCountUnmoderatedForNews(Request $request)
    {
        return NewsComment::where('active', 0)->count();
    }

    /*--------------------------------------------------------------------------------*/
    /* For Reviews */

    public function getAllForReviews(Request $request, $xnumber = 10)
    {
        $comments = ReviewsComment::matchCommentTextLike($request->commentText ?? null)
            ->matchUsersNicknameLike($request->nick ?? null)
            ->orderByDate(null)
            ->paginateOrGet($request->page, $xnumber);
        CommentsHelper::addUserToComment($comments);
        CommentsHelper::addReviewToComment($comments);
        if ($request->page) {
            $comments_data = $comments->all();
            $comments_with_timestamps = CommentsHelper::addTimestampsPublishedAtReview($comments_data);
            $comments->data = $comments_with_timestamps;
            return $comments;
        }
        $comments_with_timestamps = CommentsHelper::addTimestampsPublishedAtReview($comments);

        return $comments_with_timestamps;
    }
    public function getByIdFullForReviews(Request $request, $id)
    {
        $comment = ReviewsComment::findOrFail($id);
        $this->addAllRelationships($comment);
        return $comment;
    }
    public function addForReviewsByAdmin(Request $request)
    {
        $comment = new ReviewsComment();
        $comment->review_id = $request->review_id;
        $comment->user_id = $request->user_id;
        $comment->active = $request->active ?? 0;
        $comment->text = $request->text;
        $comment->save();
        if ($comment->active) {
            $review = Review::findOrFail($comment->review_id);
            $user = User::findOrFail($review->user_id);;
            $data_obj = new stdClass();
            $data_obj->text =  $comment->text;
            Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_COMMENT_TO_REVIEW'));
        }
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($comment);
        LogsHelper::addLogEntry($request, "reviews.comments", "create", $comment);
    }

    public function updateForReviews(Request $request, $id)
    {
        $old_comment = ReviewsComment::findOrFail($id);
        $this->addAllRelationships($old_comment);

        $comment = ReviewsComment::findOrFail($id);
        $comment->review_id = $request->review_id;
        $comment->user_id = $request->user_id;
        $comment->active = $request->active ?? 0;
        $comment->text = $request->text;
        $comment->save();

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table */
        $this->addAllRelationships($comment);
        LogsHelper::addLogEntry($request, "reviews.comments", "update", $comment, $old_comment);

    }

    public function toggleActiveByIdForReviews(Request $request, $id)
    {
        $old_comment = ReviewsComment::findOrFail($id);
        $comment = ReviewsComment::findOrFail($id);
        $comment->active =  $comment->active ? 0 : 1;
        $comment->save();
        if ($comment->active) {
            $review = Review::findOrFail($comment->review_id);
            $user = User::findOrFail($review->user_id);;
            $data_obj = new stdClass();
            $data_obj->text =  $comment->text;
            Mail::to($user->email)->send(new SendMailToUser($data_obj, 'NEW_COMMENT_TO_REVIEW'));
        }

        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        $this->addAllRelationships($comment);
        $this->addAllRelationships($old_comment);
        LogsHelper::addLogEntry($request, "reviews.comments", "update", $comment, $old_comment);
    }

    public function deleteByIdForReviews(Request $request, $id)
    {
        $old_comment = ReviewsComment::findOrFail($id);
	    $this->addAllRelationships($old_comment);
        ReviewsComment::findOrFail($id)->delete();
        
        /* Internal logs logic. We add all the relationships for the product and save all that info in the internal_logs table*/
        LogsHelper::addLogEntry($request, "reviews.comments", "delete", null, $old_comment);
    }

    public function getCountUnmoderatedForReviews(Request $request)
    {
        return ReviewsComment::where('active', 0)->count();
    }
}
