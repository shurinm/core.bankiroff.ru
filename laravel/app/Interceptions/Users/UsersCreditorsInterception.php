<?php

namespace App\Interceptions\Users;

use Illuminate\Database\Eloquent\Model;

class UsersCreditorsInterception extends Model
{

    protected $fillable = [
        'user_id', 'creditor_id'
    ];

    public function creditor()
    {
        return $this->belongsTo('App\Models\Creditors\Creditor', 'creditor_id', 'id')->where('active', 1)->select('id', 'name', 'type_slug');
    }
}
