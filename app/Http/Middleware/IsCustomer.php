<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsCustomer
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
        if (Auth::check() && (Auth::user()->user_type == 'customer')) {
            return $next($request);
        }
        else{
            // #region agent log
            if (str_contains($request->path(), 'product-notify')) {
                @file_put_contents(base_path('debug-a0012d.log'), json_encode(['sessionId'=>'a0012d','hypothesisId'=>'B','location'=>'IsCustomer.php:handle','message'=>'customer middleware blocked notify','data'=>['auth'=>Auth::check(),'user_type'=>optional(Auth::user())->user_type,'path'=>$request->path(),'method'=>$request->method()],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
            }
            // #endregion
            session(['link' => url()->current()]);
            return redirect()->route('user.login');
        }
    }
}
