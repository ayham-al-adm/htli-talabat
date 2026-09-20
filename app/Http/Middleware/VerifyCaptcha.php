<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Verifies a Google reCAPTCHA token on the routes it is attached to.
 *
 * Keys are shared with the existing Admin > reCAPTCHA settings screen, so the
 * same ENABLE_RECAPTCHA switch that drives the landing page contact form drives
 * the auth routes.
 *
 * Failure modes, deliberately:
 *  - captcha disabled, or the channel is off  -> pass through
 *  - no secret configured at all              -> pass through + log an error
 *    (a blank secret is a misconfiguration, and failing closed there would lock
 *     every operator out of the panel with no way back in)
 *  - token missing, rejected, or unreachable  -> 422, the request never reaches
 *    the controller
 */
class VerifyCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $channel Either "web" or "api"; see config/security.php.
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $channel = 'web')
    {
        if (!$this->shouldVerify($channel)) {
            return $next($request);
        }

        $secret = config('security.captcha.secret');

        if (empty($secret)) {
            Log::error('CAPTCHA is enabled but no reCAPTCHA secret key is configured; skipping verification.');

            return $next($request);
        }

        $token = $this->tokenFrom($request);

        if (empty($token)) {
            $this->fail('Please complete the CAPTCHA challenge.');
        }

        $this->verify($secret, $token, $request->ip());

        return $next($request);
    }

    /**
     * Whether verification is switched on for this channel.
     *
     * @param string $channel
     * @return bool
     */
    protected function shouldVerify($channel)
    {
        if (!config('security.captcha.enabled')) {
            return false;
        }

        return (bool) config('security.captcha.channels.' . $channel, false);
    }

    /**
     * Pull the captcha token out of the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function tokenFrom(Request $request)
    {
        foreach ((array) config('security.captcha.input_names', []) as $name) {
            if (is_string($token = $request->input($name)) && $token !== '') {
                return $token;
            }
        }

        $header = $request->header('X-Captcha-Token');

        return is_string($header) && $header !== '' ? $header : null;
    }

    /**
     * Verify the token with Google, aborting the request when it does not check out.
     *
     * @param string $secret
     * @param string $token
     * @param string|null $ip
     * @return void
     */
    protected function verify($secret, $token, $ip)
    {
        try {
            $response = Http::asForm()
                ->timeout((int) config('security.captcha.timeout', 5))
                ->post(config('security.captcha.verify_url'), [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (\Throwable $e) {
            Log::error('CAPTCHA verification request failed: ' . $e->getMessage());

            $this->fail('Could not verify the CAPTCHA right now. Please try again.');
        }

        if (!$response->successful()) {
            Log::error('CAPTCHA verification returned HTTP ' . $response->status());

            $this->fail('Could not verify the CAPTCHA right now. Please try again.');
        }

        $result = (array) $response->json();

        if (empty($result['success'])) {
            $this->fail('CAPTCHA verification failed. Please try again.');
        }

        // reCAPTCHA v3 returns a score; v2 does not and passes on "success" alone.
        if (isset($result['score']) && (float) $result['score'] < (float) config('security.captcha.min_score', 0.5)) {
            $this->fail('CAPTCHA verification failed. Please try again.');
        }
    }

    /**
     * Abort with a 422 the SPA and the mobile clients both already understand.
     *
     * @param string $message
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function fail($message)
    {
        throw ValidationException::withMessages(['captcha' => [$message]]);
    }
}
