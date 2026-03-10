<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', '/settings/appearance');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
