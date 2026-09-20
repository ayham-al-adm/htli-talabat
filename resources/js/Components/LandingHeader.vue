<script>
import { CountTo } from "vue3-count-to";
import { Link, router } from '@inertiajs/vue3';
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/autoplay";
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import logo from "@/assets/images/logo.png";

export default {

    data() {
        return {
            Autoplay, Navigation, Pagination,
            currentTab: '',
            activeSection: 'hero', // Track active section for scroll-based navigation
            isCollapsed: false, // Track the collapse state
            header: window.headers, // Access global headers data
            headers: this.$page.props.landingHeader,
            enable_web_booking: window.headers[0].enable_web_booking,
            locales: this.$page.props.locales,
            selectedLocale: this.$page.props.landingHeader.locale,
            selectedDirection: this.$page.props.landingHeader.direction,
            user_login: window.headers[0].userlogin,
        };
    },
    components: {
        Swiper,
        SwiperSlide,
        CountTo,
        Link,
    },
    methods: {
        toggleMenu() {
            this.isCollapsed = !this.isCollapsed; // Toggle collapse state
        },

        smoothScroll(targetId) {
            const element = document.getElementById(targetId);
            if (element) {
                // Element exists on current page, scroll to it
                const navbarHeight = 80; // Height of fixed navbar
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - navbarHeight;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Set active section immediately
                this.activeSection = targetId;
            } else {
                // Element doesn't exist, navigate to main landing page with hash using Inertia
                const currentLocale = this.selectedLocale || 'en';
                router.visit(`/?locale=${currentLocale}#${targetId}`, {
                    preserveScroll: false,
                    onFinish: () => {
                        // Scroll to target after navigation completes
                        setTimeout(() => {
                            const targetElement = document.getElementById(targetId);
                            if (targetElement) {
                                const navbarHeight = 80;
                                const elementPosition = targetElement.getBoundingClientRect().top;
                                const offsetPosition = elementPosition + window.pageYOffset - navbarHeight;

                                window.scrollTo({
                                    top: offsetPosition,
                                    behavior: 'smooth'
                                });

                                // Set active section after scroll
                                this.activeSection = targetId;
                            }
                        }, 100);
                    }
                });
            }
            // Close mobile menu after click
            this.isCollapsed = false;
        },

        changeLocale(event) {
            const localeId = event.target.value;
            this.selectedLocale = this.locales[localeId];
            localStorage.setItem('locale', this.selectedLocale.toLowerCase());
            window.location.href = `?locale=${this.selectedLocale.toLowerCase()}`;
        },

        changeLocale(locale) {
            this.selectedLocale = locale;
            localStorage.setItem('locale', this.selectedLocale.toLowerCase());
            window.location.href = `?locale=${this.selectedLocale.toLowerCase()}`;
        },

        headerLogoUrl() {
            return logo;
        },

        // headerLogoUrl() {
        //     return this.header.length > 0 ? this.header[1].header_logo_url : '';
        // },

        // Method to handle scroll events and update active section
        handleScroll() {
            const sections = ['hero', 'features', 'services', 'food-section', 'app-download', 'service-area'];
            const navbarHeight = 80;
            const scrollPosition = window.pageYOffset + navbarHeight + 100; // Add some offset for better UX

            for (const sectionId of sections) {
                const section = document.getElementById(sectionId);
                if (section) {
                    const sectionTop = section.offsetTop;
                    const sectionBottom = sectionTop + section.offsetHeight;

                    if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        this.activeSection = sectionId;
                        break;
                    }
                }
            }
        },

        // Method to set up scroll event listener
        setupScrollListener() {
            window.addEventListener('scroll', this.handleScroll);
            // Initial check
            this.handleScroll();
        },

        // Method to remove scroll event listener
        removeScrollListener() {
            window.removeEventListener('scroll', this.handleScroll);
        },

    },

    mounted(){
        const body = document.body;
        if( this.selectedDirection === 'rtl'){
            localStorage.setItem('directiontoggleValue', true);
            body.classList.add('rtl');
             body.classList.remove('ltr');
        }
        else{
            localStorage.setItem('directiontoggleValue', false);
            body.classList.add('ltr');
            body.classList.remove('rtl');
        }

        // Setup scroll listener for dynamic navigation activation
        this.setupScrollListener();
    },
    created() {
        // Set initial active tab based on current route
        this.currentTab = window.location.pathname;
    },
    beforeUnmount() {
        // Clean up scroll event listener
        this.removeScrollListener();
    },
};
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-landing fixed-top" style="background-color: var(--landing_header_bg);" id="navbar">
        <BContainer>
            <Link :href="`/?locale=${selectedLocale}`" class="navbar-brand">
                <img :src="headerLogoUrl()" class="card-logo card-logo-dark" alt="logo light" width="50">
            </Link>
            <BButton variant="link" class="navbar-toggler py-0 fs-20 text-body" @click="toggleMenu()">
                <i class="mdi mdi-menu"></i>
            </BButton>

            <BCollapse class="navbar-collapse" id="navbarSupportedContent" v-model="isCollapsed">
                <ul class="navbar-nav mx-auto mt-2 mt-lg-0" id="navbar-example">
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('hero')" :class="{ 'active': activeSection === 'hero' }">{{ $t('landing_page.home') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('features')" :class="{ 'active': activeSection === 'features' }">{{ $t('landing_page.features') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('services')" :class="{ 'active': activeSection === 'services' }">{{ $t('landing_page.why_htli') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('food-section')" :class="{ 'active': activeSection === 'food-section' }">{{ $t('landing_page.food_delivery') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('app-download')" :class="{ 'active': activeSection === 'app-download' }">{{ $t('landing_page.download') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="smoothScroll('service-area')" :class="{ 'active': activeSection === 'service-area' }">{{ $t('landing_page.service_locations') }}</a>
                    </li>
                </ul>
                <div class="flex-shrink-0 me-5 selectLanguages">
                    <!-- <select v-model="selectedLocale" @change="changeLocale" class="form-select form-select-sm" aria-label=".form-select-sm example">
                        <option v-for="(locale, id) in locales" :key="id" :value="id">{{ locale }}</option>
                    </select> -->
                    <!-- <BDropdown class="dropdown" variant="ghost-secondary" dropstart
                        :offset="{ alignmentAxis: 55, crossAxis: 15, mainAxis: -50 }"
                        toggle-class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle arrow-none"
                        menu-class="dropdown-menu-end">
                        <template #button-content>
                            <div class="bg-success-subtle" style="height: 38px; width: 38px; border-radius: 45px;">
                            <i class="ri-translate fs-22 text-success"></i>
                            </div>
                        </template>
                        <BLink href="javascript:void(0);" class="dropdown-item notify-item language py-2"
                            v-for="(locale, id) in locales" :data-lang="locale"
                            :title="locale"
                            @click="changeLocale(locale)"
                            :key="id"
                            :class="{ 'bg-success-subtle text-dark': selectedLocale === locale }">
                            <span class="align-middle">{{ locale }}</span>


                            <i v-if="selectedLocale === locale" class="bx bx-check text-success float-end fs-22"></i>
                        </BLink>
                    </BDropdown> -->
                    <BDropdown class="dropdown" variant="ghost-secondary" dropstart
                        :offset="{ alignmentAxis: 55, crossAxis: 15, mainAxis: -50 }"
                        toggle-class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle arrow-none"
                        menu-class="dropdown-menu-end">

                        <template #button-content>
                            <div class="bg-success-subtle" style="height: 38px; width: 38px; border-radius: 45px;">
                                <i class="ri-translate fs-22 text-success"></i>
                            </div>
                        </template>

                        <BLink href="javascript:void(0);" class="dropdown-item notify-item language py-2"
                            v-for="(language, locale) in locales"
                            :data-lang="locale"
                            :title="language"
                            @click="changeLocale(locale)"
                            :key="locale"
                            :class="{ 'bg-success-subtle text-dark': selectedLocale === locale }">

                            <span class="align-middle">{{ $t(language) }}</span>

                            <!-- Checkmark for selected language -->
                            <i v-if="selectedLocale === locale" class="bx bx-check text-success float-end fs-22"></i>
                        </BLink>
                    </BDropdown>
                </div>
                <div class="">
                    <!-- <BLink v-if="enable_web_booking == 1" class="btn text-white" style="background-color: var(--landing_header_act_text);" :href="user_login">{{ $t('landing_page.book_now_btn') }}</BLink> -->
                </div>
            </BCollapse>
        </BContainer>
    </nav>
</template>

