<?php

declare(strict_types=1);

use App\Http\Controllers\EveController;
use App\Http\Controllers\UserCharacterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/eve', [EveController::class, 'show'])->name('login');
Route::get('/eve/callback', [EveController::class, 'store'])->name('auth.eve.callback');

Route::middleware(['auth'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::put('/auth/character/{character}', [UserCharacterController::class, 'update'])->name('auth.character.update');
    Route::delete('/auth/character/{character}', [UserCharacterController::class, 'destroy'])->name('auth.character.destroy');

    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/settings.php';
