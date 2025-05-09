<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PackagePaymentController;
use App\Http\Controllers\GalleryImageController;
use App\Http\Controllers\ExpressInterestController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ShortlistController;
use App\Http\Controllers\IgnoredUserController;
use App\Http\Controllers\ViewProfilePictureController;
use App\Http\Controllers\ViewGalleryImageController;
use App\Http\Controllers\ReportedUserController;
use App\Http\Controllers\ViewContactController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\PhysicalAttributeController;
use App\Http\Controllers\HobbyController;
use App\Http\Controllers\AttitudeController;
use App\Http\Controllers\RecidencyController;
use App\Http\Controllers\LifestyleController;
use App\Http\Controllers\AstrologyController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\SpiritualBackgroundController;
use App\Http\Controllers\PartnerExpectationController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CasteController;
use App\Http\Controllers\SubCasteController;
use App\Http\Controllers\HappyStoryController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\AamarpayController;
use App\Http\Controllers\SslcommerzController;

use Illuminate\Support\Facades\Auth;

Auth::routes(['verify' => true]);


// Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
//     Route::get('dashboard', [HomeController::class, 'admin_dashboard'])->name('admin.dashboard');
// });


// Home Page
Route::get('/', [HomeController::class, 'index'])->name('index');
Route::get('/', [HomeController::class, 'index'])->name('home');
// Contact Us
Route::get('/contact-us/page', [ContactUsController::class, 'show_contact_us_page'])->name('contact_us');
Route::post('/contact-us', [ContactUsController::class, 'store'])->name('contact-us.store');

// Demo
Route::get('/demo/cron_1', [DemoController::class, 'cron_1']);
Route::get('/demo/cron_2', [DemoController::class, 'cron_2']);

// FCM Token
Route::post('/fcm-token', [HomeController::class, 'updateToken'])->name('fcmToken');

// CSRF refresh
Route::get('/refresh-csrf', fn() => csrf_token());

// Uploader
Route::post('/aiz-uploader', [AizUploadController::class, 'show_uploader']);
Route::post('/aiz-uploader/upload', [AizUploadController::class, 'upload']);
Route::get('/aiz-uploader/get_uploaded_files', [AizUploadController::class, 'get_uploaded_files']);
Route::delete('/aiz-uploader/destroy/{id}', [AizUploadController::class, 'destroy']);
Route::post('/aiz-uploader/get_file_by_ids', [AizUploadController::class, 'get_preview_files']);
Route::get('/aiz-uploader/download/{id}', [AizUploadController::class, 'attachment_download'])->name('download_attachment');
Route::get('/migrate/database', [AizUploadController::class, 'migrate_database']);

// Auth
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
Route::get('/verification-confirmation/{code}', [VerificationController::class, 'verification_confirmation'])->name('email.verification.confirmation');
Route::get('/email_change/callback', [HomeController::class, 'email_change_callback'])->name('email_change.callback');
Route::post('/password/reset/email/submit', [HomeController::class, 'reset_password_with_code'])->name('password.update');

// Social Login
Route::get('/users/login', [HomeController::class, 'login'])->name('user.login');
Route::get('/social-login/redirect/{provider}', [LoginController::class, 'redirectToProvider'])->name('social.login');
Route::get('/social-login/{provider}/callback', [LoginController::class, 'handleProviderCallback'])->name('social.callback');

Route::get('/users/blocked', [HomeController::class, 'user_account_blocked'])->name('user.blocked');

// Language and Currency
Route::post('/language', [LanguageController::class, 'changeLanguage'])->name('language.change');
Route::post('/currency', [CurrencyController::class, 'changeCurrency'])->name('currency.change');

// Packages and Blog
Route::get('/packages', [PackageController::class, 'select_package'])->name('packages');
Route::get('/happy-stories', [HomeController::class, 'happy_stories'])->name('happy_stories');
Route::get('/story_details/{id}', [HomeController::class, 'story_details'])->name('story_details');

