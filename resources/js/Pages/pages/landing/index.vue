<script>
import { CountTo } from "vue3-count-to";
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/autoplay";
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import LandingHeader from "@/Components/LandingHeader.vue";
import LandingFooter from "@/Components/LandingFooter.vue";
import { Link, router, Head } from '@inertiajs/vue3';
import Cookie from '@/Layouts/Cookie.vue';
import AOS from 'aos';
import 'aos/dist/aos.css';
import lottie from 'lottie-web';
import carDrivingAnimation from '@/Components/widgets/Car Driving Landscape.json';
import carDrivingRoadAnimation from '@/Components/widgets/Car driving on road.json';

export default {
    props: {
        landingHome: Object,
        landingHeader: Object,
    },
    computed: {
        splitServices() {
            if (typeof this.landingHome.services === 'string') {
                return this.landingHome.services.split(',');
            }
            return [];
        },
        splitAbout() {
            if (typeof this.landingHome.about_lists === 'string') {
                return this.landingHome.about_lists.split(',');
            }
            return [];
        },
        features() {
            return [
                {
                    icon: 'ri-map-pin-line',
                    title: this.$t('landing_page.easy_booking'),
                    description: this.$t('landing_page.easy_booking_desc')
                },
                {
                    icon: 'ri-shield-check-line',
                    title: this.$t('landing_page.safe_secure'),
                    description: this.$t('landing_page.safe_secure_desc')
                },
                {
                    icon: 'ri-price-tag-3-line',
                    title: this.$t('landing_page.fair_pricing'),
                    description: this.$t('landing_page.fair_pricing_desc')
                },
                {
                    icon: 'ri-time-line',
                    title: this.$t('landing_page.available_247'),
                    description: this.$t('landing_page.available_247_desc')
                }
            ];
        },
        steps() {
            return [
                {
                    title: this.$t('landing_page.step_1'),
                    description: this.$t('landing_page.step_1_desc')
                },
                {
                    title: this.$t('landing_page.step_2'),
                    description: this.$t('landing_page.step_2_desc')
                },
                {
                    title: this.$t('landing_page.step_3'),
                    description: this.$t('landing_page.step_3_desc')
                },
                {
                    title: this.$t('landing_page.step_4'),
                    description: this.$t('landing_page.step_4_desc')
                }
            ];
        }
    },
    data() {
        return {
            Autoplay, Navigation, Pagination,
            activeApp: 'user', // Default to user app
        };
    },
    mounted() {
        if (this.$refs.carLottie) {
            lottie.loadAnimation({
                container: this.$refs.carLottie,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                animationData: carDrivingAnimation
            });
        }
        if (this.$refs.carRoadLottie) {
            lottie.loadAnimation({
                container: this.$refs.carRoadLottie,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                animationData: carDrivingRoadAnimation
            });
        }
        AOS.init({
            once: true,
            offset: 50,
            duration: 1000,
        });

        // Initialize counters
        this.initCounters();

        // Handle hash scrolling
        this.handleHashScroll();
    },
    methods: {
        stripHtmlTags(content) {
            const parser = new DOMParser();
            const parsedContent = parser.parseFromString(content, 'text/html');
            return parsedContent.body.textContent || "";
        },
        initCounters() {
            const counters = document.querySelectorAll('.counter');
            const speed = 200;

            const animateCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                const increment = target / speed;

                const updateCount = () => {
                    const count = +counter.innerText;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target;
                    }
                };

                updateCount();
            };

            const observerOptions = {
                threshold: 0.5
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            counters.forEach(counter => {
                observer.observe(counter);
            });
        },
        handleHashScroll() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                // Small delay to ensure page is fully rendered
                setTimeout(() => {
                    const element = document.getElementById(hash);
                    if (element) {
                        const navbarHeight = 80; // Height of fixed navbar
                        const elementPosition = element.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - navbarHeight;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                }, 100);
            }
        }
    },
    components: {
        Swiper,
        SwiperSlide,
        CountTo,
        LandingHeader,
        LandingFooter,
        Link,
        router,
        Head,
        Cookie
    },
};
</script>

