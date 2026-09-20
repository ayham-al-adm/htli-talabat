<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limits applied by the named rate limiters registered in
    | App\Providers\RouteServiceProvider. Each limiter is applied through the
    | "throttle:<name>" middleware on the login / signup routes.
    |
    | NOTE: rate limiting is backed by the cache. The "array" cache driver does
    | not persist between requests, so CACHE_DRIVER must be file/redis/memcached
    | for these limits to have any effect.
    |
    */

    'rate_limiting' => [

        // Login attempts, counted per credential+IP pair.
        'login' => [
            'max_attempts'  => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_LOGIN_DECAY_MINUTES', 1),
            // A wider net per IP, to slow down credential stuffing across many accounts.
            'ip_max_attempts' => (int) env('SECURITY_LOGIN_IP_MAX_ATTEMPTS', 30),
        ],

        // Account creation.
        'register' => [
            'max_attempts'  => (int) env('SECURITY_REGISTER_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_REGISTER_DECAY_MINUTES', 60),
        ],

        // OTP generation / verification (mobile + email).
        'otp' => [
            'max_attempts'  => (int) env('SECURITY_OTP_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_OTP_DECAY_MINUTES', 10),
        ],

        // Password reset / update endpoints.
        'password-update' => [
            'max_attempts'  => (int) env('SECURITY_PASSWORD_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_PASSWORD_DECAY_MINUTES', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    |
    | After "max_attempts" failed credential checks inside "window_minutes",
    | the account is locked for "duration_minutes". The counters live on the
    | users table, so a lockout survives cache flushes and app restarts.
    |
    */

    'lockout' => [
        'enabled'          => (bool) env('SECURITY_LOCKOUT_ENABLED', true),
        'max_attempts'     => (int) env('SECURITY_LOCKOUT_MAX_ATTEMPTS', 5),
        'duration_minutes' => (int) env('SECURITY_LOCKOUT_DURATION_MINUTES', 15),
        // Failed attempts older than this are forgiven before the next attempt is counted.
        'window_minutes'   => (int) env('SECURITY_LOCKOUT_WINDOW_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Applied through App\Rules\StrongPassword on every endpoint that accepts a
    | new password (registration, password reset, admin-side password updates).
    |
    */

    'password' => [
        'min_length'         => (int) env('SECURITY_PASSWORD_MIN_LENGTH', 8),
        'max_length'         => (int) env('SECURITY_PASSWORD_MAX_LENGTH', 72),
        'require_mixed_case' => (bool) env('SECURITY_PASSWORD_MIXED_CASE', true),
        'require_numbers'    => (bool) env('SECURITY_PASSWORD_NUMBERS', true),
        'require_symbols'    => (bool) env('SECURITY_PASSWORD_SYMBOLS', true),
        // Checks the password against the haveibeenpwned breach corpus (k-anonymity,
        // the password itself never leaves the server). Needs outbound HTTP.
        'uncompromised'      => (bool) env('SECURITY_PASSWORD_UNCOMPROMISED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA
    |--------------------------------------------------------------------------
    |
    | Google reCAPTCHA verification, enforced by App\Http\Middleware\VerifyCaptcha
    | ("captcha" middleware alias). Keys are shared with the existing
    | Admin > reCAPTCHA settings screen (config/services.php).
    |
    | "channels" controls where the middleware actually enforces. Mobile clients
    | that cannot render a widget would be locked out, so the "api" channel is
    | off by default -- turn it on once the apps ship a captcha token.
    |
    */

    'captcha' => [
        'enabled'    => filter_var(env('ENABLE_RECAPTCHA', false), FILTER_VALIDATE_BOOLEAN),
        'site_key'   => env('REACPTCHA_SITE_KEY'),
        'secret'     => env('REACPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        'timeout'    => 5,

        'channels' => [
            'web' => filter_var(env('SECURITY_CAPTCHA_WEB', true), FILTER_VALIDATE_BOOLEAN),
            'api' => filter_var(env('SECURITY_CAPTCHA_API', false), FILTER_VALIDATE_BOOLEAN),
        ],

        // Minimum score for reCAPTCHA v3 responses. v2 responses carry no score
        // and are accepted on the "success" flag alone.
        'min_score' => (float) env('SECURITY_CAPTCHA_MIN_SCORE', 0.5),

        // Request fields the token may arrive in.
        'input_names' => [
            'g-recaptcha-response',
            'recaptchaResponse',
            'recaptcha_response',
            'captcha_token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers
    |--------------------------------------------------------------------------
    |
    | Emitted on every response by App\Http\Middleware\SecurityHeaders.
    |
    */

    'headers' => [

        'enabled' => filter_var(env('SECURITY_HEADERS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        // Stops browsers from MIME-sniffing a response away from its declared type.
        'content_type_options' => 'nosniff',

        // Legacy clickjacking defence. CSP frame-ancestors is the modern one;
        // both are sent so older browsers are covered too. Keep the two in step.
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),

        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

        // Blocks Adobe cross-domain policy files.
        'permitted_cross_domain_policies' => 'none',

        // "same-origin" would break any OAuth or Firebase popup flow, so popups
        // stay allowed while the panel keeps its own browsing context group.
        'cross_origin_opener_policy' => env('SECURITY_COOP', 'same-origin-allow-popups'),

        // Strip the framework/server fingerprint where PHP lets us.
        'remove_headers' => ['X-Powered-By', 'Server'],

        /*
        | Permissions-Policy
        |
        | NOTE: geolocation is NOT disabled. Open Dispatch and the customer
        | booking page both call navigator.geolocation.getCurrentPosition(), so
        | "geolocation=()" would silently break "use my current location" on
        | both screens. It is scoped to (self) instead: this origin may ask,
        | embedded third parties may not. Camera and microphone are unused
        | anywhere in the app and are fully off.
        |
        | Unknown feature names are ignored by browsers, so the list is safe to
        | carry across browser versions.
        */
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', implode(', ', [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=(self)',
            'battery=()',
            'camera=()',
            'display-capture=()',
            'encrypted-media=()',
            'fullscreen=(self)',
            'geolocation=(self)',
            'gyroscope=()',
            'idle-detection=()',
            'local-fonts=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'payment=(self)',
            'picture-in-picture=()',
            'publickey-credentials-get=(self)',
            'screen-wake-lock=()',
            'serial=()',
            'usb=()',
            'xr-spatial-tracking=()',
        ])),

        /*
        | HSTS -- only ever sent over HTTPS, so it cannot lock out a plain-HTTP
        | local XAMPP install. Do not enable "preload" until you are certain
        | every subdomain will serve HTTPS forever; it is very hard to undo.
        */
        'hsts' => [
            'enabled'            => filter_var(env('SECURITY_HSTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'max_age'            => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => filter_var(env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true), FILTER_VALIDATE_BOOLEAN),
            'preload'            => filter_var(env('SECURITY_HSTS_PRELOAD', false), FILTER_VALIDATE_BOOLEAN),
        ],

        /*
        |----------------------------------------------------------------------
        | Content Security Policy
        |----------------------------------------------------------------------
        |
        | Shipped in two parts, because a single strict policy would take this
        | panel down on the first page load:
        |
        |  'baseline'   Always ENFORCED. Only directives that cannot break this
        |               app, and they shut down the highest-value attacks:
        |               plugin injection, base-tag hijacking, clickjacking.
        |
        |  'directives' The full policy. Sent as Content-Security-Policy-Report-Only
        |               until SECURITY_CSP_ENFORCE=true, so you can watch the
        |               browser console (or a report endpoint) for a week and fix
        |               what breaks before it starts blocking. Flip the flag and
        |               it merges with the baseline into one enforced policy.
        |
        | The host lists below are the CDNs and payment gateways this codebase
        | actually loads. Auditing them down is the next win: every entry is a
        | third party that can run script in your admin panel.
        |
        | "{nonce}" is replaced per request with a nonce source expression;
        | csp_nonce() returns the matching value for a script nonce attribute.
        | It stays inert while 'unsafe-inline' is present -- see script-src.
        |
        */
        'csp' => [

            'enabled' => filter_var(env('SECURITY_CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

            'enforce' => filter_var(env('SECURITY_CSP_ENFORCE', false), FILTER_VALIDATE_BOOLEAN),

            // Optional collector for violation reports.
            'report_uri' => env('SECURITY_CSP_REPORT_URI'),

            'baseline' => [
                'object-src'      => ["'none'"],
                'base-uri'        => ["'self'"],
                'frame-ancestors' => ["'none'"],
            ],

            'directives' => [

                'default-src' => ["'self'"],

                /*
                | 'unsafe-inline' is here because app.blade.php and the payment
                | and report blades carry inline script blocks. Removing it means
                | putting a nonce on every one of them first -- a browser ignores
                | 'unsafe-inline' as soon as a nonce source is present, so it is
                | all-or-nothing.
                |
                | 'unsafe-eval' is kept for the Vite dev server and any bundled
                | library that compiles templates at runtime. Report-only mode
                | will tell you whether production actually needs it.
                */
                'script-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    "'unsafe-eval'",
                    '{nonce}',
                    'https://cdn.jsdelivr.net',
                    'https://cdnjs.cloudflare.com',
                    'https://code.jquery.com',
                    'https://unpkg.com',
                    'https://maxcdn.bootstrapcdn.com',
                    'https://www.gstatic.com',
                    'https://www.google.com',
                    'https://maps.googleapis.com',
                    // Payment gateway checkout scripts.
                    'https://checkout.razorpay.com',
                    'https://checkout.flutterwave.com',
                    'https://js.paystack.co',
                    'https://sdk.mercadopago.com',
                    'https://cdn.payphonetodoesposible.com',
                    'https://khalti.s3.ap-south-1.amazonaws.com',
                    'https://*.myfatoorah.com',
                    'https://secure.sadadqa.com',
                ],

                // Vue, Bootstrap-Vue and the theme all set style attributes and
                // inject stylesheets at runtime, so 'unsafe-inline' is
                // unavoidable here without a rewrite. Style injection is a far
                // smaller risk than script injection.
                'style-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    'https://cdn.jsdelivr.net',
                    'https://cdnjs.cloudflare.com',
                    'https://fonts.googleapis.com',
                    'https://maxcdn.bootstrapcdn.com',
                    'https://cdn.payphonetodoesposible.com',
                ],

                'font-src' => [
                    "'self'",
                    'data:',
                    'https://fonts.gstatic.com',
                    'https://cdn.jsdelivr.net',
                    'https://cdnjs.cloudflare.com',
                ],

                // Images come from map tiles, country flags, S3 buckets and
                // operator uploads. Broad by design; images cannot execute.
                'img-src' => ["'self'", 'data:', 'blob:', 'https:'],

                'media-src' => ["'self'", 'data:', 'blob:'],

                'connect-src' => [
                    "'self'",
                    'https://maps.googleapis.com',
                    'https://places.googleapis.com',
                    'https://routes.googleapis.com',
                    'https://nominatim.openstreetmap.org',
                    'https://router.project-osrm.org',
                    'https://www.google.com',
                    // Firebase realtime database + phone auth.
                    'https://*.firebaseio.com',
                    'wss://*.firebaseio.com',
                    'https://*.googleapis.com',
                ],

                // reCAPTCHA renders in an iframe; the rest are hosted checkouts.
                // data:/blob: are for MultiUpload.vue, which previews an
                // uploaded PDF by framing the FileReader data URL directly.
                'frame-src' => [
                    "'self'",
                    'data:',
                    'blob:',
                    'https://www.google.com',
                    'https://*.myfatoorah.com',
                    'https://secure.sadadqa.com',
                    'https://checkout.razorpay.com',
                    'https://checkout.flutterwave.com',
                    'https://sdk.mercadopago.com',
                ],

                'worker-src' => ["'self'", 'blob:'],

                // CCAvenue and Sadad are POSTed to directly from a form, so a
                // bare 'self' here would break those two checkouts.
                'form-action' => [
                    "'self'",
                    'https://secure.ccavenue.com',
                    'https://secure.sadadqa.com',
                ],

                'object-src'      => ["'none'"],
                'base-uri'        => ["'self'"],
                'frame-ancestors' => ["'none'"],
            ],

            // Extra sources folded in only when running locally, for the Vite
            // dev server and its hot-reload websocket.
            'local_directives' => [
                'script-src'  => ['http://localhost:5173', 'http://127.0.0.1:5173'],
                'style-src'   => ['http://localhost:5173', 'http://127.0.0.1:5173'],
                'connect-src' => [
                    'http://localhost:5173',
                    'http://127.0.0.1:5173',
                    'ws://localhost:5173',
                    'ws://127.0.0.1:5173',
                ],
            ],
        ],
    ],
];
