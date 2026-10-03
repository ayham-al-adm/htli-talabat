import { onBeforeUnmount, onMounted, ref } from 'vue';

const RECAPTCHA_SRC = 'https://www.google.com/recaptcha/api.js?render=explicit';

/**
 * Read an on/off flag the same way PHP's FILTER_VALIDATE_BOOLEAN does.
 *
 * The value reaches us through env() -> config() -> @json(), and what lands in
 * the browser depends on how it was written: the Admin > reCAPTCHA screen saves
 * "1"/"0" (strings), while a hand-edited .env with ENABLE_RECAPTCHA=true gives a
 * real boolean. The server parses all of those with FILTER_VALIDATE_BOOLEAN, so
 * anything stricter here lets the widget vanish while the server still demands a
 * token -- which locks every operator out of the panel.
 *
 * @param {*} value
 * @returns {boolean}
 */
function isTruthyFlag(value) {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        return ['1', 'true', 'on', 'yes'].includes(value.trim().toLowerCase());
    }

    return false;
}

let scriptPromise = null;

/**
 * Load the reCAPTCHA v2 script once per page, in explicit-render mode so each
 * login screen can mount (and reset) its own widget.
 *
 * @returns {Promise<object>} resolves with window.grecaptcha
 */
function loadRecaptcha() {
    if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
        return Promise.resolve(window.grecaptcha);
    }

    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise((resolve, reject) => {
        if (!document.querySelector('script[data-recaptcha-explicit]')) {
            const script = document.createElement('script');
            script.src = RECAPTCHA_SRC;
            script.async = true;
            script.defer = true;
            script.setAttribute('data-recaptcha-explicit', 'true');
            script.addEventListener('error', () => {
                scriptPromise = null;
                reject(new Error('Failed to load reCAPTCHA.'));
            });
            document.head.appendChild(script);
        }

        const startedAt = Date.now();
        const poll = setInterval(() => {
            if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
                clearInterval(poll);
                resolve(window.grecaptcha);
            } else if (Date.now() - startedAt > 15000) {
                clearInterval(poll);
                scriptPromise = null;
                reject(new Error('Failed to load reCAPTCHA.'));
            }
        }, 100);
    });

    return scriptPromise;
}

/**
 * Renders the reCAPTCHA challenge on an auth screen and exposes the token to
 * send along with the credentials.
 *
 * The widget only appears when the admin has switched reCAPTCHA on
 * (Admin > reCAPTCHA), which is the same flag the server middleware reads --
 * so the two can never disagree about whether a token is required.
 *
 * @returns {{enabled: boolean, siteKey: string, container: object, token: object, reset: Function, payload: Function}}
 */
export function useLoginCaptcha() {
    // Prefer window.authCaptcha, which app.blade.php resolves from the exact
    // config VerifyCaptcha enforces on. Fall back to the older globals so the
    // page still works if the blade has not been updated.
    const settings = window.authCaptcha || {
        enabled: window.enablerecaptcha,
        site_key: window.recaptchaKey,
    };

    const siteKey = settings.site_key || window.recaptchaKey || '';
    const enabled = isTruthyFlag(settings.enabled) && siteKey !== '';

    if (isTruthyFlag(settings.enabled) && siteKey === '') {
        // The server will reject every login with "Please complete the CAPTCHA
        // challenge" while we have no key to render a challenge with. Say so
        // loudly rather than showing a form that cannot succeed.
        console.error(
            'reCAPTCHA is enabled but no site key is configured. '
            + 'Set REACPTCHA_SITE_KEY (Admin > reCAPTCHA) or set ENABLE_RECAPTCHA=0.'
        );
    }

    const container = ref(null);
    const token = ref('');
    const widgetId = ref(null);

    onMounted(async () => {
        if (!enabled || !container.value) {
            return;
        }

        try {
            const grecaptcha = await loadRecaptcha();

            widgetId.value = grecaptcha.render(container.value, {
                sitekey: siteKey,
                callback: (response) => {
                    token.value = response;
                },
                'expired-callback': () => {
                    token.value = '';
                },
                'error-callback': () => {
                    token.value = '';
                },
            });
        } catch (error) {
            console.error(error);
        }
    });

    onBeforeUnmount(() => {
        token.value = '';
    });

    /**
     * Clear the solved challenge. Call it after every rejected login, otherwise
     * the next attempt replays a token Google has already burned.
     */
    const reset = () => {
        token.value = '';

        if (widgetId.value !== null && window.grecaptcha) {
            try {
                window.grecaptcha.reset(widgetId.value);
            } catch (error) {
                // Widget already gone; nothing to reset.
            }
        }
    };

    /**
     * The captcha fields to merge into the request body.
     *
     * @returns {object}
     */
    const payload = () => (enabled ? { 'g-recaptcha-response': token.value } : {});

    return { enabled, siteKey, container, token, reset, payload };
}

/**
 * Map an auth failure onto the form's error bag.
 *
 * Covers the three server responses an auth route can now produce:
 *   422 with errors.email / errors.password  - bad credentials, locked account
 *   422 with errors.captcha                  - captcha missing or rejected
 *   429                                      - rate limit exhausted
 *
 * @param {object} form An Inertia useForm instance.
 * @param {object} error The axios error.
 * @returns {string} The message shown to the user, or an empty string.
 */
export function applyAuthError(form, error) {
    const response = error?.response;

    if (!response) {
        console.error('Unexpected error:', error);

        return '';
    }

    if (response.status === 429) {
        const message = response.data?.message || 'Too many attempts. Please try again later.';

        form.setError('email', message);

        return message;
    }

    if (response.status === 422) {
        const errors = response.data?.errors || {};

        if (errors.captcha) {
            form.setError('email', errors.captcha[0]);

            return errors.captcha[0];
        }

        if (errors.email) {
            form.setError('email', errors.email[0]);
        }

        if (errors.password) {
            form.setError('password', errors.password[0]);
        }

        return errors.email?.[0] || errors.password?.[0] || response.data?.message || '';
    }

    console.error('Unexpected error:', error);

    return '';
}
