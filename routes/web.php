<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\Admin\DiscordSettingController;
use App\Http\Controllers\Admin\StandingRequestController as AdminStandingRequestController;
use App\Http\Controllers\Admin\StandingsSourceController;
use App\Http\Controllers\CharacterSyncController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EveController;
use App\Http\Controllers\MainCharacterController;
use App\Http\Controllers\StandingRequestController;
use App\Http\Controllers\UserCharacterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/eve', [EveController::class, 'show'])->name('login');
Route::get('/eve/callback', [EveController::class, 'store'])->name('auth.eve.callback');

Route::middleware(['auth'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/standings/sync', [CharacterSyncController::class, 'sync'])->name('standings.sync');
    Route::put('/standings/{character}', [CharacterSyncController::class, 'update'])->name('standings.update');
    Route::post('/standings/{character}/request', [StandingRequestController::class, 'store'])->name('standing-requests.store');

    Route::put('/auth/character/{character}', [UserCharacterController::class, 'update'])->name('auth.character.update');
    Route::put('/auth/character/{character}/main', [MainCharacterController::class, 'update'])->name('auth.character.main');
    Route::delete('/auth/character/{character}', [UserCharacterController::class, 'destroy'])->name('auth.character.destroy');

    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    Route::middleware('can:standings.admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdministrationController::class, 'index'])->name('index');
        Route::put('standings', [StandingsSourceController::class, 'update'])->name('standings.update');
        Route::post('standings/sync', [StandingsSourceController::class, 'sync'])->name('standings.sync');
        Route::put('standing-requests/{standingRequest}', [AdminStandingRequestController::class, 'update'])->name('standing-requests.update');
        Route::put('discord-settings', [DiscordSettingController::class, 'update'])->name('discord-settings.update');
    });
});

require __DIR__.'/settings.php';
