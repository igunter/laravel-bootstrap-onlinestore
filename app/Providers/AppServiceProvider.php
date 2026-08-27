<?php

namespace App\Providers;

use App\Support\Cart;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::useBootstrapFive();

        Gate::define('access-admin', fn ($user) => $user->isAdmin());

        // A guest who added items to their session cart before logging in
        // shouldn't lose them — fold that session cart into their database
        // cart (see Cart::mergeSessionIntoDatabase()) the moment they log in.
        Event::listen(Login::class, fn () => app(Cart::class)->mergeSessionIntoDatabase());
    }
}
