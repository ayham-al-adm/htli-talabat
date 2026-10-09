import { createI18n } from 'vue-i18n';

const i18n = createI18n({
  legacy: false, // Use Composition API mode
  globalInjection: true, // Allow $t to be used globally
  locale: 'en', // Default locale
  fallbackLocale: 'en', // Fallback locale
  messages: {}, // Initial empty messages
});

// Translation files the web app uses, merged in this order into one message set.
const FILES = [
  'error_messages',
  'pages_names',
  'success_messages',
  'view_pages_1',
  'view_pages_2',
  'view_pages_3',
];

/**
 * Load locale messages dynamically.
 * @param {string} locale - The locale to load messages for.
 */
async function loadLocaleMessages(locale) {
  const parts = await Promise.all(FILES.map(async (file) => {
    try {
      const response = await fetch(`/lang/${locale}/${file}.json`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error(`Error loading "${file}" messages for locale "${locale}":`, error);
      return {};
    }
  }));

  i18n.global.setLocaleMessage(locale, Object.assign({}, ...parts));
}

/**
 * Initialize i18n with the specified locale. English is always loaded too, so a
 * key missing from the active language shows the English text, not the raw key.
 * @param {string} locale - The locale to initialize.
 */
async function initI18n(locale) {
  const loads = [loadLocaleMessages(locale)];
  if (locale !== 'en') {
    loads.push(loadLocaleMessages('en'));
  }
  await Promise.all(loads);
  i18n.global.locale.value = locale; // Set the locale
}

/**
 * Handle dynamic locale changes reactively.
 */
function setupLocaleReactivity() {
  const localeWatcher = new MutationObserver(async () => {
    const currentLocale = i18n.global.locale.value;
    if (!i18n.global.getLocaleMessage(currentLocale)) {
      await loadLocaleMessages(currentLocale);
    }
  });

  localeWatcher.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
}

setupLocaleReactivity(); // Ensure messages are updated when locale changes dynamically

// Translate outside component setup (e.g. SweetAlert options in Options API code).
const i18nT = (...args) => i18n.global.t(...args);

export default i18n;
export { loadLocaleMessages, initI18n, i18nT };
