<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    protected $codeExpiration = 20; // Code expires in 20 minutes
    protected $resendCooldown = 1;  // User can resend every 1 minute (adjust as needed)

    public function show(Request $request)
    {
        // Pass email to the view, useful for pre-filling or context
        return view('auth.verify', [
            'email' => $request->get('email')
        ]);
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email', // Ensure email is always provided for resend
        ]);

        $email = $request->get('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            session()->flash(translate('error', 'The provided email address is not registered.'))->error();
            return redirect('/register'); // Redirect to login if email not found
        }

        if ($user->email_verified_at !== null) {
            session()->flash(translate('success', 'Your email is already verified. Please try logging in.'))->info();
            return redirect('/login');
        }

        // --- ADDED: Implement Rate Limiting for Resend ---
        if ($user->verification_code_sent_at) {
            $lastSent = Carbon::parse($user->verification_code_sent_at);
            if ($lastSent->addMinutes($this->resendCooldown)->isFuture()) {
                $timeRemaining = $lastSent->diffInSeconds(Carbon::now());
                $minutesRemaining = ceil($timeRemaining / 60);
                session()->flash(translate('error', "Please wait {$minutesRemaining} more minutes before requesting a new code."))->info();
                return back();
            }
        }
        // --- END Rate Limiting ---

        $user->verification_code = $this->generateVerificationCode();
        $user->verification_code_sent_at = now();
        $user->save();

        $user->notify(new VerificationCode($user->verification_code));

        session()->flash(translate('success', 'A new verification code has been sent to your email.'))->info();
        return back();
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'email' => 'required|email', // Require email to prevent guessing codes for other users
        ]);

        $code = $request->input('code');
        $email = $request->input('email');

        // Find user by both email and code for security
        $user = User::where('email', $email)
            ->where('verification_code', $code)
            ->first();

        if (!$user) {
            session()->flash(translate('error', 'Invalid verification code or email.'))->error();
            // Pass email back so the form can be pre-filled again
            return redirect('/verify-form?email=' . urlencode($email));
        }

        if ($this->isCodeExpired($user)) {
            session()->flash(translate('error', 'Verification code expired. Please request a new one.'))->error();
            // Pass email back
            return redirect('/verify-form?email=' . urlencode($email));
        }

        $user->markEmailAsVerified();
        $user->verification_code = null;
        $user->verification_code_sent_at = null;
        $user->save();

        session()->flash(translate('success', 'Email verified successfully! You are now logged in.'))->success();

        // ADDED: Log the user in after successful verification
        Auth::login($user);

        // Redirect to their intended destination (e.g., dashboard)
        return redirect('/');
    }

    protected function generateVerificationCode()
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function isCodeExpired(User $user)
    {
        if (!$user->verification_code_sent_at) {
            return true; // Should not happen if a code was expected
        }
        return Carbon::parse($user->verification_code_sent_at)
            ->addMinutes($this->codeExpiration)
            ->isPast();
    }
}
