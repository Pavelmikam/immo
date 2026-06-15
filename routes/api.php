<?php

use App\Http\Controllers\Api\Admin\AdminLogController;
use App\Http\Controllers\Api\Admin\ExportController;
use App\Http\Controllers\Api\Admin\NeighborhoodController as AdminNeighborhoodController;
use App\Http\Controllers\Api\Admin\StatisticsController as AdminStatisticsController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\NeighborhoodController;
use App\Http\Controllers\Api\Admin\AmenityCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
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

// ─── Référentiel public ───────────────────────────────────────────────────────
Route::get('/reference/amenities',      [PropertyController::class, 'amenities'])->name('api.reference.amenities');
Route::get('/reference/property-types', [PropertyController::class, 'propertyTypes'])->name('api.reference.property-types');
Route::get('/reference/charges',        [PropertyController::class, 'charges'])->name('api.reference.charges');

// ─── Statistiques publiques ───────────────────────────────────────────────────
Route::get('/properties/popular', [StatisticsController::class, 'popularProperties'])
     ->name('api.properties.popular');

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
    // Brouillons / images : pas de vérif email (submit la requiert)
    Route::middleware(['role:proprietaire'])->group(function () {
        Route::get('my-properties',            [PropertyController::class, 'myProperties'])->name('api.properties.my');
        Route::post('properties',              [PropertyController::class, 'store'])->name('api.properties.store');
        Route::put('properties/{property}',    [PropertyController::class, 'update'])->name('api.properties.update');

        // Soumettre à la modération nécessite un email vérifié
        Route::post('properties/{property}/submit', [PropertyController::class, 'submit'])
             ->middleware('verified.api')
             ->name('api.properties.submit');

        // Images
        Route::post('properties/{property}/images',                          [PropertyImageController::class, 'store'])->name('api.property-images.store');
        Route::delete('properties/{property}/images/{propertyImage}',        [PropertyImageController::class, 'destroy'])->name('api.property-images.destroy');
        Route::put('properties/{property}/images/reorder',                   [PropertyImageController::class, 'reorder'])->name('api.property-images.reorder');
        Route::patch('properties/{property}/images/{propertyImage}/primary', [PropertyImageController::class, 'setPrimary'])->name('api.property-images.set-primary');
    });

    // Policy-driven: admin bypasses via before(); proprietaire owner-checked in policy
    Route::patch('properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('api.properties.update-status');
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

    // ─── Notifications ────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/',                          [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count',              [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/mark-all-read',            [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/{notificationId}/read',    [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::delete('/{notificationId}',       [NotificationController::class, 'destroy'])->name('destroy');
    });

    // ─── Préférences de notification ─────────────────────────────────────────
    Route::prefix('notification-preferences')->name('api.notification-preferences.')->group(function () {
        Route::get('/',  [NotificationPreferenceController::class, 'show'])->name('show');
        Route::put('/',  [NotificationPreferenceController::class, 'update'])->name('update');
    });

    // ─── Signalements utilisateurs ────────────────────────────────────────────
    Route::prefix('reports')->name('api.reports.')->group(function () {
        Route::post('/properties/{property}', [ReportController::class, 'storePropertyReport'])->name('property');
        Route::post('/messages/{message}',    [ReportController::class, 'storeMessageReport'])->name('message');
    });

    // ─── Administration ───────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',               [AdminUserController::class, 'index'])->name('index');
            Route::get('/{userId}',       [AdminUserController::class, 'show'])->name('show');
            Route::post('/{user}/suspend',  [AdminUserController::class, 'suspend'])->name('suspend');
            Route::post('/{user}/activate', [AdminUserController::class, 'activate'])->name('activate');
            Route::delete('/{user}',       [AdminUserController::class, 'destroy'])->name('destroy');
            Route::post('/{userId}/restore', [AdminUserController::class, 'restore'])->name('restore');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',              [AdminReportController::class, 'index'])->name('index');
            Route::get('/{report}',      [AdminReportController::class, 'show'])->name('show');
            Route::post('/{report}/handle', [AdminReportController::class, 'handle'])->name('handle');
        });

        Route::apiResource('amenity-categories', AmenityCategoryController::class)
             ->names('amenity-categories')
             ->except(['show']);

        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');

        Route::prefix('statistics')->name('statistics.')->group(function () {
            Route::get('/advanced',       [AdminStatisticsController::class, 'advanced'])->name('advanced');
            Route::get('/views-timeline', [AdminStatisticsController::class, 'viewsTimeline'])->name('views-timeline');
            Route::get('/top-properties', [AdminStatisticsController::class, 'topProperties'])->name('top-properties');
        });

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/properties',          [ExportController::class, 'exportProperties'])->name('properties');
            Route::get('/users',               [ExportController::class, 'exportUsers'])->name('users');
            Route::get('/rental-requests',     [ExportController::class, 'exportRentalRequests'])->name('rental-requests');
            Route::get('/activity-report',     [ExportController::class, 'exportActivityReport'])->name('activity-report');
            Route::get('/property-report/{property}', [ExportController::class, 'exportPropertyReport'])->name('property-report');
        });

        Route::prefix('neighborhood')->name('neighborhood.')->group(function () {
            Route::get('/reports',                                         [AdminNeighborhoodController::class, 'index'])->name('reports.index');
            Route::post('/reports/{neighborhoodReport}/flag',             [AdminNeighborhoodController::class, 'flag'])->name('reports.flag');
            Route::post('/reports/{neighborhoodReport}/validate',         [AdminNeighborhoodController::class, 'validate'])->name('reports.validate');
            Route::post('/recompute',                                      [AdminNeighborhoodController::class, 'recompute'])->name('recompute');
        });
    });
});

// ─── Score de quartier (public) ───────────────────────────────────────────────
Route::prefix('neighborhood')->name('api.neighborhood.')->group(function () {
    Route::get('/score',               [NeighborhoodController::class, 'score'])->name('score');
    Route::get('/history',             [NeighborhoodController::class, 'history'])->name('history');
    Route::get('/property/{property}', [NeighborhoodController::class, 'scoreForProperty'])->name('property-score');
});

// ─── Score de quartier (authentifié) ─────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active'])->prefix('neighborhood')->name('api.neighborhood.')->group(function () {
    Route::post('/report',     [NeighborhoodController::class, 'submit'])->name('submit');
    Route::get('/my-reports',  [NeighborhoodController::class, 'myReports'])->name('my-reports');
    Route::get('/my-profile',  [NeighborhoodController::class, 'myProfile'])->name('my-profile');
});

// ─── Statistiques utilisateurs ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active'])->prefix('statistics')->name('api.statistics.')->group(function () {
    Route::get('/property/{property}', [StatisticsController::class, 'propertyStats'])->name('property');
    Route::get('/owner-dashboard',     [StatisticsController::class, 'ownerDashboard'])->name('owner-dashboard');
    Route::get('/tenant-dashboard',    [StatisticsController::class, 'tenantDashboard'])->name('tenant-dashboard');
});
