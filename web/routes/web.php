<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SportsController;
use App\Http\Controllers\AdminAuthController;

// User Facing - 99% UniScore interface
Route::get('/', [SportsController::class, 'index'])->name('home');
Route::get('/competition/{sport}/{id}', [SportsController::class, 'competition'])->name('competition.show');

// AJAX API for interactive sidebar, right match panel & real-time live updates
Route::get('/api/leagues/{sport}/{categoryId}', [SportsController::class, 'apiLeaguesByCategory'])->name('api.leagues');
Route::get('/api/top-leagues/{sport}', [SportsController::class, 'apiTopLeagues'])->name('api.top_leagues');
Route::get('/api/match-detail/{sport}/{eventId}', [SportsController::class, 'apiMatchDetail'])->name('api.match_detail');
Route::get('/api/match-extended/{sport}/{eventId}', [SportsController::class, 'apiMatchExtended'])->name('api.match_extended');
Route::get('/api/live-updates/{sport}', [SportsController::class, 'apiLiveUpdates'])->name('api.live_updates');

// Admin Authentication
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::get('/admin/signin', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Protected Admin Panel (Auth Required)
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [SportsController::class, 'admin'])->name('admin.index');
    Route::post('/scraper/trigger', [SportsController::class, 'triggerScraper'])->name('admin.scraper.trigger');
    Route::get('/scraper/status', [SportsController::class, 'adminScraperStatus'])->name('admin.scraper.status');
    Route::post('/scraper/stop', [SportsController::class, 'adminScraperStop'])->name('admin.scraper.stop');
    Route::post('/matches/{id}/pin', [SportsController::class, 'togglePin'])->name('admin.matches.pin');
    Route::post('/matches', [SportsController::class, 'store'])->name('admin.matches.store');
    Route::put('/matches/{id}', [SportsController::class, 'update'])->name('admin.matches.update');
    Route::delete('/matches/{id}', [SportsController::class, 'destroy'])->name('admin.matches.destroy');

    // System & Tournament Priority Management
    Route::post('/system/sync', [SportsController::class, 'adminSyncDb'])->name('admin.system.sync');
    Route::post('/system/clear-cache', [SportsController::class, 'adminClearCache'])->name('admin.system.clear_cache');
    Route::post('/system/prune-old', [SportsController::class, 'adminPruneOld'])->name('admin.system.prune_old');
    Route::post('/tournaments/pin', [SportsController::class, 'adminToggleTournamentPin'])->name('admin.tournaments.pin');
});
