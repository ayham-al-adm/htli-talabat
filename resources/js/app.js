import './bootstrap';
import '../scss/config/default/app.scss';
import '@vueform/slider/themes/default.css';
import '../scss/mermaid.min.css';
import 'leaflet/dist/leaflet.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import BootstrapVueNext from 'bootstrap-vue-next';
import vClickOutside from "click-outside-vue3";
import VueApexCharts from "vue3-apexcharts";
import VueFeather from 'vue-feather';
import VueTheMask from 'vue-the-mask';
import { ref, onMounted } from "vue";
import AOS from 'aos';
import 'aos/dist/aos.css';

import store from "./state/store";
import axios from 'axios';
import i18n, { initI18n } from './i18n';
import { getStoredLocale, hasLocaleCookie, persistLocale, storeLocale } from './common/locale';
import CookieConsent from './Components/CookieConsent.vue';
import statusLabels from './common/status';

AOS.init({
    easing: 'ease-out-back',
    duration: 1000
});


async function bootstrap() {

    const serverLocale = window.appLocale || window.defaultLocale || 'en';
    const storedLocale = getStoredLocale();

    // Visitors who chose a language before the locale cookie existed: hand that
    // choice to the server once so server-rendered content uses it as well.
    if (storedLocale && storedLocale !== serverLocale && !hasLocaleCookie()) {
        let alreadySynced = true;
        try {
            alreadySynced = sessionStorage.getItem('localeSynced') === '1';
            sessionStorage.setItem('localeSynced', '1');
        } catch (e) {
            // no sessionStorage: skip the reload rather than risk a reload loop
        }
        if (!alreadySynced) {
            persistLocale(storedLocale);
            window.location.reload();
            return;
        }
    }

    // vue-i18n always follows the locale the server rendered this page with.
    const currentLocale = serverLocale;
    if (!hasLocaleCookie()) {
        persistLocale(currentLocale);
    } else {
        storeLocale(currentLocale);
    }
    axios.defaults.headers.common['X-Locale'] = currentLocale;

    const body = document.body;

    // Fetch permissions before initializing the app
    // await store.dispatch('fetchPermissions');
    await initI18n(currentLocale);

    createInertiaApp({
        title: title => title ? `${title} | Admin-Panel` : 'Admin-Panel',
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            return createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(store)
                .use(i18n)
                .use(statusLabels)
                .use(ZiggyVue)
                .use(BootstrapVueNext)
                .use(VueApexCharts)
                .use(VueTheMask)
                .use(vClickOutside)
                .component(VueFeather.type, VueFeather)
                .component('CookieConsent', CookieConsent)
                .mount(el);
                
        },
        progress: {
            color: '#4B5563',
        },
    });
}

bootstrap();
