<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyImageController;
use App\Http\Controllers\Api\RentalDocumentController;
use App\Http\Controllers\Api\RentalRequestController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─── Auth publique ────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login',    [AuthController::class, 'login'])->name('login');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
         ->middleware('throttle:5,1')
         ->name('forgot-password');

    Route::post('reset-password', [AuthController::class, 'resetPassword'])
         ->name('reset-password');

    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
         ->middleware('signed')
         ->name('verification.verify');
});

// ─── Biens publics ────────────────────────────────────────────────────────────
// IMPORTANT: /map MUST come before /{property} to avoid 'map' being parsed as an ID
Route::get('properties/map',        [PropertyController::class, 'map'])->name('api.properties.map');
Route::get('properties',            [PropertyController::class, 'index'])->name('api.properties.index');
Route::get('properties/{property}', [PropertyController::class, 'show'])->name('api.properties.show');

// ─── Auth protégée ────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('logout',       [AuthController::class, 'logout'])->name('logout');
        Route::get('me',            [AuthController::class, 'me'])->name('me');
        Route::post('email/resend', [AuthController::class, 'resendVerification'])
             ->middleware('throttle:1,1')
             ->name('verification.resend');
    });

    // ─── Profil utilisateur ───────────────────────────────────────────────────
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('profile',  [UserController::class, 'profile'])->name('profile');
        Route::put('profile',  [UserController::class, 'updateProfile'])->name('update-profile');
        Route::post('avatar',  [UserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::put('password', [UserController::class, 'changePassword'])->name('change-password');
    });

    // ─── Biens immobiliers (propriétaire) ─────────────────────────────────────
    Route::middleware(['verified.api', 'role:proprietaire'])->group(function () {
        Route::post('properties',                   [PropertyController::class, 'store'])->name('api.properties.store');
        Route::put('properties/{property}',         [PropertyController::class, 'update'])->name('api.properties.update');
        Route::post('properties/{property}/submit', [PropertyController::class, 'submit'])->name('api.properties.submit');

        // Images
        Route::post('properties/{property}/images',                   [PropertyImageController::class, 'store'])->name('api.property-images.store');
        Route::delete('properties/{property}/images/{propertyImage}', [PropertyImageController::class, 'destroy'])->name('api.property-images.destroy');
        Route::put('properties/{property}/images/reorder',            [PropertyImageController::class, 'reorder'])->name('api.property-images.reorder');
    });

    // Policy-driven: admin bypasses via before(); proprietaire owner-checked in policy
    Route::post('properties/{property}/archive', [PropertyController::class, 'archive'])->name('api.properties.archive');
    Route::delete('properties/{property}',       [PropertyController::class, 'destroy'])->name('api.properties.destroy');

    // ─── Favoris ─────────────────────────────────────────────────────────────
    Route::prefix('favorites')->name('api.favorites.')->group(function () {
        Route::get('/',                  [FavoriteController::class, 'index'])->name('index');
        Route::post('/{property}',       [FavoriteController::class, 'toggle'])->name('toggle');
        Route::get('/{property}/check',  [FavoriteController::class, 'check'])->name('check');
    });

    // ─── Recherches sauvegardées ──────────────────────────────────────────────
    Route::prefix('saved-searches')->name('api.saved-searches.')->group(function () {
        Route::get('/',                                     [SavedSearchController::class, 'index'])->name('index');
        Route::post('/',                                    [SavedSearchController::class, 'store'])->name('store');
        Route::get('/{savedSearch}',                        [SavedSearchController::class, 'show'])->name('show');
        Route::put('/{savedSearch}',                        [SavedSearchController::class, 'update'])->name('update');
        Route::delete('/{savedSearch}',                     [SavedSearchController::class, 'destroy'])->name('destroy');
        Route::patch('/{savedSearch}/toggle-notifications', [SavedSearchController::class, 'toggleNotifications'])->name('toggle-notifications');
        Route::get('/{savedSearch}/results',                [SavedSearchController::class, 'results'])->name('results');
    });

    // ─── Admin ────────────────────────────────────────────────────────────────
    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function () {
        Route::get('properties', [\App\Http\Controllers\Api\Admin\PropertyController::class, 'index'])
             ->name('properties.index');
        Route::post('properties/{property}/moderate', [\App\Http\Controllers\Api\Admin\PropertyController::class, 'moderate'])
             ->name('properties.moderate');
    });

    // ─── Demandes de location ─────────────────────────────────────────────────
    Route::prefix('rental-requests')->name('api.rental-requests.')->group(function () {
        Route::get('/', [RentalRequestController::class, 'index'])->name('index');

        Route::post('/properties/{property}', [RentalRequestController::class, 'store'])
             ->middleware('verified.api')
             ->name('store');

        Route::get('/{rentalRequest}',              [RentalRequestController::class, 'show'])->name('show');
        Route::post('/{rentalRequest}/decide',      [RentalRequestController::class, 'decide'])->name('decide');
        Route::post('/{rentalRequest}/cancel',      [RentalRequestController::class, 'cancel'])->name('cancel');
        Route::post('/{rentalRequest}/schedule-visit', [RentalRequestController::class, 'scheduleVisit'])->name('schedule-visit');
        Route::post('/{rentalRequest}/confirm-visit',  [RentalRequestController::class, 'confirmVisit'])->name('confirm-visit');

        Route::post('/{rentalRequest}/documents',                      [RentalDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/{rentalRequest}/documents/{document}',         [RentalDocumentController::class, 'destroy'])->name('documents.destroy');
    });

    // ─── Téléchargement sécurisé de document ─────────────────────────────────
    Route::get('/documents/{document}/download', [RentalDocumentController::class, 'download'])
         ->name('api.documents.download');

    // ─── Vérification de document (admin) ────────────────────────────────────
    Route::middleware('role:admin')
         ->post('/documents/{document}/verify', [RentalDocumentController::class, 'verifyDocument'])
         ->name('api.documents.verify');

    // ─── Badge global non lus ─────────────────────────────────────────────────
    Route::get('/messaging/unread-count',
               [ConversationController::class, 'unreadCount'])
         ->name('api.messaging.unread-count');

    // ─── Conversations ────────────────────────────────────────────────────────
    Route::prefix('conversations')->name('api.conversations.')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('index');

        Route::post('/properties/{property}', [ConversationController::class, 'store'])
             ->middleware('verified.api')
             ->name('store');

        Route::get('/{conversation}',          [ConversationController::class, 'show'])->name('show');
        Route::post('/{conversation}/read',    [ConversationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{conversation}/archive', [ConversationController::class, 'archive'])->name('archive');
        Route::post('/{conversation}/unarchive', [ConversationController::class, 'unarchive'])->name('unarchive');
    });

    // ─── Messages ─────────────────────────────────────────────────────────────
    Route::prefix('conversations/{conversation}/messages')->name('api.messages.')->group(function () {
        Route::get('/',      [MessageController::class, 'index'])->name('index');
        Route::post('/',     [MessageController::class, 'store'])->name('store');
        Route::get('/since', [MessageController::class, 'since'])->name('since');
        // ⚠️ PAS de PUT/{message} ni DELETE/{message} — messages immuables
    });
});
