import i18n from '../i18n';

/**
 * Turn a raw status value ("On Trip", "Not Paid", "Wallet/Cash", "onride") into its
 * translation key ("on_trip", "not_paid", "wallet_cash", "onride").
 */
export function statusKey(value) {
    return String(value ?? '').trim().toLowerCase().replace(/[\s/-]+/g, '_');
}

/**
 * Translated label for a status value; falls back to the raw value when no key exists.
 */
export function translateStatus(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    const key = statusKey(value);
    const { te, t } = i18n.global;
    return te(key) || te(key, 'en') ? t(key) : String(value);
}

// Exposes `$st(status)` to every template.
export default {
    install(app) {
        app.config.globalProperties.$st = translateStatus;
    },
};
