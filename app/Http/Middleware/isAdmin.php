<?php

namespace App\Http\Middleware;
use App\Http\Controllers\AuthenticationController;
use Closure;
use Auth;
class isAdmin
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
        if(Auth::User()->role == 'admin'){
            return $next($request);
        }else{
            return redirect()->action([AuthenticationController::class, 'adminAuthError']);
        }
        
    }
}
