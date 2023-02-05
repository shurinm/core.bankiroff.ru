<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'full_name', 'image'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y H:i');
    }
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->hasOne('App\Models\Roles\Role', 'id', 'role_id')->select("id", "name");
    }

    public function my_creditors()
    {
        return $this->hasMany('App\Interceptions\Users\UsersCreditorsInterception')->select('user_id', 'creditor_id');
    }
    public function my_news_tags()
    {
        return $this->hasMany('App\Interceptions\Users\UsersNewsTagsInterception')->select('user_id', 'news_tag_id');
    }

    public function my_searches()
    {
        return $this->hasMany('App\Models\UsersSearch')->select('user_id', 'text');
    }

    public function my_credits_requests()
    {
        return $this->hasMany('App\Models\Products\Credits\CreditsRequest')->where("status_id", ">", 0);
    }
    public function my_cards_requests()
    {
        return $this->hasMany('App\Models\Products\Cards\CardsRequest')->where("status_id", ">", 0);
    }
    public function my_deposits_requests()
    {
        return $this->hasMany('App\Models\Products\Deposits\DepositsRequest')->where("status_id", ">", 0);
    }

    public function scopeSelectFields($query, $isKeyValue)
    {
        if ($isKeyValue) {
            return $query
                ->select('id AS value', 'nickname as title');
        }
        return $query
            ->select('id', 'nickname');
    }

    public function scopePaginateOrGet($query, $pagination, $xnumber)
    {
        if ($pagination) {
            return $query
                ->paginate($xnumber);
        } else if ($xnumber) {
            return $query
                ->take($xnumber)->get();
        } else {
            return $query->get();
        }
    }

    public function scopeOrderByDate($query)
    {
        return $query
            ->orderBy('created_at', 'desc');
    }

    public function scopeMatchEmailLike($query, $email)
    {
        if ($email) {
            return  $query->where('email', 'like', "%{$email}%");
        }
    }
    public function scopeMatchPhoneLike($query, $phone)
    {
        if ($phone) {
            return  $query->where('phone', 'like', "%{$phone}%");
        }
    }
}
