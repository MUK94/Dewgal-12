<?php
namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Closure;


class IsAdmin
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
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            return $next($request);
        }
        return redirect()->route('admin.login');
    }
}
