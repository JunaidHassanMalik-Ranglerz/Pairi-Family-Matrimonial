<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShortlistController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => 200,
        'app' => 'Pairi Family API',
        'version' => '1.1',
        'screens' => [
            'auth' => [
                'POST /api/register',
                'POST /api/register/complete',
                'POST /api/verify-email-otp',
                'POST /api/resend-email-otp',
                'POST /api/login',
                'POST /api/social-login',
                'POST /api/forgot-password',
                'POST /api/verify-reset-otp',
                'POST /api/set-new-password',
                'POST /api/reset-password',
            ],
            'profile' => [
                'GET  /api/profile',
                'POST /api/profile/country',
                'POST /api/profile/basic-info',
                'POST /api/profile/education',
                'POST /api/profile/career',
                'POST /api/profile/physical',
                'POST /api/profile/faith',
                'POST /api/profile/photos',
                'POST /api/profile/update',
                'POST /api/profile/complete',
            ],
            'verification' => [
                'POST /api/verify-phone/send',
                'POST /api/verify-phone/resend',
                'POST /api/verify-phone/verify',
            ],
            'settings' => [
                'POST /api/change-password',
                'POST /api/logout',
            ],
            'notifications' => [
                'GET /api/notifications',
                'POST /api/notifications/{id}/read',
                'POST /api/notifications/read-all',
                'DELETE /api/notifications/clear-all',
            ],
            'lookup' => [
                'GET /api/countries',
                'GET /api/profile-options',
            ],
            'content' => [
                'GET /api/faqs',
                'GET /api/terms-conditions',
                'GET /api/privacy-policy',
            ],
        ],
    ]);
});

// Lookup (public)
Route::get('/countries', [LookupController::class, 'countries']);
Route::get('/profile-options', [LookupController::class, 'profileOptions']);
Route::get('/faqs', [ContentController::class, 'faqs']);
Route::get('/terms-conditions', [ContentController::class, 'termsConditions']);
Route::get('/privacy-policy', [ContentController::class, 'privacyPolicy']);
Route::get('/lookups/{type}', [LookupController::class, 'lookup']);
foreach (array_keys(config('profile_lookups', [])) as $lookupType) {
    if ($lookupType === 'countries') {
        continue;
    }

    Route::get('/'.$lookupType, [LookupController::class, 'lookup'])->defaults('type', $lookupType);
}
Route::get('/referrals/resolve/{referralCode}', [ReferralController::class, 'resolve'])
    ->where('referralCode', '[A-Z0-9]{8}');

// Public Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/otp-verify', [AuthController::class, 'registerComplete']);
Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
Route::post('/resend-email-otp', [AuthController::class, 'resendEmailOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/social-login', [SocialAuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/forgot-resend-otp', [AuthController::class, 'forgotResendEmailOtp']);
Route::post('/set-new-password', [AuthController::class, 'setNewPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/account/deactivate', [SettingsController::class, 'deactivate']);

// Protected
Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/verify-phone/send', [AuthController::class, 'sendPhoneOtp']);
    Route::post('/verify-phone/resend', [AuthController::class, 'resendPhoneOtp']);
    Route::post('/verify-phone/verify', [AuthController::class, 'verifyPhoneOtp']);

    Route::prefix('profile')->group(function () {
        Route::post('/country', [ProfileController::class, 'updateCountry']);
        Route::post('/location', [ProfileController::class, 'updateLocation']);
        Route::post('/basic-info', [ProfileController::class, 'updateBasicInfo']);
        Route::post('/education', [ProfileController::class, 'updateEducation']);
        Route::post('/career', [ProfileController::class, 'updateCareer']);
        Route::post('/physical', [ProfileController::class, 'updatePhysical']);
        Route::post('/faith', [ProfileController::class, 'updateFaith']);
        Route::post('/photos', [ProfileController::class, 'uploadPhotos']);
        Route::post('/update', [ProfileController::class, 'updateProfile']);
        Route::post('/complete', [ProfileController::class, 'completeProfile']);
        Route::post('/photo-visibility',[ProfileController::class, 'updatePhotoVisibility']);
    });
    Route::get('/profile/{user}/photo-gallery', [ProfileController::class, 'photoGallery']);
    Route::get('/profile-details/{user}', [MatchController::class, 'profileDetails']);

    Route::prefix('matches')->group(function () {
        Route::get('/home', [MatchController::class, 'home']);
        Route::get('/best-match', [MatchController::class, 'bestMatch']);
        Route::get('/search', [MatchController::class, 'search']);
        Route::get('/filter', [MatchController::class, 'filter']);
        Route::get('/{user}', [MatchController::class, 'show']);
    });

    Route::get('/shortlist', [ShortlistController::class, 'index']);
    Route::post('/shortlist/{user}/interest', [ShortlistController::class, 'sendInterest']);
    Route::post('/shortlist/{user}/pass', [ShortlistController::class, 'pass']);

    // Subscriptions and Referrals
    Route::get('/subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'index']);
    Route::get('/subscriptions/my-plan', [\App\Http\Controllers\Api\SubscriptionController::class, 'myPlan']);
    Route::get('/subscriptions/access', [\App\Http\Controllers\Api\SubscriptionController::class, 'access']);
    Route::post('/subscriptions/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/upload-payment', [\App\Http\Controllers\Api\SubscriptionController::class, 'uploadPayment']);
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
    Route::get('/referrals/link', [ReferralController::class, 'link']);
    Route::get('/referrals/stats', [ReferralController::class, 'stats']);
    Route::get('/referrals/history', [ReferralController::class, 'history']);
    Route::get('/referrals/rewards', [ReferralController::class, 'rewards']);
    Route::post('/referrals/redeem', [ReferralController::class, 'redeem']);

    // Settings & Account
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/settings/visibility', [SettingsController::class, 'updateVisibility']);
    Route::post('/account/delete', [SettingsController::class, 'deleteAccount']);

    // Notifications (DB-backed)
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/clear-all', [\App\Http\Controllers\Api\NotificationController::class, 'clearAll']);

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('subscriptions', \App\Http\Controllers\Api\Admin\SubscriptionController::class);
        Route::get('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
        Route::post('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'update']);
    });
});

Route::fallback(function (Request $request) {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found.',
        'path' => $request->path(),
        'method' => $request->method(),
    ], 404);
});