// Blog
Route::get('/blog', [BlogController::class, 'all_blog'])->name('blog');
Route::get('/blog/{slug}', [BlogController::class, 'blog_details'])->name('blog.details');

// Routes for verified members
Route::middleware(['member', 'verified'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::any('/member-listing', [HomeController::class, 'member_listing'])->name('member.listing');

    // Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::post('/new-user-email', [HomeController::class, 'update_email'])->name('user.change.email');
    Route::post('/new-user-verification', [HomeController::class, 'new_verify'])->name('user.new.verify');

    Route::get('/profile-settings', [MemberController::class, 'profile_settings'])->name('profile_settings');

    Route::get('/package-payment-methods/{id}', [PackageController::class, 'package_payemnt_methods'])->name('package_payment_methods');
    Route::post('/package-payment', [PackagePaymentController::class, 'store'])->name('package.payment');
    Route::get('/package-purchase-history', [PackagePaymentController::class, 'package_purchase_history'])->name('package_purchase_history');

    Route::get('/member-profile/{id}', [HomeController::class, 'view_member_profile'])->name('member_profile');

    // Password Change
    Route::get('/members/change-password', [MemberController::class, 'change_password'])->name('member.change_password');
    Route::post('/member/password-update/{id}', [MemberController::class, 'password_update'])->name('member.password_update');

    // Gallery
    Route::resource('/gallery-image', GalleryImageController::class);
    Route::get('/gallery_image/destroy/{id}', [GalleryImageController::class, 'destroy'])->name('gallery_image.destroy');

    // Account deactivate/delete
    Route::post('/member/account-activation', [MemberController::class, 'update_account_deactivation_status'])->name('member.account_deactivation');
    Route::post('/member/account-delete', [MemberController::class, 'account_delete'])->name('member.account_delete');

    // Express Interest
    Route::resource('/express-interest', ExpressInterestController::class);
    Route::get('/my-interests', [ExpressInterestController::class, 'index'])->name('my_interests.index');
    Route::get('/interest/requests', [ExpressInterestController::class, 'interest_requests'])->name('interest_requests');
    Route::post('/interest/accept', [ExpressInterestController::class, 'accept_interest'])->name('accept_interest');
    Route::post('/interest/reject', [ExpressInterestController::class, 'reject_interest'])->name('reject_interest');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('all.messages');
    Route::get('/single-chat/{id}', [ChatController::class, 'chat_view'])->name('chat_view');
    Route::post('/chat-reply', [ChatController::class, 'chat_reply'])->name('chat.reply');
    Route::get('/chat/refresh/{id}', [ChatController::class, 'chat_refresh'])->name('chat_refresh');
    Route::post('/chat/old-messages', [ChatController::class, 'get_old_messages'])->name('get-old-message');

    // Shortlist
    Route::get('/my-shortlists', [ShortlistController::class, 'index'])->name('my_shortlists');
    Route::post('/member/add-to-shortlist', [ShortlistController::class, 'create'])->name('member.add_to_shortlist');
    Route::post('/member/remove-from-shortlist', [ShortlistController::class, 'remove'])->name('member.remove_from_shortlist');

    // Ignore
    Route::get('/ignored-list', [IgnoredUserController::class, 'index'])->name('my_ignored_list');
    Route::post('/member/add-to-ignore-list', [IgnoredUserController::class, 'add_to_ignore_list'])->name('member.add_to_ignore_list');
    Route::post('/member/remove-from-ignored-list', [IgnoredUserController::class, 'remove_from_ignored_list'])->name('member.remove_from_ignored_list');

    // View Profile Picture Requests
    Route::resource('/profile-picture-view-request', ViewProfilePictureController::class);
    Route::post('/profile-picture-view-request/accept', [ViewProfilePictureController::class, 'accept_request'])->name('profile_picture_view_request_accept');
    Route::post('/profile-picture-view-request/reject', [ViewProfilePictureController::class, 'reject_request'])->name('profile_picture_view_request_reject');

    // Gallery Image View Requests
    Route::resource('/gallery-image-view-request', ViewGalleryImageController::class);
    Route::post('/gallery-image-view-request/accept', [ViewGalleryImageController::class, 'accept_request'])->name('gallery_image_view_request_accept');
    Route::post('/gallery-image-view-request/reject', [ViewGalleryImageController::class, 'reject_request'])->name('gallery_image_view_request_reject');

    // Reporting
    Route::resource('reportusers', ReportedUserController::class);
    Route::resource('view_contacts', ViewContactController::class);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet-recharge-methods', [WalletController::class, 'wallet_recharge_methods'])->name('wallet.recharge_methods');
    Route::post('/recharge', [WalletController::class, 'recharge'])->name('wallet.recharge');
    Route::post('/user/remaining_package_value', [HomeController::class, 'user_remaining_package_value'])->name('user.remaining_package_value');

    Route::get('/member/notifications', [NotificationController::class, 'frontend_notify_listing'])->name('frontend.notifications');
});

