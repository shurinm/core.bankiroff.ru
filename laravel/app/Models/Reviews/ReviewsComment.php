<?php

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReviewsComment extends Model
{

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y H:i');
    }

    public function user()
    {
        return $this->belongsTo('App\User')->select('id', 'nickname', 'full_name', 'image', 'created_at');
    }

    public function review()
    {
        return $this->belongsTo('App\Models\Reviews\Review');
    }

    public function scopeOrderByDate($query, $sort)
    {
        if ($sort && $sort !== 'default') {
            return $query
                ->orderBy('created_at', $sort);
        }

        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            return $query
                ->paginate($xnumber);
        } else if ($xnumber && $xnumber != 'all') {
            return $query
                ->take($xnumber)->get();
        } else {
            return $query->get();
        }
    }


    public function scopeMatchCommentTextLike($query, $text)
    {
        if ($text) {
            return  $query->where('text', 'like', "%{$text}%");
        }
    }

    public function scopeMatchUsersNicknameLike($query, $nick)
    {
        if ($nick) {
            return $query
                ->whereHas('user', function ($query) use ($nick) {
                    $query->where('nickname', 'like', "%{$nick}%");
                });
        }
    }
}
