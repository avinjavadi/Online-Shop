<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class userError
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->session()->has('user'))
            return redirect()->route('home')->with('user_error','لطفا ابتدا از حساب خود خارج شوید');
        return $next($request);
    }
}
