<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\OTPVerificationController;
use App\Http\Requests\AuthRequest;
use App\Http\Resources\Profile\MaritialStatusResource;
use App\Models\EmailTemplate;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Package;
use App\Notifications\AppEmailVerificationNotification;
use App\Notifications\DbStoreNotification;
use App\Notifications\VerificationCode;
use App\Services\MemberService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use App\Utility\EmailUtility;
use Carbon\Carbon;
use Socialite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registration using api
     */
    public function signup(AuthRequest $request)
    {
        $user_service = new UserService();
        if (addon_activation('referral_system')) {
            $user = $user_service->store($request->only(['first_name', 'last_name', 'email', 'password', 'referral_code', 'phone']));
        } else {
            $user = $user_service->store($request->only(['first_name', 'last_name', 'email', 'password', 'phone']));
        }
        $package = Package::where('id', 1)->first();
        $member_service = new MemberService();
        $request->merge(['user_id' => $user->id]);
        $member = $member_service->store($request->only(['gender', 'birthday', 'on_behalves_id', 'user_id']), $package);

        if (addon_activation('otp_system') && $request->phone != null) {
            $otpController = new OTPVerificationController();
            $otpController->send_code($user);
        }
        // Email to member
        if ($request->email != null && env('MAIL_USERNAME') != null) {
            $account_oppening_email = EmailTemplate::where('identifier', 'account_oppening_email')->first();
            if ($account_oppening_email->status == 1) {
                EmailUtility::account_oppening_email($user->id, $request->password);
            }
        }

        try {
            $notify_type = 'member_registration';
            $id = unique_notify_id();
            $notify_by = $user->id;
            $info_id = $user->id;
            $message = translate('A new member has been registered to your system. Name: ') . $user->first_name . ' ' . $user->last_name;
            $route = route('members.index', $user->membership);

            Notification::send(User::where('user_type', 'admin')->first(), new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
        } catch (\Exception $e) {
            // dd($e);
        }

        if (env('MAIL_USERNAME') != null && get_email_template('account_opening_email_to_admin', 'status') == 1) {
            $admin = User::where('user_type', 'admin')->first();
            EmailUtility::account_opening_email_to_admin($user, $admin);
        }

        //create token
        $user->createToken('tokens')->plainTextToken;

        if (get_setting('email_verification') != 1) {
            if ($user->email != null || $user->phone != null) {
                $user->email_verified_at = date('Y-m-d H:m:s');
                $user->save();
                return response()->json(
                    [
                        'result' => true,
                        'message' => 'Registration successfull.',

                    ],
                    201
                );
            }
        } else {
            // verification code send to user
            $user->notify(new VerificationCode($user));
            return response()->json(
                [
                    'result' => true,
                    'message' => 'Registration successfull. Please verify your email.',

                ],
                201
            );
        }
    }

    /**
     * Login using api
     */

    public function signin(Request $request)
    {
        $user = User::where('email', $request->email_or_phone)
            ->orWhere('phone', $request->email_or_phone)
            ->first();

        //memeber check
        if (\App\Utility\MemberUtility::member_check($request->identity_matrix) == false) {
            return response()->json(['result' => false, 'message' => 'Identity matrix error', 'user' => null], 401);
        }

        if ($user != null) {
            if (Hash::check($request->password, $user->password)) {
                // if ($user->email_verified_at == null) {
                //     $user->verification_code = rand(100000, 999999);
                //     $user->save();
                //     $user->notify(new VerificationCode($user));
                //     return response()->json(['result' => false, 'message' => translate('Please verify your account'), 'user' => null], 401);
                // }
                return $this->loginSuccess($user);
            }
            return response()->json(['result' => false, 'message' => translate('Unauthorized'), 'user' => null], 401);
        }
        return response()->json(['result' => false, 'message' => translate('User not found'), 'user' => null], 401);
    }

    /**
     * Social Login
     */
    public function socialLogin(Request $request)
    {
        if (!$request->provider) {
            return response()->json([
                'result' => false,
                'message' => translate('User not found'),
                'user' => null,
            ]);
        }

        switch ($request->social_provider) {
            case 'facebook':
                $social_user = Socialite::driver('facebook')->fields(['name', 'first_name', 'last_name', 'email']);
                break;
            case 'google':
                $social_user = Socialite::driver('google')->scopes(['profile', 'email']);
                break;
            case 'twitter':
                $social_user = Socialite::driver('twitter');
                break;
            case 'apple':
                $social_user = Socialite::driver('sign-in-with-apple')->scopes(['name', 'email']);
                break;
            default:
                $social_user = null;
        }
        if ($social_user == null) {
            return response()->json(['result' => false, 'message' => translate('No social provider matches'), 'user' => null]);
        }

        $existingUserByProviderId = User::where('provider_id', $request->provider)->first();

        if ($existingUserByProviderId) {
            if ($existingUserByProviderId->approved == 0) {
                return response()->json(['result' => false, 'message' => translate('Please wait for admin approval'), 'user' => null], 401);
            } else {
                return $this->loginSuccess($existingUserByProviderId);
            }

        } else {
            // create a new user
            $newUser                     = new User;
            $newUser->first_name         = $request->name;
            $newUser->email              = $request->email;
            $newUser->email_verified_at  = date('Y-m-d H:m:s');
            $newUser->provider_id        = $request->provider;
            $newUser->code               = unique_code();
            $newUser->membership         = 1;
            $newUser->approved           = get_setting('member_approval_by_admin') == 1 ? 0 : 1;
            $newUser->save();

            $member                             = new Member;
            $member->user_id                    = $newUser->id;
            $member->gender                     = null;
            $member->on_behalves_id             = null;
            $member->birthday                   = null;

            $package                                = Package::where('id', 1)->first();
            $member->current_package_id             = $package->id;
            $member->remaining_interest             = $package->express_interest;
            $member->remaining_photo_gallery        = $package->photo_gallery;

            $member->remaining_contact_view         = $package->contact;
            $member->remaining_profile_image_view    = $package->profile_image_view;
            $member->remaining_gallery_image_view   = $package->gallery_image_view;
            $member->auto_profile_match             = $package->auto_profile_match;
            $member->package_validity               = Date('Y-m-d', strtotime($package->validity . " days"));
            $member->save();

            if ($newUser->approved == 0) {
                return response()->json(['result' => false, 'message' => translate('Please wait for admin approval'), 'user' => null], 401);
            } else {
                return $this->loginSuccess($newUser);
            }
        }
    }

    /**
     * Log Out using api
     */

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user
            ->tokens()
            ->where('id', $user->currentAccessToken()->id)
            ->delete();
        return $this->success_message('Successfully logged out');
    }

    /**
     * Log in success
     */

    protected function loginSuccess($user)
    {
        // check email verification
        if (get_setting('email_verification') == 1) {
            if ($user->email_verified_at == null) {
                return response()->json([
                    'result' => false, 'message' => translate('Please verify your account'),
                    'user' => null
                ], 401);
            }
        }
        // check admin approval
        if (get_setting('member_approval_by_admin') == 1) {
            if ($user->approved == 0) {
                auth()->logout();
                return response()->json(['result' => false, 'message' => translate('Please wait for admin approval'), 'user' => null], 401);
            }
        }

        $maritial_status = MaritalStatus::where('id', $user->member->marital_status_id)->first();
        $token = $user->createToken('API Token')->plainTextToken;
        $age = Carbon::parse($user->member->birthday)->age;

        return response()->json([
            'result' => true,
            'message' => translate('Successfully logged in'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => null,
            'user' => [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'birthday' => $age,
                'height' => $user->physical_attributes ? $user->physical_attributes->height : '',
                'marital_status_id' => $maritial_status ? new MaritialStatusResource($maritial_status) :  new MaritialStatusResource($maritial_status),
                'avatar' => $user->avatar ?? '',
                'avatar_original' => uploaded_asset($user->avatar_original) ?? '',
                'phone' => $user->phone ?? '',
            ],
        ]);
    }

    /**
     * Forgot password request from forgot password form
     * generate a code and send it via email or phone
     */

    public function forgotPassword(Request $request)
    {
        if ($request->send_code_by == 'email') {
            $this->validate($request, [
                'email_or_phone' => 'required|email',
            ]);
            $user = User::where('email', $request->email_or_phone)->first();
        } else {
            $this->validate($request, [
                'email_or_phone' => 'required',
            ]);
            $user = User::where('phone', $request->email_or_phone)->first();
        }
        if ($user) {
            $user->verification_code = rand(1000, 9999);
            $user->save();
            if ($request->send_code_by == 'phone') {
                $otpController = new OTPVerificationController();
                $otpController->send_code($user);
            } else {
                $user->notify(new AppEmailVerificationNotification($user));
            }
        } else {
            return response()->json(
                [
                    'result' => false,
                    'message' => translate('User is not found'),
                ],
                404,
            );
        }

        return response()->json(
            [
                'result' => true,
                'message' => translate('A code is sent'),
            ],
            200,
        );
    }

    /**
     * Verify registered user first
     * Verify code
     */

    public function verifyCode(Request $request)
    {
        $this->validate($request, [
            'verification_code' => 'required',
        ]);
        $user = User::where('verification_code', $request->verification_code)->first();
        if ($user) {
            $user->email_verified_at = Carbon::now();;
            $user->save();
            return $this->success_message('Verify code matched!!');
        }
        return $this->failure_message('Verification code does not match!!');
    }

    /**
     * Verify registered user first
     * Reset verification code
     * insert new password
     */

    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($request->send_code_by == 'phone' && !empty($request->email_or_phone)) {
            $user = User::where('phone', $request->email_or_phone)->first();
        } elseif ($request->send_code_by == 'email' && !empty($request->email_or_phone)) {
            $user = User::where('email', $request->email_or_phone)->first();
        }

        if (!$user) {
            return $this->failure_message('User not found!!');
        }
        if ($user->verification_code == $request->verification_code) {
            $user->password = Hash::make($request['password']);
            $user->verification_code = null;
            $user->save();

            return $this->success_message('Password has been updated, you can login now');
        }
        return $this->failure_message('Verification code does not match.');
    }
}
