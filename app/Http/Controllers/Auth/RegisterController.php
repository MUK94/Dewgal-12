<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Member; // Assuming these are needed for registration
use App\Models\Package;
use App\Models\EmailTemplate;
use App\Rules\RecaptchaRule; // Assuming you have this custom rule
use App\Utility\EmailUtility; // Assuming you have this utility class
use App\Http\Controllers\Controller;
use App\Notifications\VerificationCode; // For sending the initial code
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification; // May not be strictly needed if using $user->notify()
use Illuminate\Auth\Events\Registered; // If you want to dispatch this event
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Carbon\Carbon; // Ensure Carbon is imported

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest'); // Only guests can register
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('frontend.user_registration');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'on_behalf' => 'required|integer',
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => 'required',
            'date_of_birth' => 'required|date',
            // Use 'required_without' to allow either phone or email, but not both null
            'phone' => 'required_without:email|nullable|string|unique:users',
            'email' => 'required_without:phone|nullable|email|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'g-recaptcha-response' => [
                // Apply reCAPTCHA rule conditionally based on settings
                Rule::when(function_exists('get_setting') && get_setting('google_recaptcha_activation') == 1, ['required', new RecaptchaRule()], ['sometimes'])
            ],
            'checkbox_example_1' => ['required', 'string'], // Assuming this is for terms & conditions
        ], [
            // Custom error messages
            'on_behalf.required' => translate('on_behalf is required'),
            'on_behalf.integer' => translate('on_behalf should be integer value'),
            'first_name.required' => translate('first_name is required'),
            'last_name.required' => translate('last_name is required'),
            'gender.required' => translate('gender is required'),
            'date_of_birth.required' => translate('date_of_birth is required'),
            'date_of_birth.date' => translate('date_of_birth should be in date format'),
            'email.required_without' => translate('Email is required'),
            'email.email' => translate('Email must be a valid email address'),
            'email.unique' => translate('A user exists with this email'),
            'phone.unique' => translate('A user exists with this phone'),
            'phone.required_without' => translate('Phone is required'),
            'password.required' => translate('Password is required'),
            'password.confirmed' => translate('Password confirmation does not match'),
            'password.min' => translate('Minimum 8 digits required for password'),
            'checkbox_example_1.required' => translate('You must agree to our terms and conditions.'),
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Determine if admin approval is needed using get_setting() helper
        $approval = (function_exists('get_setting') && get_setting('member_approval_by_admin') == 1) ? 0 : 1;
        $isEmail = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL); // Null coalescing operator for safety

        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'membership' => 1, // Default membership
            'password' => Hash::make($data['password']),
            'code' => function_exists('unique_code') ? unique_code() : null, // Assuming unique_code() helper exists
            'approved' => $approval,
            'email_verified_at' => null, // User is unverified upon registration
            'verification_code' => str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT), // Generate initial code
            'verification_code_sent_at' => now(), // Record when it was sent
        ];

        // Assign email or phone based on validation
        if ($isEmail) {
            $userData['email'] = $data['email'];
        } elseif (function_exists('addon_activation') && addon_activation('otp_system')) {
            $userData['phone'] = '+' . ($data['country_code'] ?? '') . $data['phone']; // Ensure country_code is present
        }

        $user = User::create($userData);

        // Handle referral system if activated
        if (function_exists('addon_activation') && addon_activation('referral_system') && !empty($data['referral_code'])) {
            $referrer = User::whereNotNull('code')->where('code', $data['referral_code'])->first();
            if ($referrer) {
                $user->referred_by = $referrer->id;
                $user->save(); // Save after updating referred_by
            }
        }

        // Create associated member profile
        $member = new Member();
        $member->user_id = $user->id;
        $member->gender = $data['gender'];
        $member->on_behalves_id = $data['on_behalf'];
        $member->birthday = date('Y-m-d', strtotime($data['date_of_birth']));

        $package = Package::find(1); // Assuming package ID 1 is the default package
        if ($package) {
            $member->current_package_id = $package->id;
            $member->remaining_interest = $package->express_interest;
            $member->remaining_photo_gallery = $package->photo_gallery;
            $member->remaining_contact_view = $package->contact;
            $member->remaining_profile_image_view = $package->profile_image_view;
            $member->remaining_gallery_image_view = $package->gallery_image_view;
            $member->auto_profile_match = $package->auto_profile_match;
            $member->package_validity = Carbon::now()->addDays($package->validity);
        }
        $member->save();

        // Send account opening email (if mail is configured and template is active)
        if ($isEmail && env('MAIL_USERNAME') && function_exists('EmailUtility::account_oppening_email')) {
            $template = EmailTemplate::where('identifier', 'account_oppening_email')->first();
            if ($template && $template->status == 1) {
                EmailUtility::account_oppening_email($user->id, $data['password']);
            }
        }

        // Send the initial verification code notification
        // This is the *only* place the initial code is sent for new registrations.
        $user->verification_code_sent_at = now();
        $user->save();
        $user->notify(new VerificationCode($user->verification_code));
        return $user;
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // 1. Check for duplicates *before* validation to provide more specific error messages
        // This is a common pattern in your existing code.
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $request->email)->exists()) {
                flash(translate('Email already exists.'))->error();
                return back();
            }
        } elseif (isset($request->phone) && User::where('phone', '+' . $request->country_code . $request->phone)->exists()) {
            flash(translate('Phone already exists.'))->error();
            return back();
        }

        // 2. Validate and create the user (the create method now handles code generation and initial notification)
        $this->validator($request->all())->validate();
        $user = $this->create($request->all());

        // 3. Notify admin about the new registration (if applicable)
        if (env('MAIL_USERNAME') && function_exists('get_email_template') && get_email_template('account_opening_email_to_admin', 'status') == 1) {
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                EmailUtility::account_opening_email_to_admin($user, $admin);
            }
        }

        // 4. Dispatch the Registered event (optional, but standard Laravel)
        // event(new Registered($user)); // Uncomment if you have listeners for this event

        // 5. Redirect to the verification form.
        flash(translate('Registration successful! Please verify your account to continue.'));
        return redirect('/verify-form?email=' . urlencode($user->email));

        // The default `RegistersUsers` trait's `registered` method is usually called after this,
        // but since you're returning a redirect here, it won't be explicitly called.
        // If you rely on `registered` for other logic, you might need to adjust.
    }

    /**
     * The user has been registered.
     * (This method is part of RegistersUsers trait, but your custom `register` method
     * might bypass it if it returns a redirect directly.)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function registered(Request $request, $user)
    {
        // This logic will only be hit if your custom `register` method doesn't return
        // a redirect itself. Given your current `register` method, it likely won't be.
        // It provides a fallback behavior.
        if (function_exists('get_setting') && get_setting('member_approval_by_admin') == 1) {
            return redirect()->route('user.login')->with('error', 'Your account has been created. Please wait for admin approval.');
        }

        return redirect()->route('login'); // Or whatever your default authenticated route is
    }
}
