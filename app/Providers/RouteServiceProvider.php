<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    

    public const HOME = '/';


    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiters();

        parent::boot();  
    }

    /**
     * Register the named rate limiters used by the authentication routes.
     *
     * Limits are configured in config/security.php. They are cache backed, so
     * CACHE_DRIVER must be something that persists between requests (file,
     * redis, memcached) -- the "array" driver silently disables them.
     *
     * @return void
     */
    protected function configureRateLimiters()
    {
        // Login: a tight limit per credential+IP pair, plus a looser one per IP
        // so credential stuffing spread across many accounts is still capped.
        RateLimiter::for('login', function (Request $request) {
            $config = config('security.rate_limiting.login');

            return [
                Limit::perMinutes(
                    max(1, (int) $config['decay_minutes']),
                    max(1, (int) $config['max_attempts'])
                )->by('login:' . $this->credentialKey($request) . '|' . $request->ip())
                    ->response($this->tooManyAttemptsResponse()),

                Limit::perMinutes(
                    max(1, (int) $config['decay_minutes']),
                    max(1, (int) $config['ip_max_attempts'])
                )->by('login-ip:' . $request->ip())
                    ->response($this->tooManyAttemptsResponse()),
            ];
        });

        $this->limiterFromConfig('register', 'register');
        $this->limiterFromConfig('otp', 'otp');
        $this->limiterFromConfig('password-update', 'password-update');
    }

    /**
     * Register a simple per-IP limiter from a config/security.php entry.
     *
     * @param string $name
     * @param string $configKey
     * @return void
     */
    protected function limiterFromConfig($name, $configKey)
    {
        RateLimiter::for($name, function (Request $request) use ($name, $configKey) {
            $config = config('security.rate_limiting.' . $configKey);

            return Limit::perMinutes(
                max(1, (int) $config['decay_minutes']),
                max(1, (int) $config['max_attempts'])
            )->by($name . ':' . $this->credentialKey($request) . '|' . $request->ip())
                ->response($this->tooManyAttemptsResponse());
        });
    }

    /**
     * The 429 payload returned once a limit is exhausted.
     *
     * Shaped like the app's other error responses (success/message/status_code)
     * and carries Retry-After so clients can tell the user when to come back.
     *
     * @return \Closure
     */
    protected function tooManyAttemptsResponse()
    {
        return function (Request $request, array $headers) {
            $seconds = (int) ($headers['Retry-After'] ?? 60);

            $minutes = max(1, (int) ceil($seconds / 60));

            $message = trans_choice(
                'Too many attempts. Please try again in 1 minute.|Too many attempts. Please try again in :count minutes.',
                $minutes,
                ['count' => $minutes]
            );

            return response()->json([
                'success'     => false,
                'message'     => $message,
                'status_code' => 429,
                'retry_after' => $seconds,
                'errors'      => ['email' => [$message]],
            ], 429, $headers);
        };
    }

    /**
     * A stable, non-reversible key for whichever identifier the request carries.
     *
     * Hashed so raw emails and mobile numbers never land in cache keys, and
     * lower-cased so "A@b.com" and "a@b.com" share a bucket.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function credentialKey(Request $request)
    {
        foreach (['email', 'mobile', 'username', 'social_unique_id', 'social_id', 'uuid'] as $field) {
            $value = $request->input($field);

            if (is_string($value) && $value !== '') {
                return sha1($field . ':' . Str::lower(trim($value)));
            }
        }

        return 'anonymous';
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

   
}