// Routes for authenticated users
Route::middleware('auth')->group(function () {
    Route::post('/members/introduction_update/{id}', [MemberController::class, 'introduction_update'])->name('member.introduction.update');
    Route::post('/members/basic_info_update/{id}', [MemberController::class, 'basic_info_update'])->name('member.basic_info_update');
    Route::post('/members/language_info_update/{id}', [MemberController::class, 'language_info_update'])->name('member.language_info_update');

    Route::resource('/address', AddressController::class);
    Route::resource('/education', EducationController::class);
    Route::resource('/career', CareerController::class);
    Route::resource('/physical-attribute', PhysicalAttributeController::class);
    Route::resource('/hobbies', HobbyController::class);
    Route::resource('/attitudes', AttitudeController::class);
    Route::resource('/recidencies', RecidencyController::class);
    Route::resource('/lifestyles', LifestyleController::class);
    Route::resource('/astrologies', AstrologyController::class);
    Route::resource('/families', FamilyController::class);
    Route::resource('/spiritual_backgrounds', SpiritualBackgroundController::class);
    Route::resource('/partner_expectations', PartnerExpectationController::class);

    Route::post('/states/get_state_by_country', [StateController::class, 'get_state_by_country'])->name('states.get_state_by_country');
    Route::post('/cities/get_cities_by_state', [CityController::class, 'get_cities_by_state'])->name('cities.get_cities_by_state');
    Route::post('/castes/get_caste_by_religion', [CasteController::class, 'get_caste_by_religion'])->name('castes.get_caste_by_religion');
    Route::post('/sub-castes/get_sub_castes_by_religion', [SubCasteController::class, 'get_sub_castes_by_religion'])->name('sub_castes.get_sub_castes_by_religion');

    Route::get('/package-payment-invoice/{id}', [PackagePaymentController::class, 'package_payment_invoice'])->name('package_payment.invoice');
    Route::resource('/happy-story', HappyStoryController::class);

    Route::get('/notification-view/{id}', [NotificationController::class, 'notification_view'])->name('notification_view');
    Route::get('/notification/mark-all-as-read', [NotificationController::class, 'mark_all_as_read'])->name('notification.mark_all_as_read');
});

// Payment Gateways
Route::get('/paypal/payment/done', [PaypalController::class, 'getDone'])->name('payment.done');
Route::get('/paypal/payment/cancel', [PaypalController::class, 'getCancel'])->name('payment.cancel');

Route::post('/aamarpay/success', [AamarpayController::class, 'success'])->name('aamarpay.success');
Route::post('/aamarpay/fail', [AamarpayController::class, 'fail'])->name('aamarpay.fail');

Route::get('/sslcommerz/pay', [SslcommerzController::class, 'index']);
Route::any('/sslcommerz/success', [SslcommerzController::class, 'success'])->name('sslcommerz.success');
