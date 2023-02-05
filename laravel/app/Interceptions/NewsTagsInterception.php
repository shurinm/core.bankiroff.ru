<?php

namespace App\Interceptions;

use Illuminate\Database\Eloquent\Model;

class NewsTagsInterception extends Model
{
    public function tag()
    {
        return $this->belongsTo('App\Models\News\NewsTag', 'news_tag_id', 'id')->select("id", "title");
    }

    public function users()
    {
        return $this->hasManyThrough('App\User', 'App\Interceptions\Users\UsersNewsTagsInterception', "news_tag_id", "id", "news_tag_id", "user_id");
    }
}
