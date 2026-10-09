<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
            Request::setTrustedProxies(['*'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_HOST);
        }

        // Audit degli eventi di autenticazione.
        Event::listen(Login::class, fn (Login $e) => AuditLog::record('login', $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? AuditLog::record('logout', $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => AuditLog::record('login_failed', null, null, [
            'email' => $e->credentials['email'] ?? null,
        ]));
    }
}