<template>
    <div class="landing-page">
        <Head :title="$t('home')" />
        <LandingHeader :headers="landingHeader" />

        <!-- Hero Section -->
        <section class="hero" id="hero">
            <div class="hero-content">
                <div class="hero-text" data-aos="fade-up">
                    <h1 class="hero-title">{{ landingHome.feature_heading }}</h1>
                    <p class="hero-subtitle">{{ stripHtmlTags(landingHome.feature_para) }}</p>
                    <div class="hero-buttons">
                        <a :href="landingHome.hero_user_link_android" target="_blank" class="btn btn-primary">
                            <i class="ri-store-2-fill"></i>
                            {{ $t('store') }}
                        </a>
                        <a :href="landingHome.hero_user_link_direct" target="_blank" class="btn btn-dark">
                            <i class="ri-download-line"></i>
                            {{ $t('direct_download') }}
                        </a>
                    </div>
                </div>
                <div class="hero-visual" data-aos="fade-left">
                    <div ref="carLottie" class="lottie-animation"></div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>{{ landingHome.feature_heading }}</h2>
                    <!-- <p>{{ stripHtmlTags(landingHome.feature_para) }}</p> -->
                </div>
                <div class="features-grid">
                    <div v-for="(feature, index) in features" :key="index"
                         class="feature-card" data-aos="fade-up" :data-aos-delay="index * 100">
                        <div class="feature-icon">
                            <i :class="feature.icon"></i>
                        </div>
                        <h3>{{ feature.title }}</h3>
                        <p>{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="how-it-works" id="services">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>{{ landingHome.drive_heading }}</h2>
                    <p>{{ $t('landing_page.how_it_works_subtitle') }}</p>
                </div>
                <div class="steps-container">
                    <div class="steps-visual" data-aos="fade-right">
                        <div ref="carRoadLottie" class="lottie-animation"></div>
                    </div>
                    <div class="steps-list">
                        <div v-for="(step, index) in steps" :key="index"
                             class="step-item" data-aos="fade-left" :data-aos-delay="index * 100">
                            <div class="step-number">{{ index + 1 }}</div>
                            <div class="step-content">
                                <h4>{{ step.title }}</h4>
                                <p>{{ step.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services" id="services">
            <div class="container">
                <div class="services-content">
                    <div class="services-text" data-aos="fade-right">
                        <h2>{{ landingHome.service_heading_2 }}</h2>
                        <p>{{ stripHtmlTags(landingHome.service_para) }}</p>
                        <div class="services-list">
                            <div v-for="(item, index) in splitServices" :key="index"
                                 class="service-item" data-aos="fade-up" :data-aos-delay="index * 100">
                                <i class="ri-check-line"></i>
                                <span>{{ stripHtmlTags(item) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="services-image" data-aos="fade-left">
                        <img :src="landingHome.service_img_url" alt="Services" />
                    </div>
                </div>
            </div>
        </section>

        <section class="food-section" id="food-section">
            <div class="container">
                <div class="food-how-it-works">
                    <div class="section-header" data-aos="fade-up">
                        <h3>{{ $t('landing_page.how_food_delivery_works') }}</h3>
                    </div>

                    <div class="food-steps">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="100">
                                <div class="food-step">
                                    <div class="step-number">1</div>
                                    <div class="step-icon">
                                        <i class="ri-search-line"></i>
                                    </div>
                                    <h5>{{ $t('landing_page.browse_restaurants') }}</h5>
                                    <p>{{ $t('landing_page.browse_restaurants_desc') }}</p>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="200">
                                <div class="food-step">
                                    <div class="step-number">2</div>
                                    <div class="step-icon">
                                        <i class="ri-shopping-cart-2-line"></i>
                                    </div>
                                    <h5>{{ $t('landing_page.place_order') }}</h5>
                                    <p>{{ $t('landing_page.place_order_desc') }}</p>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="300">
                                <div class="food-step">
                                    <div class="step-number">3</div>
                                    <div class="step-icon">
                                        <i class="ri-truck-line"></i>
                                    </div>
                                    <h5>{{ $t('landing_page.track_delivery') }}</h5>
                                    <p>{{ $t('landing_page.track_delivery_desc') }}</p>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="400">
                                <div class="food-step">
                                    <div class="step-number">4</div>
                                    <div class="step-icon">
                                        <i class="ri-restaurant-line"></i>
                                    </div>
                                    <h5>{{ $t('landing_page.enjoy_food') }}</h5>
                                    <p>{{ $t('landing_page.enjoy_food_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                            </div>
        </section>

        <!-- App Download -->
        <section class="app-download" id="app-download">
            <div class="container">
                <div class="download-card" data-aos="zoom-in">
                    <h2>{{ $t('landing_page.download_title') }}</h2>
                    <p>{{ $t('landing_page.download_subtitle') }}</p>

                    <!-- App Type Switcher -->
                    <div class="app-switcher">
                        <div class="switcher-buttons">
                            <button
                                @click="activeApp = 'user'"
                                :class="['switcher-btn', { active: activeApp === 'user' }]"
                            >
                                <i class="ri-user-line"></i>
                                {{ $t('landing_page.user_app') }}
                            </button>
                            <button
                                @click="activeApp = 'driver'"
                                :class="['switcher-btn', { active: activeApp === 'driver' }]"
                            >
                                <i class="ri-steering-2-line"></i>
                                {{ $t('landing_page.driver_app') }}
                            </button>
                        </div>
                    </div>

                    <div class="download-buttons">
                        <!-- User App Section -->
                        <div v-show="activeApp === 'user'" class="app-section">
                            <h4>{{ landingHeader.user_app }}</h4>
                            <div class="app-buttons">
                                <a :href="landingHome.hero_user_link_android" target="_blank" class="btn btn-primary">
                                    <i class="ri-google-play-fill"></i>
                                    {{ $t('store') }}
                                </a>
                                <a :href="landingHome.hero_user_link_direct" target="_blank" class="btn btn-dark">
                                    <i class="ri-download-line"></i>
                                    {{ $t('direct_download') }}
                                </a>
                            </div>
                        </div>

                        <!-- Driver App Section -->
                        <div v-show="activeApp === 'driver'" class="app-section">
                            <h4>{{ landingHeader.driver_app }}</h4>
                            <div class="app-buttons">
                                <a :href="landingHome.hero_driver_link_android" target="_blank" class="btn btn-primary">
                                    <i class="ri-google-play-fill"></i>
                                    {{ $t('store') }}
                                </a>
                                <a :href="landingHome.hero_driver_link_direct" target="_blank" class="btn btn-dark">
                                    <i class="ri-download-line"></i>
                                    {{ $t('direct_download') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Area -->
        <section class="service-area" id="service-area">
            <div class="container">
                <div class="area-content">
                    <div class="area-text" data-aos="fade-right">
                        <h2>{{ landingHome.service_area_title }}</h2>
                        <p>{{ stripHtmlTags(landingHome.service_area_para) }}</p>
                        <div class="area-stats">
                            <div class="stat">
                                <h3>{{ landingHome.service_area_cities || 50 }}+</h3>
                                <p>{{ $t('landing_page.cities_covered') }}</p>
                            </div>
                            <div class="stat">
                                <h3>{{ landingHome.service_area_drivers || 1000 }}+</h3>
                                <p>{{ $t('landing_page.active_drivers') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="area-visual" data-aos="fade-left">
                        <div class="map-placeholder">
                            <div class="map-points">
                                <div class="point"></div>
                                <div class="point"></div>
                                <div class="point"></div>
                                <div class="point"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <LandingFooter />
    </div>
</template>

<style scoped>
/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Scroll padding for fixed navbar */
:root {
    scroll-padding-top: 80px;
}

/* Base styles */
.landing-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
    overflow-x: hidden;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: white;
    color: #333;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 80%, rgba(254, 96, 29, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(254, 96, 29, 0.03) 0%, transparent 50%);
    z-index: 1;
}

.hero::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23fe601d" fill-opacity="0.03"><path d="M20 40c0-2.2 1.8-4 4-4s4 1.8 4 4 4-1.8 4-4-4-1.8-4-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2zm0 4c0 2.2 1.8 4 4 4s4-1.8 4-4-4-1.8-4-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2z"/><path d="M40 30c-2.2 0-4 1.8-4 4s4 1.8 4 4 4-1.8 4-4-4-1.8-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2zm0 4c0 2.2 1.8 4 4 4s4-1.8 4-4-4-1.8-4-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2z"/><path d="M60 50c-2.2 0-4 1.8-4 4s4 1.8 4 4 4-1.8 4-4-4-1.8-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2zm0 4c0 2.2 1.8 4 4 4s4-1.8 4-4-4-1.8-4-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2z"/><path d="M80 35c-2.2 0-4 1.8-4 4s4 1.8 4 4 4-1.8 4-4-4-1.8-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2zm0 4c0 2.2 1.8 4 4 4s4-1.8 4-4-4-1.8-4-4-4-4zm-1 8v4c0 1.1.9 2 2 2s2-.9 2-2-2-.9-2-2-2-2z"/></g></g></svg>') repeat;
    animation: drive 25s linear infinite;
    z-index: 1;
}

@keyframes drive {
    0% { transform: translateX(-50px) translateY(0); }
    25% { transform: translateX(30px) translateY(-10px); }
    50% { transform: translateX(20px) translateY(5px); }
    75% { transform: translateX(-20px) translateY(-5px); }
    100% { transform: translateX(-50px) translateY(0); }
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    position: relative;
    z-index: 3;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.hero-title {
    font-size: 4rem;
    font-weight: 900;
    margin-bottom: 25px;
    line-height: 1.1;
    background: linear-gradient(135deg, #fe601d 0%, #ff8c42 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    position: relative;
}

.hero-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(135deg, #fe601d, #ff8c42);
    border-radius: 2px;
}

.hero-subtitle {
    font-size: 1.3rem;
    margin-bottom: 35px;
    opacity: 0.8;
    line-height: 1.6;
    font-weight: 400;
    font-family: Cairo, sans-serif;
}

.hero-buttons {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
    align-items: center;
    font-family: 'Cairo';
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 18px 35px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 700;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: none;
    cursor: pointer;
    font-size: 17px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: #fe601d;
    color: white;
    box-shadow: 0 10px 30px rgba(254, 96, 29, 0.3);
}

.btn-primary:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 15px 40px rgba(254, 96, 29, 0.4);
    background: #ff7043;
}

.btn-dark {
    background: transparent;
    color: #fe601d;
    border: 2px solid #fe601d;
    box-shadow: 0 5px 20px rgba(254, 96, 29, 0.1);
}

.btn-dark:hover {
    background: #fe601d;
    color: white;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 15px 40px rgba(254, 96, 29, 0.3);
    border-color: #fe601d;
}

.hero-visual {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

.hero-visual::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(254, 96, 29, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    animation: carPulse 4s ease-in-out infinite;
    z-index: 1;
}

.hero-visual::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 400px;
    height: 400px;
    border: 2px dashed rgba(254, 96, 29, 0.1);
    border-radius: 50%;
    animation: rotate 20s linear infinite;
    z-index: 0;
}

@keyframes carPulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.6; }
}

@keyframes rotate {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

.lottie-animation {
    max-width: 100%;
    height: auto;
}

/* Section Headers */
.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
}

.section-header p {
    font-size: 1.1rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

/* Features Section */
.features {
    padding: 100px 0;
    background: #f8f9fa;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.feature-card {
    background: white;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    font-family: Cairo, sans-serif;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.feature-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #fe601d, #ff8c42);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.feature-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

/* How It Works */
.how-it-works {
    padding: 100px 0;
    font-family: Cairo, sans-serif;
}

.steps-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #fe601d, #ff8c42);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.step-content h4 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.step-content p {
    color: #666;
    line-height: 1.6;
}

/* Services Section */
.services {
    padding: 100px 0;
    background: #f8f9fa;
    font-family: Cairo, sans-serif;
}

.services-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.services-text h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: #333;
}

.services-text p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.services-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.service-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.service-item i {
    color: #fe601d;
    font-size: 20px;
}

.service-item span {
    color: #333;
    font-weight: 500;
}

.services-image img {
    width: 100%;
    height: auto;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* App Download */
.app-download {
    padding: 120px 0;
    background: linear-gradient(135deg, #fe601d 0%, #ff8c42 100%);
    color: white;
    position: relative;
    overflow: hidden;
    font-family: Cairo, sans-serif;
}

.app-download::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.05"><circle cx="30" cy="30" r="4"/></g></g></svg>') repeat;
    z-index: 1;
}

.download-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border-radius: 30px;
    padding: 70px 50px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 2;
    transition: transform 0.3s ease;
}

.download-card:hover {
    transform: translateY(-5px);
}

.download-card h2 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.download-card > p {
    font-size: 1.3rem;
    margin-bottom: 50px;
    opacity: 0.95;
    font-weight: 400;
}

.download-buttons {
    display: grid;
    /* grid-template-columns: 1fr 1fr; */
    gap: 50px;
    max-width: 900px;
    margin: 0 auto;
}

.app-section h4 {
    font-size: 1.5rem;
    margin-bottom: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.app-buttons {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.app-buttons .btn {
    justify-content: center;
    padding: 18px 35px;
    font-size: 17px;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.app-buttons .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
}

.app-buttons .btn-primary {
    background: white;
    color: #fe601d;
    border: 2px solid white;
}

.app-buttons .btn-dark {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.app-buttons .btn-dark:hover {
    background: white;
    color: #fe601d;
}

/* Service Area */
.service-area {
    padding: 100px 0;
    background: #f8f9fa;
    font-family: Cairo, sans-serif;
}

.area-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.area-text h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: #333;
}

.area-text p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 40px;
    line-height: 1.6;
}

.area-stats {
    display: flex;
    gap: 40px;
}

.stat {
    text-align: center;
}

.stat h3 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fe601d;
    margin-bottom: 10px;
}

.stat p {
    color: #666;
    font-weight: 500;
}

.area-visual {
    display: flex;
    justify-content: center;
    align-items: center;
}

.map-placeholder {
    width: 100%;
    height: 400px;
    background: linear-gradient(45deg, #e3f2fd, #bbdefb);
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.map-points {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.point {
    position: absolute;
    width: 20px;
    height: 20px;
    background: #fe601d;
    border-radius: 50%;
    box-shadow: 0 0 20px rgba(254, 96, 29, 0.5);
    animation: carMove 8s ease-in-out infinite;
}

.point::before {
    content: '';
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 2px solid #fe601d;
    border-bottom: 2px solid #fe601d;
    border-width: 0 0 15px 15px;
    border-color: transparent transparent #fe601d #fe601d;
    opacity: 0.6;
}

.point:nth-child(1) {
    top: 20%;
    left: 30%;
    animation-delay: 0s;
}
.point:nth-child(2) {
    top: 60%;
    left: 70%;
    animation-delay: 2s;
}
.point:nth-child(3) {
    top: 40%;
    left: 50%;
    animation-delay: 4s;
}
.point:nth-child(4) {
    top: 80%;
    left: 20%;
    animation-delay: 6s;
}

@keyframes carMove {
    0%, 100% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    50% {
        transform: translateY(-10px) scale(1.1);
        opacity: 0.8;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
        padding-top: 7rem;
    }

    .hero-title {
        font-size: 2.5rem;
    }

    .hero-buttons {
        justify-content: center;
    }

    .steps-container,
    .services-content,
    .area-content {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .download-buttons {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .area-stats {
        flex-direction: column;
        gap: 20px;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .section-header h2 {
        font-size: 2rem;
    }

    .download-card {
        padding: 40px 20px;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }

    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }

    .btn {
        width: 100%;
        max-width: 300px;
    }

    .container {
        padding: 0 15px;
    }
}
</style>
