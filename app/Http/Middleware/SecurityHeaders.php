<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the hardened HTTP response headers configured in
 * config/security.php > headers.
 *
 * Runs as global middleware so it also covers error pages and any response
 * produced by an exception, not just the routes that reached a controller.
 *
 * Note that assets Apache serves straight from public/ never enter PHP, so they
 * do not get these headers. That is fine for CSP, which only governs documents,
 * but if you want nosniff on static files too, mirror the simple headers in
 * public/.htaccess under an <IfModule mod_headers.c> block.
 */
class SecurityHeaders
{
    /**
     * The container key the per-request CSP nonce is bound to.
     */
    public const NONCE_KEY = 'csp.nonce';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Bound before the response is built so views can read it via csp_nonce().
        app()->instance(self::NONCE_KEY, $this->generateNonce());

        $response = $next($request);

        if (!$response instanceof Response || !config('security.headers.enabled', true)) {
            return $response;
        }

        $this->applySimpleHeaders($response);
        $this->applyHsts($request, $response);
        $this->applyContentSecurityPolicy($response);
        $this->removeFingerprintHeaders($response);

        return $response;
    }

    /**
     * The fixed-value headers.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    protected function applySimpleHeaders(Response $response)
    {
        $map = [
            'X-Content-Type-Options'             => config('security.headers.content_type_options'),
            'X-Frame-Options'                    => config('security.headers.frame_options'),
            'Referrer-Policy'                    => config('security.headers.referrer_policy'),
            'Permissions-Policy'                 => config('security.headers.permissions_policy'),
            'X-Permitted-Cross-Domain-Policies'  => config('security.headers.permitted_cross_domain_policies'),
            'Cross-Origin-Opener-Policy'         => config('security.headers.cross_origin_opener_policy'),
        ];

        foreach ($map as $header => $value) {
            if (!empty($value)) {
                $response->headers->set($header, $value);
            }
        }
    }

    /**
     * HSTS, only over HTTPS -- sending it over plain HTTP is meaningless and
     * would be ignored anyway, and this keeps local XAMPP installs usable.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    protected function applyHsts(Request $request, Response $response)
    {
        if (!config('security.headers.hsts.enabled') || !$request->isSecure()) {
            return;
        }

        $value = 'max-age=' . (int) config('security.headers.hsts.max_age', 31536000);

        if (config('security.headers.hsts.include_subdomains')) {
            $value .= '; includeSubDomains';
        }

        if (config('security.headers.hsts.preload')) {
            $value .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $value);
    }

    /**
     * Emit the CSP.
     *
     * While 'enforce' is off the baseline is enforced and the full policy rides
     * along as report-only, so violations show up in the console without
     * anything actually breaking. Once enforced, the two merge into one header.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    protected function applyContentSecurityPolicy(Response $response)
    {
        if (!config('security.headers.csp.enabled', true)) {
            return;
        }

        $baseline = (array) config('security.headers.csp.baseline', []);
        $full = $this->fullDirectives();

        if (config('security.headers.csp.enforce')) {
            $response->headers->set(
                'Content-Security-Policy',
                $this->compile($this->mergeDirectives($baseline, $full))
            );

            return;
        }

        if ($baseline) {
            $response->headers->set('Content-Security-Policy', $this->compile($baseline));
        }

        if ($full) {
            $response->headers->set('Content-Security-Policy-Report-Only', $this->compile($full));
        }
    }

    /**
     * The full policy, with the dev-server sources folded in when running locally.
     *
     * @return array
     */
    protected function fullDirectives()
    {
        $directives = (array) config('security.headers.csp.directives', []);

        if (app()->environment('local')) {
            $directives = $this->mergeDirectives(
                $directives,
                (array) config('security.headers.csp.local_directives', [])
            );
        }

        return $directives;
    }

    /**
     * Merge two directive sets, keeping each directive's sources unique.
     *
     * @param array $base
     * @param array $extra
     * @return array
     */
    protected function mergeDirectives(array $base, array $extra)
    {
        foreach ($extra as $directive => $sources) {
            $base[$directive] = array_values(array_unique(array_merge(
                (array) ($base[$directive] ?? []),
                (array) $sources
            )));
        }

        return $base;
    }

    /**
     * Render a directive set into a policy string.
     *
     * @param array $directives
     * @return string
     */
    protected function compile(array $directives)
    {
        $nonce = $this->nonce();

        $parts = [];

        foreach ($directives as $directive => $sources) {
            $sources = array_filter(array_map(function ($source) use ($nonce) {
                return $source === '{nonce}'
                    ? ($nonce ? "'nonce-{$nonce}'" : null)
                    : $source;
            }, (array) $sources));

            $parts[] = $sources
                ? $directive . ' ' . implode(' ', $sources)
                : $directive;
        }

        if ($reportUri = config('security.headers.csp.report_uri')) {
            $parts[] = 'report-uri ' . $reportUri;
        }

        return implode('; ', $parts);
    }

    /**
     * Drop the headers that advertise the stack to a scanner.
     *
     * PHP's X-Powered-By is emitted by the SAPI rather than the response object,
     * so it needs header_remove() as well.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    protected function removeFingerprintHeaders(Response $response)
    {
        foreach ((array) config('security.headers.remove_headers', []) as $header) {
            $response->headers->remove($header);

            if (!headers_sent()) {
                @header_remove($header);
            }
        }
    }

    /**
     * The nonce bound to this request.
     *
     * @return string|null
     */
    protected function nonce()
    {
        return app()->bound(self::NONCE_KEY) ? app(self::NONCE_KEY) : null;
    }

    /**
     * Generate a fresh per-request nonce.
     *
     * @return string
     */
    protected function generateNonce()
    {
        return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
}
