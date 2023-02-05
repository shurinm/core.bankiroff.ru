<?php

namespace App\Interceptions\Users;

use Illuminate\Database\Eloquent\Model;

class UsersNewsTagsInterception extends Model
{

    public function newsTag()
    {
        return $this->belongsTo('App\Models\News\NewsTag', 'news_tag_id', 'id')->select('id', 'title');
    }
}
