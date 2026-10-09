<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref, onMounted } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import search from "@/Components/widgets/search.vue";
import searchbar from "@/Components/widgets/searchbar.vue";
import { useI18n } from 'vue-i18n';
import L from "leaflet";
import 'leaflet-routing-machine';
import "leaflet/dist/leaflet.css";


export default {
    data() {
        return {
            rightOffcanvas: false,
        };
    },
    components: {
        Layout,
        PageHeader,
        Head,
        Multiselect,
        flatPickr,
        Link,
        search,
        searchbar,

    },
    props: {
        successMessage: String,
        alertMessage: String,

        pick_icon: String,
        drop_icon: String,
        stop_icon: String,
        firebaseConfig: Object,
        request: Object,
    },
    setup(props) {
        const { t } = useI18n();
        const result = ref(props.request);
        const map = ref(null);
        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const rideStatus = (trip) => {
            if(trip.is_cancelled){
                return 'Cancelled';
            }else if(trip.is_completed){
                return 'Completed';
            }else if(trip.is_trip_start){
                return 'On Trip';
            }else if(trip.is_driver_arrived){
                return 'Driver Arrived';
            }else if(trip.is_later && trip.is_driver_started){
                return 'Driver Started';
            }else if(trip.is_driver_started){
                return 'Accepted';
            }else if(!trip.is_later){
                return 'Searching';
            }else{
                return 'Upcoming'
            }
        };

        const initMap = () => {
            if (props.request) {
                // Initialize Leaflet map
                map.value = L.map('map').setView([props.request.pick_lat, props.request.pick_lng], 13);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map.value);

                // Custom icons
                const pickupIcon = L.icon({
                    iconUrl: props.pick_icon,
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                const dropoffIcon = L.icon({
                    iconUrl: props.drop_icon,
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                // Add markers
                const pickupMarker = L.marker([props.request.pick_lat, props.request.pick_lng], { icon: pickupIcon })
                    .addTo(map.value)
                    .bindPopup('Pickup Location');

                const dropoffMarker = L.marker([props.request.drop_lat, props.request.drop_lng], { icon: dropoffIcon })
                    .addTo(map.value)
                    .bindPopup('Dropoff Location');

                // Add routing
                const control = L.Routing.control({
                    waypoints: [
                        L.latLng(props.request.pick_lat, props.request.pick_lng),
                        L.latLng(props.request.drop_lat, props.request.drop_lng)
                    ],
                    routeWhileDragging: true,
                    addWaypoints: false,
                    createMarker: function() { return null; }, // Don't create additional markers
                    lineOptions: {
                        styles: [{ color: 'blue', weight: 4, opacity: 0.7 }]
                    }
                }).addTo(map.value);

                // Fit map to show both markers
                const group = new L.featureGroup([pickupMarker, dropoffMarker]);
                map.value.fitBounds(group.getBounds().pad(0.1));
            }
        };

        onMounted(() => {
            initMap();
        });

        return {
            result,
            map,
            successMessage,
            alertMessage,
            dismissMessage,
            rideStatus,
            initMap
        };
    }
};
</script>

<template>
    <Layout>
        <Head :title="$t('food_request_details')" />
        <PageHeader :title="$t('food_request_details')" :pageTitle="$t('food_requests')" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ $t('request_information') }}</h5>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="6">
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('request_id') }}:</label>
                                    <p class="form-control-plaintext">{{ result.request_number }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('user_name') }}:</label>
                                    <p class="form-control-plaintext">{{ result.user_detail ? result.user_detail.name : '----' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('driver_name') }}:</label>
                                    <p class="form-control-plaintext">{{ result.driver_detail ? result.driver_detail.name : '----' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('transport_type') }}:</label>
                                    <p class="form-control-plaintext">{{ $t('food') }} {{ result.is_bid_ride ? $t('bidding') : '' }} - {{ result.vehicle_type_name }}</p>
                                </div>
                            </BCol>
                            <BCol md="6">
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('date') }}:</label>
                                    <p class="form-control-plaintext">{{ result.is_later ? result.converted_trip_start_time : result.converted_created_at }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('trip_status') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase': true,
                                            'text-bg-success': rideStatus(result) === 'Completed' || rideStatus(result) === 'Accepted' || rideStatus(result) === 'Driver Started',
                                            'text-bg-danger': rideStatus(result) === 'Cancelled',
                                            'text-bg-info': rideStatus(result) === 'On Trip',
                                            'text-bg-warning': rideStatus(result) === 'Upcoming' || rideStatus(result) === 'Driver Arrived' || rideStatus(result) === 'Searching',
                                        }">{{ $st(rideStatus(result)) }}</BBadge>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('payment_option') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase':true,
                                            'text-bg-success': result.is_paid,
                                            'text-bg-danger': !result.is_paid,
                                        }">{{ $t(result.payment_opt == 1 ? 'cash' : (result.payment_opt == 2 ? 'wallet' : 'card')) }}</BBadge>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('payment_status') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase':true,
                                            'text-bg-success': result.is_paid,
                                            'text-bg-danger': !result.is_paid,
                                        }">{{ result.is_paid ? $t('paid') : $t('not_paid') }}</BBadge>
                                    </p>
                                </div>
                            </BCol>
                        </BRow>
                        
                        <div class="mt-4">
                            <h6>{{ $t('pickup_location') }}</h6>
                            <p>{{ result.pick_address }}</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>{{ $t('dropoff_location') }}</h6>
                            <p>{{ result.drop_address }}</p>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <BRow class="mt-4">
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ $t('route_map') }}</h5>
                    </BCardHeader>
                    <BCardBody>
                        <div id="map" style="height: 400px; width: 100%;"></div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Success Message -->
        <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show">
            <div class="alert-content">
                <i class="ri-notification-off-line me-3 align-middle"></i>
                <strong>{{ $t('success') }}</strong> - {{ successMessage }}
                <button type="button" class="btn-close btn-close-success" @click="dismissMessage"></button>
            </div>
        </div>

        <!-- Alert Message -->
        <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show">
            <div class="alert-content">
                <i class="ri-notification-off-line me-3 align-middle"></i>
                <strong>{{ $t('alert') }}</strong> - {{ alertMessage }}
                <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"></button>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
.custom-alert {
    max-width: 600px;
    float: right;
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 1050;
}

.rtl .custom-alert {
    max-width: 600px;
    float: left;
    right: auto;
    left: 20px;
}

@media only screen and (max-width: 1024px) {
    .custom-alert {
        max-width: 600px;
        float: right;
        position: fixed;
        top: 90px;
        right: 20px;
    }
    
    .rtl .custom-alert {
        max-width: 600px;
        float: left;
        right: auto;
        left: 20px;
    }
}
</style>
