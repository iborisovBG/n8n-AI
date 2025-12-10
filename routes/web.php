<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Ad Scripts Routes
    // TODO: Uncomment when Livewire components are created
    // Route::get('ad-scripts', \App\Livewire\Pages\AdScripts\IndexTasks::class)->name('ad-scripts.index');
    // Route::get('ad-scripts/create', \App\Livewire\Pages\AdScripts\CreateTask::class)->name('ad-scripts.create');
    // Route::get('ad-scripts/{id}', \App\Livewire\Pages\AdScripts\ShowTask::class)->name('ad-scripts.show');
});
