<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use JWTAuth;
use Str;

class JwtHelper
{

    public static function isAdmin()
    {
        $token = JWTAuth::getToken();
        if (!$token) return false;
        $user = JWTAuth::toUser($token);
        if (!$user) return false;
        if ($user->role_id == 1 || $user->role_id == 4 || $user->role_id == 5 || $user->role_id == 6) return true;
        return false;
    }

    public static function getUser()
    {
        $token = JWTAuth::getToken();
        if (!$token) return null;
        $user = JWTAuth::toUser($token);
        if (!$user) return null;
        return $user->id;
    }
}
