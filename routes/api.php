<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\PackageController;
use App\Http\Controllers\API\HappyStoryController;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\CustomPageController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\EducationController;
use App\Http\Controllers\API\CareerController;
use App\Http\Controllers\API\SupportTicketController;
use App\Http\Controllers\API\GalleryImageController;
use App\Http\Controllers\API\ProfileImageController;
use App\Http\Controllers\API\ProfileDropdownController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\InterestController;
use App\Http\Controllers\API\ShortlistController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\NotificationController;

use App\Http\Controllers\API\Payment\PaypalController;
use App\Http\Controllers\API\Payment\StripeController;
use App\Http\Controllers\API\Payment\PaystackController;
use App\Http\Controllers\API\Payment\PaytmController;
use App\Http\Controllers\API\Payment\RazorpayController;
use App\Http\Controllers\API\Payment\PaymentTypesController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('app_language')->group(function () {
    // Authentication
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('signin', [AuthController::class, 'signin']);
    Route::post('forgot/password', [AuthController::class, 'forgotPassword']);
    Route::post('verify/code', [AuthController::class, 'verifyCode']);
    Route::post('reset/password', [AuthController::class, 'resetPassword']);
    Route::post('social-login', [AuthController::class, 'socialLogin']);

    // Home Content
    Route::get('home/slider', [HomeController::class, 'home_slider']);
    Route::get('home/banner', [HomeController::class, 'home_banner']);
    Route::get('home/how-it-works', [HomeController::class, 'home_how_it_works']);
    Route::get('home/trusted-by-millions', [HomeController::class, 'home_trusted_by_millions']);
    Route::get('home/happy-stories', [HomeController::class, 'home_happy_stories']);
    Route::get('home/packages', [HomeController::class, 'home_packages']);
    Route::get('home/reviews', [HomeController::class, 'home_reviews']);
    Route::get('home/blogs', [HomeController::class, 'home_blogs']);
    Route::get('home/premium-members', [HomeController::class, 'home_premium_members']);
    Route::get('home/new-members', [HomeController::class, 'home_new_members']);

    Route::get('home', [HomeController::class, 'home']);
    Route::get('packages', [PackageController::class, 'active_packages']);
    Route::post('package-details', [PackageController::class, 'package_details']);
    Route::get('happy-stories', [HappyStoryController::class, 'happy_stories']);
    Route::post('story-details', [HappyStoryController::class, 'story_details']);
    Route::get('blogs', [BlogController::class, 'all_blogs']);
    Route::post('blog-details', [BlogController::class, 'blog_details']);
    Route::post('contact-us', [HomeController::class, 'contact_us']);

    // App Info
    Route::get('addon-check', [HomeController::class, 'addon_check']);
    Route::get('feature-check', [HomeController::class, 'feature_check']);
    Route::get('app-info', [HomeController::class, 'app_info']);
    Route::get('on-behalf', [ProfileDropdownController::class, 'onbehalf_list']);

    Route::get('static-page', [CustomPageController::class, 'custom_page']);

    // Payment Gateways
    // Paypal
    Route::get('paypal/payment/done', [PaypalController::class, 'getDone'])->name('api.paypal.done');
    Route::get('paypal/payment/cancel', [PaypalController::class, 'getCancel'])->name('api.paypal.cancel');

    // Stripe
    Route::any('stripe/success', [StripeController::class, 'success'])->name('api.stripe.success');
    Route::any('stripe/cancel', [StripeController::class, 'cancel'])->name('api.stripe.cancel');
    Route::any('stripe/create-checkout-session', [StripeController::class, 'create_checkout_session'])->name('api.stripe.get_token');

    // PayStack
    Route::get('paystack/payment/callback', [PaystackController::class, 'handleGatewayCallback']);

    // Paytm
    Route::post('paytm/callback', [PaytmController::class, 'callback'])->name('api.paytm.callback');

    // Razor Pay
    Route::any('razorpay/payment', [RazorpayController::class, 'payment'])->name('api.razorpay.payment');
    Route::post('razorpay/success', [RazorpayController::class, 'success'])->name('api.razorpay.success');

    // Auth routes
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
    Route::get('member-validate', [MemberController::class, 'member_validate']);

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Payment Gateways
        Route::get('payment-types', [PaymentTypesController::class, 'getList']);

        // Paypal
        Route::any('paypal/payment/pay', [PaypalController::class, 'pay'])->name('api.paypal.pay');

        // Stripe
        Route::any('stripe', [StripeController::class, 'stripe']);
        Route::any('stripe/payment/callback', [StripeController::class, 'callback'])->name('api.stripe.callback');

        // Paytm
        Route::get('paytm/index', [PaytmController::class, 'index']);

        // Razor Pay
        Route::any('pay-with-razorpay', [RazorpayController::class, 'payWithRazorpay'])->name('api.razorpay.payment');

        // Member routes
        Route::prefix('member')->group(function () {
            // Profile
            Route::get('public-profile/{id}', [ProfileController::class, 'public_profile']);
            Route::get('profile-settings', [ProfileController::class, 'profile_settings']);
            Route::get('introduction', [ProfileController::class, 'get_introduction']);
            Route::get('get-email', [ProfileController::class, 'get_email']);
            Route::post('introduction-update', [ProfileController::class, 'introduction_update']);
            Route::get('basic-info', [ProfileController::class, 'get_basic_info']);
            Route::post('basic-info/update', [ProfileController::class, 'basic_info_update']);
            Route::get('present/address', [ProfileController::class, 'present_address']);
            Route::get('permanent/address', [ProfileController::class, 'permanent_address']);
            Route::post('address/update', [ProfileController::class, 'address_update']);
            Route::post('education-status/update', [EducationController::class, 'education_status_update']);
            Route::post('career-status/update', [CareerController::class, 'career_status_update']);
            Route::get('physical-attributes', [ProfileController::class, 'physical_attributes']);
            Route::post('physical-attributes/update', [ProfileController::class, 'physical_attributes_update']);
            Route::get('language', [ProfileController::class, 'member_language']);
            Route::post('language/update', [ProfileController::class, 'member_language_update']);
            Route::get('hobbies-interests', [ProfileController::class, 'hobbies_interest']);
            Route::post('hobbies/update', [ProfileController::class, 'hobbies_interest_update']);
            Route::get('attitude-behavior', [ProfileController::class, 'attitude_behavior']);
            Route::post('attitude-behavior/update', [ProfileController::class, 'attitude_behavior_update']);
            Route::get('residency-info', [ProfileController::class, 'residency_info']);
            Route::post('residency-info/update', [ProfileController::class, 'residency_info_update']);
            Route::get('spiritual-background', [ProfileController::class, 'spiritual_background']);
            Route::post('spiritual-background/update', [ProfileController::class, 'spiritual_background_update']);
            Route::get('life-style', [ProfileController::class, 'life_style']);
            Route::post('life-style/update', [ProfileController::class, 'life_style_update']);
            Route::get('astronomic', [ProfileController::class, 'astronomic_info']);
            Route::post('astronomic/update', [ProfileController::class, 'astronomic_info_update']);
            Route::get('family-info', [ProfileController::class, 'family_info']);
            Route::post('family-info/update', [ProfileController::class, 'family_info_update']);
            Route::get('partner-expectation', [ProfileController::class, 'partner_expectation']);
            Route::post('partner-expectation/update', [ProfileController::class, 'partner_expectation_update']);
            Route::post('change/password', [ProfileController::class, 'password_update']);
            Route::post('contact-info/update', [ProfileController::class, 'contact_info_update']);
            Route::post('account/deactivate', [ProfileController::class, 'account_deactivation']);
            Route::post('account/delete', [ProfileController::class, 'account_delete']);
            Route::post('view-contact-store', [ProfileController::class, 'store_view_contact']);
            Route::get('matched-profile', [ProfileController::class, 'matched_profile']);

            // Support Ticket
            Route::get('my-tickets', [SupportTicketController::class, 'my_ticket']);
            Route::post('support-ticket/store', [SupportTicketController::class, 'store']);
            Route::get('support-ticket/categories', [SupportTicketController::class, 'support_ticket_categories']);
            Route::post('ticket-reply', [SupportTicketController::class, 'ticket_reply']);

            // Dashboard
            Route::get('dashboard', [HomeController::class, 'member_dashboard']);
            Route::get('home-with-login', [HomeController::class, 'home_with_login']);

            // Happy Story
            Route::get('check-happy-story', [HappyStoryController::class, 'happy_story_check']);
            Route::post('happy-story', [HappyStoryController::class, 'store']);

            // API Resources
            Route::apiResources([
                'gallery-image' => GalleryImageController::class,
                'career' => CareerController::class,
                'education' => EducationController::class,
                'support-ticket' => SupportTicketController::class,
            ]);

            // Gallery Image View Request
            Route::get('gallery-image-view-request', [GalleryImageController::class, 'image_view_request']);
            Route::post('gallery-image-view-request', [GalleryImageController::class, 'store_image_view_request']);
            Route::post('gallery-image-view-request/accept', [GalleryImageController::class, 'accept_image_view_request'])->name('gallery_image_view_request_accept');
            Route::post('gallery-image-view-request/reject', [GalleryImageController::class, 'reject_image_view_request'])->name('gallery_image_view_request_reject');

            // Profile Image View Request
            Route::get('profile-picture-view-request', [ProfileImageController::class, 'image_view_request']);
            Route::post('profile-picture-view-request', [ProfileImageController::class, 'store_image_view_request']);
            Route::post('profile-picture-view-request/accept', [ProfileImageController::class, 'accept_image_view_request'])->name('gallery_image_view_request_accept');
            Route::post('profile-picture-view-request/reject', [ProfileImageController::class, 'reject_image_view_request'])->name('gallery_image_view_request_reject');

            // Profile Dropdowns
            Route::get('maritial-status', [ProfileDropdownController::class, 'maritial_status']);
            Route::get('countries', [ProfileDropdownController::class, 'country_list']);
            Route::get('states/{id}', [ProfileDropdownController::class, 'state_list']);
            Route::get('cities/{id}', [ProfileDropdownController::class, 'city_list']);
            Route::get('languages', [ProfileDropdownController::class, 'language_list']);
            Route::get('religions', [ProfileDropdownController::class, 'religion_list']);
            Route::get('casts/{id}', [ProfileDropdownController::class, 'caste_list']);
            Route::get('sub-casts/{id}', [ProfileDropdownController::class, 'sub_caste_list']);
            Route::get('family-values', [ProfileDropdownController::class, 'family_value_list']);
            Route::get('profile-dropdown', [ProfileDropdownController::class, 'profile_dropdown']);

            // Chat
            Route::get('chat-list', [ChatController::class, 'chat_list']);
            Route::get('chat-view/{id}', [ChatController::class, 'chat_view']);
            Route::post('chat-reply', [ChatController::class, 'chat_reply']);
            Route::post('chat/old-messages', [ChatController::class, 'get_old_messages']);

            // Member
            Route::get('member-info/{id}', [MemberController::class, 'member_info']);
            Route::get('package-details', [MemberController::class, 'package_details']);
            Route::post('member-listing', [MemberController::class, 'member_listing']);
            Route::get('ignored-user-list', [MemberController::class, 'ignored_user_list']);
            Route::post('add-to-ignore-list', [MemberController::class, 'add_to_ignore_list']);
            Route::post('remove-from-ignored-list', [MemberController::class, 'remove_from_ignored_list']);
            Route::post('report-member', [MemberController::class, 'report_member']);

            // Package
            Route::post('package-purchase', [PackageController::class, 'package_purchase']);
            Route::get('package-purchase-history', [PackageController::class, 'package_purchase_history']);
            Route::post('package-purchase-history-invoice', [PackageController::class, 'package_purchase_history_invoice']);

            // Interest
            Route::get('my-interests', [InterestController::class, 'my_interests']);
            Route::post('express-interest', [InterestController::class, 'express_interest']);
            Route::get('interest-requests', [InterestController::class, 'interest_requests']);
            Route::post('interest-accept', [InterestController::class, 'accept_interest']);
            Route::post('interest-reject', [InterestController::class, 'reject_interest']);

            // Shortlist
            Route::get('my-shortlists', [ShortlistController::class, 'index']);
            Route::post('add-to-shortlist', [ShortlistController::class, 'store']);
            Route::post('remove-from-shortlist', [ShortlistController::class, 'remove']);

            // Wallet
            Route::get('my-wallet-balance', [WalletController::class, 'wallet_balance']);
            Route::get('wallet', [WalletController::class, 'index']);
            Route::post('wallet-recharge', [WalletController::class, 'recharge']);
            Route::get('wallet-withdraw-request-history', [WalletController::class, 'wallet_withdraw_request_history']);
            Route::post('wallet-withdraw-request-store', [WalletController::class, 'wallet_withdraw_request_store']);

            // Referral
            Route::get('referred-users', [ReferralController::class, 'index']);
            Route::get('referral-code', [ReferralController::class, 'referral_code']);
            Route::get('my-referral-earnings', [ReferralController::class, 'referral_earnings']);
            Route::get('referral-check', [ReferralController::class, 'referral_check']);

            // Notifications
            Route::get('notifications', [NotificationController::class, 'notifications']);
            Route::get('notification/{id}', [NotificationController::class, 'single_notification_read']);
            Route::get('mark-all-as-read', [NotificationController::class, 'mark_all_as_read']);

            // Happy Story
            // Route::get('happy-story', [HappyStoryController::class, 'happy_story']);
        });
    });
});
