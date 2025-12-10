<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

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

        Fortify::verifyEmailView(function (Request $request): View {
            return view('livewire.auth.verify-email');
        });

        Fortify::requestPasswordResetLinkView(function (Request $request): View {
            return view('livewire.auth.forgot-password');
        });

        Fortify::resetPasswordView(function (Request $request): View {
            return view('livewire.auth.reset-password');
        });

        Fortify::confirmPasswordView(function (Request $request): View {
            return view('livewire.auth.confirm-password');
        });

        // Register user creation logic
        Fortify::createUsersUsing(CreateNewUser::class);
    }
}
