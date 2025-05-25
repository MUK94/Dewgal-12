<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCustomEmailIsVerified
{
    public function handle(Request $request, Closure $next, $requireVerified = true)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Allow access if verification matches requirement
        if ($requireVerified && !$request->user()->email_verified_at) {
            return redirect()->route('verification.code.form')
                ->with('error', 'You must verify your email first.');
        }

        // For routes that should only be accessible to UNVERIFIED users
        if ($requireVerified === 'false' && $request->user()->email_verified_at) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
