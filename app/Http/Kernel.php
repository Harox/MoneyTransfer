<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Locale::class,
            \App\Http\Middleware\SessionCheckForAuth::class,

        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'                                => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic'                          => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings'                            => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can'                                 => \Illuminate\Auth\Middleware\Authorize::class,
        'throttle'                            => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        //custom
        'client_credentials'                  => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
        'permission'                          => \App\Http\Middleware\CheckPermission::class,
        'no_auth'                             => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'guest'                               => \App\Http\Middleware\Guest::class,
        'role'                                => \App\Http\Middleware\CheckRole::class,
        'locale'                              => \App\Http\Middleware\Locale::class,           //for language
        'twoFa'                               => \App\Http\Middleware\twoFa::class,            //for 2fa
        'google2fa'                           => \PragmaRX\Google2FALaravel\Middleware::class, //for google 2fa
        'check-user-suspended'                => \App\Http\Middleware\CheckUserSuspended::class,
        'check-user-inactive'                 => \App\Http\Middleware\CheckUserInactive::class,
        'check-authorization-token'           => \App\Http\Middleware\CheckAuthorizationToken::class,
        'check-enabled-currencies-preference' => \App\Http\Middleware\CheckEnabledCurrenciesPreference::class,
    ];
}
