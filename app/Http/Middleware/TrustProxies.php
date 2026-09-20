<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Only the local reverse proxy (nginx) is trusted. This was "*", which
     * trusted any X-Forwarded-For -- letting a caller that can reach PHP-FPM
     * directly spoof its IP and slip past the per-IP login limiter in
     * App\Providers\RouteServiceProvider.
     *
     * If the app ever moves behind a load balancer or CDN, add that hop's
     * address here -- with an untrusted proxy in front, $request->ip() returns
     * the proxy instead of the client and the rate limiter buckets everyone
     * together.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = ['127.0.0.1', '::1'];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
