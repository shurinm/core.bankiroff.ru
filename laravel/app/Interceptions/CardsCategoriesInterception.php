<?php

namespace App\Interceptions;

use Illuminate\Database\Eloquent\Model;

class CardsCategoriesInterception extends Model
{
    public function category()
    {
        return $this->belongsTo('App\Models\Products\Cards\CardsCategory', 'card_category_id');
    }
}
