<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\SportsApiController;
use App\Http\Controllers\Api\V1\UserAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile & External App REST API (Stateless /api/v1/*)
|--------------------------------------------------------------------------
*/

Route::get('/docs', fn () => view('api.docs'))->name('api.docs');

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    // Health & Metadata
    Route::get('/health', [SportsApiController::class, 'health']);
    Route::get('/settings', [SportsApiController::class, 'settings']);
    Route::get('/sports', [SportsApiController::class, 'sports']);

    // Matches & Live Scores (DB-First + UniScore)
    Route::get('/matches', [SportsApiController::class, 'matches']);
    Route::get('/matches/{sport}/{eventId}', [SportsApiController::class, 'matchDetail']);
    Route::get('/matches/{sport}/{eventId}/extended', [SportsApiController::class, 'matchExtended']);
    Route::get('/live/{sport}', [SportsApiController::class, 'live']);

    // Taxonomy & Competitions
    Route::get('/categories/{sport}', [SportsApiController::class, 'categories']);
    Route::get('/leagues/{sport}/top', [SportsApiController::class, 'topLeagues']);
    Route::get('/leagues/{sport}/category/{categoryId}', [SportsApiController::class, 'leaguesByCategory']);
    Route::get('/competitions/{sport}/{id}', [SportsApiController::class, 'competition']);
    Route::get('/teams/{sport}/{id}', [SportsApiController::class, 'team']);
    Route::get('/players/{sport}/{id}', [SportsApiController::class, 'player']);

    // Authentication (Bearer Token)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthApiController::class, 'register'])->middleware('throttle:20,1');
        Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:30,1');

        Route::middleware('api.auth')->group(function () {
            Route::get('/me', [AuthApiController::class, 'me']);
            Route::post('/logout', [AuthApiController::class, 'logout']);
        });
    });

    // User Favorites (Auth Required)
    Route::middleware('api.auth')->prefix('favorites')->group(function () {
        Route::get('/', [UserAppController::class, 'favorites']);
        Route::post('/', [UserAppController::class, 'addFavorite']);
        Route::post('/toggle', [UserAppController::class, 'toggleFavorite']);
        Route::delete('/{id}', [UserAppController::class, 'deleteFavorite']);
    });

    // Push Notification Device Tokens (Guest or Authenticated)
    Route::middleware('api.auth:optional')->prefix('devices')->group(function () {
        Route::post('/register', [UserAppController::class, 'registerDevice']);
        Route::delete('/{token}', [UserAppController::class, 'unregisterDevice']);
    });
});
