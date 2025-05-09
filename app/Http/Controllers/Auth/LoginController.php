<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Socialite;
use App\Models\User;
use App\Models\Hobby;
use App\Models\Staff;
use App\Models\Career;
use App\Models\Family;
use App\Models\Member;
use App\Models\Address;
use App\Models\Package;
use App\Models\Attitude;
use App\Models\Astrology;
use App\Models\Education;
use App\Models\Lifestyle;
use App\Models\Recidency;
use App\Models\Shortlist;
use App\Models\ChatThread;
use App\Models\HappyStory;
use App\Models\IgnoredUser;
use App\Models\GalleryImage;
use App\Models\ProfileMatch;
use App\Models\ReportedUser;
use CoreComponentRepository;
use Illuminate\Http\Request;
use App\Models\PackagePayment;
use App\Models\ExpressInterest;
use App\Models\PhysicalAttribute;
use App\Models\PartnerExpectation;
use App\Models\SpiritualBackground;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use GeneaLabs\LaravelSocialiter\Facades\Socialiter;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function redirectToProvider($provider)
    {
        if (request()->get('query') == 'mobile_app') {
            request()->session()->put('login_from', 'mobile_app');
        }
        if ($provider == 'apple') {
            return Socialite::driver("sign-in-with-apple")
                ->scopes(["name", "email"])
                ->redirect();
        }
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(Request $request, $provider)
    {
        if (session('login_from') == 'mobile_app') {
            return $this->mobileHandleProviderCallback($request, $provider);
        }
        try {
            if ($provider == 'twitter') {
                $user = Socialite::driver('twitter')->user();
            } else {
                $user = Socialite::driver($provider)->stateless()->user();
            }
        } catch (\Exception $e) {
            return redirect()->route('user.login')->with('error', "Something Went wrong. Please try again.");
        }
        $existingUser = User::where('provider_id', $user->id)->orWhere('email', $user->email)->first();

        if ($existingUser) {
            if($existingUser->approved == 0){
                return redirect()->route('user.login')->with('error', "Please wait for the admin approval.");
            } else {
                Auth::login($existingUser, true);
            }

        } else {
            $newUser = new User;
            $newUser->first_name = $user->name;
            $newUser->email = $user->email;
            $newUser->email_verified_at = date('Y-m-d H:m:s');
            $newUser->provider_id = $user->id;
            $newUser->code = unique_code();
            $newUser->membership = 1;
            $newUser->approved = get_setting('member_approval_by_admin') == 1 ? 0 : 1;
            $newUser->save();

            $member = new Member;
            $member->user_id = $newUser->id;
            $member->gender = null;
            $member->on_behalves_id = null;
            $member->birthday = null;

            $package = Package::where('id', 1)->first();
            $member->current_package_id = $package->id;
            $member->remaining_interest = $package->express_interest;
            $member->remaining_photo_gallery = $package->photo_gallery;
            $member->remaining_contact_view = $package->contact;
            $member->remaining_profile_image_view = $package->profile_image_view;
            $member->remaining_gallery_image_view = $package->gallery_image_view;
            $member->auto_profile_match = $package->auto_profile_match;
            $member->package_validity = Date('Y-m-d', strtotime($package->validity . " days"));
            $member->save();

            if($newUser->approved == 0){
                return redirect()->route('user.login')->with('error', "Please wait for the admin approval.");
            } else {
                Auth::login($newUser, true);
            }
        }
        if (session('link') != null) {
            return redirect(session('link'));
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function handleAppleCallback(Request $request)
    {
        try {
            $user = Socialite::driver("sign-in-with-apple")->user();
        } catch (\Exception $e) {
            return redirect()->route('user.login')->with('error', translate("Something Went wrong. Please try again."));
        }
        $existingUserByProviderId = User::where('provider_id', $user->id)->first();

        if ($existingUserByProviderId) {
            $existingUserByProviderId->access_token = $user->token;
            $existingUserByProviderId->refresh_token = $user->refreshToken;
            if (!isset($user->user['is_private_email'])) {
                $existingUserByProviderId->email = $user->email;
            }
            $existingUserByProviderId->save();
            Auth::login($existingUserByProviderId, true);
        } else {
            $existing_or_new_user = User::firstOrNew([
                'email' => $user->email
            ]);
            $existing_or_new_user->provider_id = $user->id;
            $existing_or_new_user->access_token = $user->token;
            $existing_or_new_user->refresh_token = $user->refreshToken;
            $existing_or_new_user->provider = 'apple';
            if (!$existing_or_new_user->exists) {
                $existing_or_new_user->name = 'Apple User';
                if ($user->name) {
                    $existing_or_new_user->name = $user->name;
                }
                $existing_or_new_user->email = $user->email;
                $existing_or_new_user->email_verified_at = date('Y-m-d H:m:s');
            }
            $existing_or_new_user->save();

            Auth::login($existing_or_new_user, true);
        }

        if (session('link') != null) {
            return redirect(session('link'));
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function mobileHandleProviderCallback($request, $provider)
    {
        $return_provider = '';
        $result = false;
        if ($provider) {
            $return_provider = $provider;
            $result = true;
        }
        return response()->json([
            'result' => $result,
            'provider' => $return_provider
        ]);
    }

    protected function credentials(Request $request)
    {
        if (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            return $request->only($this->username(), 'password');
        } else {
            return ['phone' => $request->get('email'), 'password' => $request->get('password')];
        }
    }

    public function authenticated()
    {
        if (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff') {
            return redirect()->route('admin.dashboard');
        } else {
            if (session('link') != null) {
                return redirect(session('link'));
            } else {
                return redirect()->route('dashboard');
            }
        }
    }

    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'account_deletion']);
    }

    public function showLoginForm()
    {
        return view('frontend.user_login');
    }

    public function logout(Request $request)
    {
        if (Auth::user() != null && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            $redirect_route = 'admin';
        } else {
            $redirect_route = 'home';
        }

        $this->guard()->logout();
        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect()->route($redirect_route);
    }

    public function account_deletion(Request $request)
    {
        $redirect_route = 'home';

        $auth_user = Auth::user();
        if ($auth_user) {

            $uploads = $auth_user->uploads;
            if ($uploads) {
                foreach ($uploads as $upload) {
                    if (file_exists(public_path() . '/' . $upload->file_name)) {
                        unlink(public_path() . '/' . $upload->file_name);
                        $upload->delete();
                    }
                }
            }

            $auth_user->member ?  $auth_user->member->delete() : '';
            Address::where('user_id', $auth_user->id)->delete();
            Education::where('user_id', $auth_user->id)->delete();
            Career::where('user_id', $auth_user->id)->delete();
            PhysicalAttribute::where('user_id', $auth_user->id)->delete();
            Hobby::where('user_id', $auth_user->id)->delete();
            Attitude::where('user_id', $auth_user->id)->delete();
            Recidency::where('user_id', $auth_user->id)->delete();
            Lifestyle::where('user_id', $auth_user->id)->delete();
            Astrology::where('user_id', $auth_user->id)->delete();
            Family::where('user_id', $auth_user->id)->delete();
            PartnerExpectation::where('user_id', $auth_user->id)->delete();
            SpiritualBackground::where('user_id', $auth_user->id)->delete();
            PackagePayment::where('user_id', $auth_user->id)->delete();
            HappyStory::where('user_id', $auth_user->id)->delete();
            Staff::where('user_id', $auth_user->id)->delete();
            Shortlist::where('user_id', $auth_user->id)->delete();
            IgnoredUser::where('user_id', $auth_user->id)->delete();
            ReportedUser::where('user_id', $auth_user->id)->delete();
            GalleryImage::where('user_id', $auth_user->id)->delete();
            ExpressInterest::where('user_id', $auth_user->id)->delete();
            ProfileMatch::where('user_id', $auth_user->id)->delete();
            ChatThread::where('sender_user_id', Auth::user()->id)->orWhere('receiver_user_id', Auth::user()->id)->delete();
            User::destroy(Auth::user()->id);

            auth()->guard()->logout();
            $request->session()->invalidate();

            return redirect()->route($redirect_route)->with('success', translate("Your account deletion successfully done."));
        }
        return redirect()->route($redirect_route)->with('error', translate("Something Went Wrong"));
    }
}
