<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class EnsureHasPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = User::where('email', $request->keyEmail)->select('role_id')->first();
        if(!$user || !$user->role_id || $user->role_id == 2) return abort(401);
        
        return $next($request);
    }
}
