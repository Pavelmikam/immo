<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BienController;
use App\Http\Controllers\Api\TypeBienController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DemandeController;

/*
|--------------------------------------------------------------------------
| API Routes — ImmoConnect
|--------------------------------------------------------------------------
*/

// Authentification
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::get('profile',  [AuthController::class, 'profile']);
        Route::put('profile',  [AuthController::class, 'updateProfile']);
    });
});

// Biens immobiliers (publics en lecture, protégés en écriture)
Route::get('biens',          [BienController::class, 'index']);
Route::get('biens/{id}',     [BienController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('biens',         [BienController::class, 'store']);
    Route::put('biens/{id}',     [BienController::class, 'update']);
    Route::delete('biens/{id}',  [BienController::class, 'destroy']);
    Route::get('mes-biens',      [BienController::class, 'mesBiens']);
});

// Types de bien (référentiel public)
Route::get('types-biens',        [TypeBienController::class, 'index']);
Route::get('types-biens/{id}',   [TypeBienController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('types-biens',        [TypeBienController::class, 'store']);
    Route::put('types-biens/{id}',    [TypeBienController::class, 'update']);
    Route::delete('types-biens/{id}', [TypeBienController::class, 'destroy']);
});

// Agents immobiliers
Route::get('agents',        [AgentController::class, 'index']);
Route::get('agents/{id}',   [AgentController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('agents',         [AgentController::class, 'store']);
    Route::put('agents/{id}',    [AgentController::class, 'update']);
    Route::delete('agents/{id}', [AgentController::class, 'destroy']);
});

// Demandes de contact / renseignements
Route::post('demandes', [DemandeController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('demandes',        [DemandeController::class, 'index']);
    Route::get('demandes/{id}',   [DemandeController::class, 'show']);
    Route::put('demandes/{id}',   [DemandeController::class, 'update']);
    Route::delete('demandes/{id}',[DemandeController::class, 'destroy']);
});
