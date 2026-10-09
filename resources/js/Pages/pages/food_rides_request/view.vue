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

        serviceLocations: {
            type: Object,
            required: true,
        },
        googleMapKey: String,
        pick_icon: String,
        drop_icon: String,
        stop_icon: String,
        rejected_drivers: Object,
        firebaseConfig: Object,

        request: Object,
    },
    methods: {
        navigateToInvoice(invoiceType, id) {
            // Navigate to the invoice Blade file
            const url = `/food-rides-request/download-invoice/${id}?invoice_type=${invoiceType}`;
            window.location.href = url;
        },
        navigateUserInvoice(id) {
            this.navigateToInvoice("user", id);
        },
        navigateDriverInvoice(id) {
            this.navigateToInvoice("driver", id);
        },
        async cancelRide(id) {
            try {
                const response = await axios.get(`/food-rides-request/cancel/${id}`);
                Swal.fire(this.$t('success'), this.$t('trip_cancelled_successfully'), 'success');
                // Optionally refresh the page or update the request data
                this.request.is_cancelled = true;
            } catch (error) {
                console.error(error);
                Swal.fire(this.$t('error'), this.$t('failed_to_cancel_trip'), 'error');
            }
        },
        async assignDriver(driverId) {
            try {
                const response = await axios.post(`/ongoing-rides/assign-driver/${this.request.id}`, {
                    driver_id: driverId
                });
                Swal.fire(this.$t('success'), this.$t('driver_assigned_successfully'), 'success');
                // Optionally refresh the page or update the request data
            } catch (error) {
                console.error(error);
                Swal.fire(this.$t('error'), this.$t('failed_to_assign_driver'), 'error');
            }
        }
    },
    setup(props) {
        const { t } = useI18n();
        const map = ref(null);
        const directionsRenderer = ref(null);
        const directionsService = ref(null);
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
            if (props.googleMapKey && props.request) {
                // Initialize Google Map
                const mapOptions = {
                    center: { lat: parseFloat(props.request.pick_lat), lng: parseFloat(props.request.pick_lng) },
                    zoom: 12
                };
                
                map.value = new google.maps.Map(document.getElementById('map'), mapOptions);
                directionsService.value = new google.maps.DirectionsService();
                directionsRenderer.value = new google.maps.DirectionsRenderer();
                directionsRenderer.value.setMap(map.value);

                // Add markers for pickup and dropoff
                const pickupMarker = new google.maps.Marker({
                    position: { lat: parseFloat(props.request.pick_lat), lng: parseFloat(props.request.pick_lng) },
                    map: map.value,
                    icon: props.pick_icon,
                    title: 'Pickup Location'
                });

                const dropoffMarker = new google.maps.Marker({
                    position: { lat: parseFloat(props.request.drop_lat), lng: parseFloat(props.request.drop_lng) },
                    map: map.value,
                    icon: props.drop_icon,
                    title: 'Dropoff Location'
                });

                // Calculate and display route
                calculateRoute();
            }
        };

        const calculateRoute = () => {
            if (directionsService.value && directionsRenderer.value && props.request) {
                const request = {
                    origin: { lat: parseFloat(props.request.pick_lat), lng: parseFloat(props.request.pick_lng) },
                    destination: { lat: parseFloat(props.request.drop_lat), lng: parseFloat(props.request.drop_lng) },
                    travelMode: google.maps.TravelMode.DRIVING
                };

                directionsService.value.route(request, (result, status) => {
                    if (status === google.maps.DirectionsStatus.OK) {
                        directionsRenderer.value.setDirections(result);
                    }
                });
            }
        };

        onMounted(() => {
            // Initialize map when component is mounted
            if (window.google && window.google.maps) {
                initMap();
            } else {
                // Load Google Maps API if not already loaded
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapKey}&libraries=places&callback=initMap`;
                script.async = true;
                script.defer = true;
                window.initMap = initMap;
                document.head.appendChild(script);
            }
        });

        return {
            map,
            directionsRenderer,
            directionsService,
            successMessage,
            alertMessage,
            dismissMessage,
            rideStatus,
            initMap,
            calculateRoute
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
                                    <p class="form-control-plaintext">{{ request.request_number }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('user_name') }}:</label>
                                    <p class="form-control-plaintext">{{ request.user_detail ? request.user_detail.name : '----' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('driver_name') }}:</label>
                                    <p class="form-control-plaintext">{{ request.driver_detail ? request.driver_detail.name : '----' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('transport_type') }}:</label>
                                    <p class="form-control-plaintext">{{ $t('food') }} {{ request.is_bid_ride ? $t('bidding') : '' }} - {{ request.vehicle_type_name }}</p>
                                </div>
                            </BCol>
                            <BCol md="6">
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('date') }}:</label>
                                    <p class="form-control-plaintext">{{ request.is_later ? request.converted_trip_start_time : request.converted_created_at }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('trip_status') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase': true,
                                            'text-bg-success': rideStatus(request) === 'Completed' || rideStatus(request) === 'Accepted' || rideStatus(request) === 'Driver Started',
                                            'text-bg-danger': rideStatus(request) === 'Cancelled',
                                            'text-bg-info': rideStatus(request) === 'On Trip',
                                            'text-bg-warning': rideStatus(request) === 'Upcoming' || rideStatus(request) === 'Driver Arrived' || rideStatus(request) === 'Searching',
                                        }">{{ $st(rideStatus(request)) }}</BBadge>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('payment_option') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase':true,
                                            'text-bg-success': request.is_paid,
                                            'text-bg-danger': !request.is_paid,
                                        }">{{ $t(request.payment_opt == 1 ? 'cash' : (request.payment_opt == 2 ? 'wallet' : 'card')) }}</BBadge>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ $t('payment_status') }}:</label>
                                    <p class="form-control-plaintext">
                                        <BBadge :class="{
                                            'text-uppercase':true,
                                            'text-bg-success': request.is_paid,
                                            'text-bg-danger': !request.is_paid,
                                        }">{{ request.is_paid ? $t('paid') : $t('not_paid') }}</BBadge>
                                    </p>
                                </div>
                            </BCol>
                        </BRow>
                        
                        <div class="mt-4">
                            <h6>{{ $t('pickup_location') }}</h6>
                            <p>{{ request.pick_address }}</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>{{ $t('dropoff_location') }}</h6>
                            <p>{{ request.drop_address }}</p>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex gap-2">
                                <button v-if="!request.is_cancelled && !request.is_completed" 
                                        @click="cancelRide(request.id)" 
                                        class="btn btn-danger">
                                    <i class="ri-close-line me-1"></i> {{ $t('cancel') }}
                                </button>
                                <Link v-if="!request.driver_id && !request.is_cancelled && !request.is_completed" 
                                      :href="`/ongoing-rides/assign/${request.id}`" 
                                      class="btn btn-primary">
                                    <i class="ri-user-add-line me-1"></i> {{ $t('assign') }}
                                </Link>
                                <button v-if="request.is_completed" 
                                        @click="navigateUserInvoice(request.id)" 
                                        class="btn btn-success">
                                    <i class="ri-file-download-line me-1"></i> {{ $t('download_user_invoice') }}
                                </button>
                                <button v-if="request.is_completed" 
                                        @click="navigateDriverInvoice(request.id)" 
                                        class="btn btn-info">
                                    <i class="ri-file-download-line me-1"></i> {{ $t('download_driver_invoice') }}
                                </button>
                            </div>
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

        <div v-if="rejected_drivers && rejected_drivers.length > 0" class="mt-4">
            <BCard no-body>
                <BCardHeader>
                    <h5 class="card-title mb-0">{{ $t('rejected_drivers') }}</h5>
                </BCardHeader>
                <BCardBody>
                    <div class="table-responsive">
                        <table class="table table-nowrap">
                            <thead>
                                <tr>
                                    <th>{{ $t('driver_name') }}</th>
                                    <th>{{ $t('reason') }}</th>
                                    <th>{{ $t('rejected_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="driver in rejected_drivers" :key="driver.id">
                                    <td>{{ driver.drivers ? driver.drivers.name : '----' }}</td>
                                    <td>{{ driver.reason || '----' }}</td>
                                    <td>{{ driver.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BCardBody>
            </BCard>
        </div>

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
