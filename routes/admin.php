<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportedUserController;
use App\Http\Controllers\MemberBulkAddController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\HappyStoryController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\CasteController;
use App\Http\Controllers\SubCasteController;
use App\Http\Controllers\MemberLanguageController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FamilyStatusController;
use App\Http\Controllers\FamilyValueController;
use App\Http\Controllers\OnBehalfController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\AizUploadController;


/*
  |--------------------------------------------------------------------------
  | Admin Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register admin routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

// Update Routes
Route::post('/update', [UpdateController::class, 'step0'])->name('update');
Route::get('/update/step1', [UpdateController::class, 'step1'])->name('update.step1');
Route::get('/update/step2', [UpdateController::class, 'step2'])->name('update.step2');

// Admin
Route::get('/admin/login', [HomeController::class, 'admin_login'])->name('admin.login');

// Optional: make /admin smart redirect to login or dashboard
Route::get('/admin', [HomeController::class, 'admin_login'])->name('admin');

// Admin dashboard (must be protected with 'auth' + 'admin' middleware)
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('dashboard', [HomeController::class, 'admin_dashboard'])->name('admin.dashboard');

    // Route::get('dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::resource('profile', ProfileController::class);

    // Contact Us page
    Route::resource('contact-us', ContactUsController::class);
    Route::get('contact-us/destroy/{id}', [ContactUsController::class, 'destroy'])->name('contact-us.delete');

    // Member Manage
    Route::resource('members', MemberController::class);
    Route::get('members/member_list/{id}', [MemberController::class, 'index'])->name('members.index');
    Route::post('members/approve', [MemberController::class, 'approve'])->name('members.approve');
    Route::post('members/block', [MemberController::class, 'block'])->name('members.block');
    Route::post('members/blocking_reason', [MemberController::class, 'blocking_reason'])->name('members.blocking_reason');
    Route::get('members/login/{id}', [MemberController::class, 'login'])->name('members.login');

    Route::get('deleted_members', [MemberController::class, 'deleted_members'])->name('deleted_members');
    Route::get('members/destroy/{id}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('restore_deleted_member/{id}', [MemberController::class, 'restore_deleted_member'])->name('restore_deleted_member');
    Route::get('members/permanently_delete/{id}', [MemberController::class, 'member_permanemtly_delete'])->name('members.permanently_delete');

    Route::get('reported-members/{id}', [ReportedUserController::class, 'reported_members'])->name('reported_members');
    Route::get('reported/destroy/{id}', [ReportedUserController::class, 'destroy'])->name('report_destrot.destroy');

    Route::get('member/unapproved-profile-pictures', [MemberController::class, 'unapproved_profile_pictures'])->name('unapproved_profile_pictures');
    Route::post('member/approve_profile_image', [MemberController::class, 'approve_profile_image'])->name('approve_profile_image');

    // Bulk member
    Route::get('member-bulk-add/index', [MemberBulkAddController::class, 'index'])->name('member_bulk_add.index');
    Route::get('download/on-behalf', [MemberBulkAddController::class, 'pdf_download_on_behalf'])->name('pdf.on_behalf');
    Route::get('download/package', [MemberBulkAddController::class, 'pdf_download_package'])->name('pdf.package');
    Route::post('bulk-member-upload', [MemberBulkAddController::class, 'bulk_upload'])->name('bulk_member_upload');

    // member's package manage
    Route::post('members/package_info', [MemberController::class, 'package_info'])->name('members.package_info');
    Route::post('members/get_package', [MemberController::class, 'get_package'])->name('members.get_package');
    Route::post('members/package_do_update/{id}', [MemberController::class, 'package_do_update'])->name('members.package_do_update');
    Route::get('package-payment-invoice/{id}', [PackagePaymentController::class, 'package_payment_invoice_admin'])->name('package_payment.invoice_admin');
    Route::post('members/wallet-balance-update', [MemberController::class, 'member_wallet_balance_update'])->name('member.wallet_balance_update');

    // Premium Packages
    Route::resource('packages', PackageController::class);
    Route::post('packages/update_package_activation_status', [PackageController::class, 'update_package_activation_status'])->name('packages.update_package_activation_status');
    Route::get('packages/destroy/{id}', [PackageController::class, 'destroy'])->name('packages.destroy');

    // package Payments
    Route::resource('package-payments', PackagePaymentController::class);
    Route::get('manual-payment-accept/{id}', [PackagePaymentController::class, 'manual_payment_accept'])->name('manual_payment_accept');

    // Wallet
    Route::get('wallet-transaction-history', [WalletController::class, 'wallet_transaction_history_admin'])->name('wallet_transaction_history_admin');
    Route::get('manual-wallet-recharge-requests', [WalletController::class, 'manual_wallet_recharge_requests'])->name('manual_wallet_recharge_requests');
    Route::get('wallet-payment-details/{id}', [WalletController::class, 'show'])->name('wallet_payment_details');
    Route::get('wallet-manual-payment-accept/{id}', [WalletController::class, 'wallet_manual_payment_accept'])->name('wallet_manual_payment_accept');

    Route::post('happy-story/update-story-status', [HappyStoryController::class, 'approval_status'])->name('happy_story_approval.status');

    //Blog Section
    Route::resource('blog-category', BlogCategoryController::class);
    Route::get('blog-category/destroy/{id}', [BlogCategoryController::class, 'destroy'])->name('blog-category.destroy');
    Route::resource('blog', BlogController::class);
    Route::get('blog/destroy/{id}', [BlogController::class, 'destroy'])->name('blog.destroy');
    Route::post('blog/change-status', [BlogController::class, 'change_status'])->name('blog.change-status');

    // Member profile attributes
    // religions
    Route::resource('religions', ReligionController::class);
    Route::get('religions/destroy/{id}', [ReligionController::class, 'destroy'])->name('religions.destroy');

    // Caste
    Route::resource('castes', CasteController::class);
    Route::get('castes/destroy/{id}', [CasteController::class, 'destroy'])->name('castes.destroy');

    // SubCaste
    Route::resource('sub-castes', SubCasteController::class);
    Route::get('sub-castes/destroy/{id}', [SubCasteController::class, 'destroy'])->name('sub-castes.destroy');

    // Member Language
    Route::resource('member-languages', MemberLanguageController::class);
    Route::get('member-language/destroy/{id}', [MemberLanguageController::class, 'destroy'])->name('member-languages.destroy');

    // Country
    Route::resource('countries', CountryController::class);
    Route::post('countries/status', [CountryController::class, 'updateStatus'])->name('countries.status');
    Route::get('countries/destroy/{id}', [CountryController::class, 'destroy'])->name('countries.destroy');

    // State
    Route::resource('states', StateController::class);
    Route::get('states/destroy/{id}', [StateController::class, 'destroy'])->name('states.destroy');

    // City
    Route::resource('cities', CityController::class);
    Route::get('cities/destroy/{id}', [CityController::class, 'destroy'])->name('cities.destroy');

    // Family Status
    Route::resource('family-status', FamilyStatusController::class);
    Route::get('family-status/destroy/{id}', [FamilyStatusController::class, 'destroy'])->name('family-status.destroy');

    // Family Value
    Route::resource('family-values', FamilyValueController::class);
    Route::get('family-values/destroy/{id}', [FamilyValueController::class, 'destroy'])->name('family-values.destroy');

    // On Behalf
    Route::resource('on-behalf', OnBehalfController::class);
    Route::get('on-behalf/destroy/{id}', [OnBehalfController::class, 'destroy'])->name('on-behalf.destroy');

    Route::resource('marital-statuses', MaritalStatusController::class);
    Route::get('marital-statuses/destroy/{id}', [MaritalStatusController::class, 'destroy'])->name('marital-statuses.destroy');

    // Email Templates
    Route::resource('email-templates', EmailTemplateController::class);
    Route::post('email-templates/update', [EmailTemplateController::class, 'update'])->name('email-templates.update');

    // Marketing
    Route::get('newsletter', [NewsletterController::class, 'index'])->name('newsletters.index');
    Route::post('newsletter/send', [NewsletterController::class, 'send'])->name('newsletters.send');
    Route::post('newsletter/test/smtp', [NewsletterController::class, 'testEmail'])->name('test.smtp');

    // Language
    Route::resource('languages', LanguageController::class);
    Route::post('languages/update_rtl_status', [LanguageController::class, 'update_rtl_status'])->name('languages.update_rtl_status');
    Route::post('languages/key_value_store', [LanguageController::class, 'key_value_store'])->name('languages.key_value_store');
    Route::get('languages/destroy/{id}', [LanguageController::class, 'destroy'])->name('languages.destroy');

    // Setting
    Route::resource('settings', SettingController::class);
    Route::post('settings/update', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/activation/update', [SettingController::class, 'updateActivationSettings'])->name('settings.activation.update');
    // Firebase Push Notification Setting
    Route::get('settings/firebase/fcm', [SettingController::class, 'fcm_settings'])->name('settings.fcm');
    Route::post('settings/firebase/fcm', [SettingController::class, 'fcm_settings_update'])->name('settings.fcm.update');

    Route::get('general-settings', [SettingController::class, 'general_settings'])->name('general_settings');
    Route::get('smtp-settings', [SettingController::class, 'smtp_settings'])->name('smtp_settings');

    Route::get('payment-methods-settings', [SettingController::class, 'payment_method_settings'])->name('payment_method_settings');
    Route::post('payment_method_update', [SettingController::class, 'payment_method_update'])->name('payment_method.update');

    Route::get('third-party-settings', [SettingController::class, 'third_party_settings'])->name('third_party_settings');
    Route::post('third-party-settings/update', [SettingController::class, 'third_party_settings_update'])->name('third_party_settings.update');

    Route::get('social-media-login-settings', [SettingController::class, 'social_media_login_settings'])->name('social_media_login');

    Route::get('member-profile-sections', [SettingController::class, 'member_profile_sections_configuration'])->name('member_profile_sections_configuration');

    // env Update
    Route::post('env_key_update', [SettingController::class, 'env_key_update'])->name('env_key_update.update');

    // Currency settings
    Route::resource('currencies', CurrencyController::class);
    Route::post('currency/update_currency_activation_status', [CurrencyController::class, 'update_currency_activation_status'])->name('currency.update_currency_activation_status');
    Route::get('currency/destroy/{id}', [CurrencyController::class, 'destroy'])->name('currency.destroy');

    // website setting
    Route::prefix('website')->group(function () {
        Route::get('header_settings', [SettingController::class, 'website_header_settings'])->name('website.header_settings');
        Route::get('footer_settings', [SettingController::class, 'website_footer_settings'])->name('website.footer_settings');
        Route::get('appearances', [SettingController::class, 'website_appearances'])->name('website.appearances');
        Route::resource('custom-pages', PageController::class);
        Route::get('custom-pages/edit/{id}', [PageController::class, 'edit'])->name('custom-pages.edit');
        Route::get('custom-pages/destroy/{id}', [PageController::class, 'destroy'])->name('custom-pages.destroy');
    });

    Route::resource('staffs', StaffController::class);
    Route::get('staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staffs.destroy');

    Route::resource('roles', RoleController::class);
    Route::get('roles/destroy/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // permission add
    Route::post('roles/add_permission', [RoleController::class, 'add_permission'])->name('roles.permission');

    Route::get('notifications', [NotificationController::class, 'index'])->name('admin.notifications');

    Route::get('system/update', [SettingController::class, 'system_update'])->name('system_update');
    Route::get('system/server-status', [SettingController::class, 'system_server'])->name('system_server');

    Route::resource('addons', AddonController::class);
    Route::post('addons/activation', [AddonController::class, 'activation'])->name('addons.activation');

    // uploaded files
    Route::any('uploaded-files/file-info', [AizUploadController::class, 'file_info'])->name('uploaded-files.info');
    Route::resource('uploaded-files', AizUploadController::class);
    Route::get('uploaded-files/destroy/{id}', [AizUploadController::class, 'destroy'])->name('uploaded-files.destroy');

    Route::get('cache-cache', [HomeController::class, 'clearCache'])->name('cache.clear');
});
