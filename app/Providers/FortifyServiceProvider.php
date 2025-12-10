<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register view responses
        Fortify::loginView(function (Request $request): View {
            return view('livewire.auth.login');
        });

        Fortify::registerView(function (Request $request): View {
            return view('livewire.auth.register');
        });

        Fortify::twoFactorChallengeView(function (Request $request): View {
            return view('livewire.auth.two-factor-challenge');
        });
    }
}

