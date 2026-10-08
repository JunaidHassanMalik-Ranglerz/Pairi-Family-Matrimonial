<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContentPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

$adminPrefix = config('custom.admin_login_prefix', 'admin');

    Route::prefix($adminPrefix)->name('admin.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('store-login', [AuthenticatedSessionController::class, 'store'])->name('store-login');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forget-password', [PasswordResetLinkController::class, 'custom_forget_password'])->name('forget-password');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'custom_reset_password_page'])->name('password.reset');
    Route::post('/reset-password-store/{token}', [NewPasswordController::class, 'custom_reset_password_store'])->name('password.reset-store');

    Route::middleware(['auth:admin', 'admin.status'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::resource('users', UserController::class)->only(['index', 'show', 'destroy']);
        Route::post('users/verify-email/{user}', [UserController::class, 'verifyEmail'])->name('users.verify-email');
        Route::post('users/verify-phone/{user}', [UserController::class, 'verifyPhone'])->name('users.verify-phone');
        Route::post('users/toggle-status/{user}', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::controller(AdminProfileController::class)->group(function () {
            Route::get('edit-profile', 'edit_profile')->name('edit-profile');
            Route::put('profile-update', 'profile_update')->name('profile-update');
            Route::put('update-password', 'update_password')->name('update-password');
        });

        // Subscriptions
        Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class);

        // Notifications
        Route::get('notifications/users-search', [\App\Http\Controllers\Admin\NotificationController::class, 'searchUsers'])->name('notifications.users-search');
        Route::resource('notifications', \App\Http\Controllers\Admin\NotificationController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

        // Marriage Bureau Management
        Route::resource('marriage-bureaus', \App\Http\Controllers\Admin\MarriageBureauController::class);
        Route::post('marriage-bureaus/{marriage_bureau}/verify-subscription', [\App\Http\Controllers\Admin\MarriageBureauController::class, 'verifySubscription'])->name('marriage-bureaus.verify-subscription');
        Route::resource('marriage-bureau-subscriptions', \App\Http\Controllers\Admin\MarriageBureauSubscriptionPlanController::class);
        
        // Normal User Subscription Verification
        Route::post('users/{user}/verify-subscription', [UserController::class, 'verifySubscription'])->name('users.verify-subscription');
        
        // Settings
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'store'])->name('settings.store');

        // Contact messages
        Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::post('contact-messages/email', [ContactMessageController::class, 'updateEmail'])->name('contact-messages.update-email');
        Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        // FAQs and legal content
        Route::resource('faqs', FaqController::class)->except('show');
        Route::get('content/{type}', [ContentPageController::class, 'edit'])
            ->whereIn('type', ['terms-conditions', 'privacy-policy'])
            ->name('content.edit');
        Route::put('content/{type}', [ContentPageController::class, 'update'])
            ->whereIn('type', ['terms-conditions', 'privacy-policy'])
            ->name('content.update');

        // Legacy country URL now uses the common profile lookup section.
        Route::redirect(
            'countries',
            '/'.config('custom.admin_login_prefix', 'admin').'/lookups/countries'
        )->name('countries.index');

        // Profile lookup sections
        Route::prefix('lookups/{type}')->name('lookups.')->controller(LookupController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('data', 'data')->name('data');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->whereNumber('id')->name('edit');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
            Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

    });
});
