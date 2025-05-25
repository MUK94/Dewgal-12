<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class EnsureUserIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        // Define routes that do not require email verification
        $publicRoutes = [
            'index',
            'home',
            'contact_us',
            'contact-us.store',
            'user.login',
            'social.login',
            'social.callback',
            'verify.form',          // Verification form itself
            'verify.resend',        // Resend code endpoint
            'verify.code',          // Verify code endpoint
            'email.verification.confirmation', // Assuming this is part of verification flow
            'password.update',      // Password reset/update
            'language.change',
            'currency.change',
            'packages',             // Publicly viewable packages
            'happy_stories',
            'story_details',
            'blog',
            'blog.details',
            'user.registration',    // Registration route
            'register',             // Default Laravel register route if used
        ];

        $currentRouteName = Route::currentRouteName();

        // Allow access if it's a public route or user is not authenticated
        if (in_array($currentRouteName, $publicRoutes) || !Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // If the user's email is verified, allow access to the requested page
        if ($user->email_verified_at) {
            return $next($request);
        } else {
            // If not verified, redirect to the verification form.
            // Do NOT send a new code here; that's handled by VerificationController@resend.
            flash(translate('Your email address is not verified. Please verify your email to access this page, or resend the code if needed.'))->error();
            return redirect()->route('verify.form');
        }
    }
}
