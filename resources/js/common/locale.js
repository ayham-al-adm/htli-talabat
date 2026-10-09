// The server resolves each request's locale (App\Http\Middleware\SetLocale) from the
// `locale` cookie, so the cookie is the source of truth; localStorage mirrors it for
// components that read the current language.
const KEY = 'locale';

export function getStoredLocale() {
    try {
        return localStorage.getItem(KEY);
    } catch (e) {
        return null;
    }
}

export function hasLocaleCookie() {
    return document.cookie.split('; ').some((c) => c.startsWith(`${KEY}=`));
}

export function storeLocale(locale) {
    try {
        localStorage.setItem(KEY, locale);
    } catch (e) {
        // storage unavailable (private mode); the cookie still carries the choice
    }
}

export function persistLocale(locale) {
    const code = String(locale).toLowerCase();
    storeLocale(code);
    document.cookie = `${KEY}=${encodeURIComponent(code)}; path=/; max-age=31536000; SameSite=Lax`;
}

/**
 * Switch language and reload so server-rendered content follows the new locale.
 */
export function switchLocale(locale) {
    persistLocale(locale);
    window.location.reload();
}
