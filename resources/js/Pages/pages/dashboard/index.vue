<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { debounce } from 'lodash';
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import useVuelidate from "@vuelidate/core";
import getChartColorsArray from "@/common/getChartColorsArray";
import { useSharedState } from '@/composables/useSharedState';
import { useI18n } from 'vue-i18n';
import { layoutComputed } from "@/state/helpers";
import { mapGetters } from 'vuex';
import Warning from "@/Components/warning.vue";

export default {
  props: {
    firebaseConfig: Object,

  },
  data() {
    return {
      selectedServiceLocation: null, // To store the selected service location
      agent_addons:window.agent_addons
    };
  },
  computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
  },
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    flatPickr,
    Link,
    Warning
  },
  setup(props) {
    const { t } = useI18n();
    const { playAudioOnce, selectedLocation } = useSharedState();
    const series = ref([]);
    const chartOptions = ref({});
    const overall = ref([]);
    const overallChartOptions = ref({});
    const cancellation = ref([]);
    const cancelChartOptions = ref({});
    const sosRequests = ref([]);
    const seriesOverallTrip = ref([]);
    const cancelledtrips = ref({
      auto_cancelled : 0,
      user_cancelled : 0,
      driver_cancelled : 0,
      dispatcher_cancelled : 0,
      total_cancelled : 0,
    });
    const chartOptionsOverallTrip = ref({});
    const earningData = ref({
        card : 0,
        cash : 0,
        wallet : 0,
        total : 0,
        admin_commision : 0,
        driver_commision : 0,
    });
     const agentEarnings = ref(earningData.value);
    const agentEarningsChartOptions = ref({});

    const todayEarnings = ref(earningData.value);
    const overallEarnings = ref(earningData.value);
    const agentOverall = ref([]);

    // Food request statistics
    const foodSeries = ref([]);
    const foodChartOptions = ref({});
    const foodOverallSeries = ref([]);
    const foodOverallChartOptions = ref({});
    const foodEarningsChartOptions = ref({});
    const foodOverall = ref([]);
    const foodTodayEarnings = ref(earningData.value);
    const foodOverallEarnings = ref(earningData.value);

    const totalDrivers = ref({
        approved : 0,
        declined : 0,
        approve_percentage : 0,
        decline_percentage : 0,
        total : 0,
    });
    const totalUsers = ref(0);
    const foodDriversCount = ref(0);
    const currencySymbol = ref('');

    // Popular drop addresses chart data
    const popularDropAddressesData = ref([]);
    const popularDropAddressesChartOptions = ref({});

    // Date filters for popular drop addresses
    const dropAddressDateFrom = ref('');
    const dropAddressDateTo = ref('');
    const dropAddressDateConfig = {
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      locale: {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
          longhand: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        },
        months: {
          shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
          longhand: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        },
      },
    };

    const fetchDashboardData = async () => {
      try {
        const response = await axios.get('/dashboard/data',{ params:{service_location_id : selectedLocation.value}});
        totalDrivers.value = response.data.totalDrivers;
        totalUsers.value = response.data.totalUsers;
        currencySymbol.value = response.data.currencySymbol;
        foodDriversCount.value = response.data.foodDriversCount || 0;

      } catch (error) {
        console.error(t('error_fetching_today_earnings'), error);
      }
    }

    // Fetch data for today earnings chart
    const fetchTodayEarnings = async () => {
      try {
        const response = await axios.get('/dashboard/today-earnings',{ params:{service_location_id : selectedLocation.value}});
        series.value = [
          Number(response.data.today.completed),
          Number(response.data.today.cancelled),
          Number(response.data.today.scheduled),
        ];

        todayEarnings.value = response.data.today.earnings;
        chartOptions.value = {
          labels: [t('completed'), t('cancelled'), t('scheduled')],
          chart: {
            type: "donut",
            height: 219,
          },
          plotOptions: {
            pie: {
              size: 100,
              donut: {
                size: "75%",
              },
            },
          },
          dataLabels: {
            enabled: false,
          },
          legend: {
            show: false,
            position: "bottom",
            horizontalAlign: "center",
            offsetX: 0,
            offsetY: 0,
            markers: {
              width: 20,
              height: 6,
              radius: 2,
            },
            itemMargin: {
              horizontal: 12,
              vertical: 0,
            },
          },
          stroke: {
            width: 0,
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value;
              },
            },
            tickAmount: 4,
            min: 0,
          },
          colors: getChartColorsArray('["--vz-primary", "--vz-warning", "--vz-info"]'),
        };

        seriesOverallTrip.value = [
          Number(response.data.overall.completed),
          Number(response.data.overall.cancelled),
          Number(response.data.overall.scheduled),
        ];
        overallEarnings.value = response.data.overall.earnings;
        chartOptionsOverallTrip.value = generateChartOptions('overall');
      } catch (error) {
        console.error(t('error_fetching_today_earnings'), error);
      }
    };


    // Helper function to generate chart options
    const generateChartOptions = (type) => {
      return {
        labels: [t('completed'), t('cancelled'), t('scheduled')],
        chart: {
          type: 'donut',
          height: 219,
        },
        plotOptions: {
          pie: {
            size: 100,
            donut: {
              size: '75%',
            },
          },
        },
        dataLabels: {
          enabled: false,
        },
        legend: {
          show: false,
          position: 'bottom',
          horizontalAlign: 'center',
          markers: {
            width: 20,
            height: 6,
            radius: 2,
          },
        },
        stroke: {
          width: 0,
        },
        yaxis: {
          labels: {
            formatter: function (value) {
              return value;
            },
          },
          tickAmount: 4,
          min: 0,
        },
        colors: getChartColorsArray(
          type === 'today'
            ? '["--vz-primary", "--vz-warning", "--vz-info"]'
            : '["--vz-secondary", "--vz-danger", "--vz-success"]'
        ),
      };
    };

    const sos_update = async(sos) => {
      try {

        const response = await axios.get(`rides-request/detail/${sos.req_id}`);
        if (response.status === 200) {
          let trip = response.data.request;
          let sosData = {
            isUser: sos.is_user,
            isDriver: sos.is_driver,
            userName: trip.userDetail?.data?.name,
            driverName: trip.driverDetail?.data?.name,
            request_id: sos.req_id,
            date: response.data.current_time,
          };
          const existingIndex = sosRequests.value.findIndex(
            (request) => request.request_id === sosData.request_id
          );
          if (existingIndex === -1) {
            sosRequests.value.push(sosData);
          } else {
            sosRequests.value[existingIndex] = sosData;
          }
          playAudioOnce();
            Swal.fire({
              title: t('notified_sos'),
              text: t('sos_has_been_notified_proceed_to_details'),
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#34c38f",
              cancelButtonColor: "#f46a6a",
              confirmButtonText: t('check'),
              cancelButtonText: t('cancel')
          }).then(async (result) => {
              if (result.isConfirmed) {
                  router.get('/rides-request/view/'+sos.req_id);
              }
          });
        }
      }catch (error) {
        console.error(error);
      }
    }


    // Fetch data for overall earnings chart
    const fetchOverallEarnings = async () => {
      try {
        const response = await axios.get('/dashboard/overall-earnings',{ params:{service_location_id : selectedLocation.value}});
        overall.value = [
          {
            name: t('overall_earnings'),
            data: response.data.earnings.values,
          },
        ];
        overallChartOptions.value = {
          chart: {
            height: 100,
            type: "area",
            toolbar: "false",
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            curve: "smooth",
            width: 3,
          },
          xaxis: {
            categories: response.data.earnings.months, // x Axis months
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value.toFixed(1);
              },
            },
            tickAmount: 5,
            min: 0,
            max: Math.max(...response.data.earnings.values) * 1.1, // Adjust max value dynamically
          },
          colors: getChartColorsArray('["--vz-success"]'),
          fill: {
            opacity: 0,
            colors: ["#0AB39C", "#F06548"],
            type: "solid",
          },
        };
      } catch (error) {
        console.error(t('error_fetching_overall_earnings'), error);
      }
    };

    // Fetch data for cancellation chart
    const fetchCancellationData = async () => {
      try {
        const response = await axios.get('/dashboard/cancel-chart',{ params:{service_location_id : selectedLocation.value}});
        cancelledtrips.value = response.data['data'];
        cancellation.value = [
          {
            name: t('cancelled_due_to_no_drivers'),
            type: "bar",
            data: response.data['a'],
          },
          {
            name: t('cancelled_by_users'),
            type: "bar",
            data: response.data['u'],
          },
          {
            name: t('cancelled_by_drivers'),
            type: "bar",
            data: response.data['d'],
          },
        ];
        cancelChartOptions.value = {
          chart: {
            height: 374,
            type: "line",
            toolbar: {
              show: false,
            },
          },
          stroke: {
            curve: "smooth",
            dashArray: [0, 0, 0],
            width: [0, 0, 0],
          },
          fill: {
            opacity: [1, 1, 1],
          },
          markers: {
            size: [0, 0, 0],
            strokeWidth: 2,
            hover: {
              size: 4,
            },
          },
          xaxis: {
            categories: response.data['y'],
            axisTicks: {
              show: false,
            },
            axisBorder: {
              show: false,
            },
          },
          grid: {
            show: true,
            xaxis: {
              lines: {
                show: true,
              },
            },
            yaxis: {
              lines: {
                show: false,
              },
            },
            padding: {
              top: 0,
              right: -2,
              bottom: 15,
              left: 10,
            },
          },
          legend: {
            show: true,
            horizontalAlign: "center",
            offsetX: 0,
            offsetY: -5,
            markers: {
              width: 9,
              height: 9,
              radius: 6,
            },
            itemMargin: {
              horizontal: 10,
              vertical: 0,
            },
          },
          plotOptions: {
            bar: {
              columnWidth: "30%",
              barHeight: "70%",
            },
          },
          colors: getChartColorsArray('["--vz-primary", "--vz-warning", "--vz-success"]'),
          tooltip: {
            shared: true,
            y: [
              {
                formatter: function (y) {
                  if (typeof y !== "undefined") {
                    return y.toFixed(0);
                  }
                  return y;
                },
              },
              {
                formatter: function (y) {
                  if (typeof y !== "undefined") {
                    return y.toFixed(0);
                  }
                  return y;
                },
              },
              {
                formatter: function (y) {
                  if (typeof y !== "undefined") {
                    return y.toFixed(0);
                  }
                  return y;
                },
              },
            ],
          },
        };
      } catch (error) {
        console.error(t('error_fetching_cancellation_data'), error);
      }
    };

    const fetchAllData = async() => {
        await fetchDashboardData();
        await fetchTodayEarnings();
        await fetchOverallEarnings();
        await fetchCancellationData();
        await fetchAgentEarnings();
        await fetchFoodStatistics();
        await fetchPopularDropAddresses();
    }
    // Call APIs on component mount
    onMounted(async() => {
      try{
        let shouldProcessSosChildAdded = false;
        setTimeout(()=> {
          shouldProcessSosChildAdded = true;
        },4000);
        await fetchAllData();
        const firebaseConfig = props.firebaseConfig;
        if (!firebase.apps.length) {
          firebase.initializeApp(firebaseConfig);
        }
        const sosRef = firebase.database().ref('SOS');

        sosRef.on('child_changed', async function(snapshot) {
          var sosData = snapshot.val();
          if (shouldProcessSosChildAdded)
          {
            await sos_update(sosData);
          }
        });
        sosRef.on('child_added', async function(snapshot) {
            var sosData = snapshot.val();
            if (shouldProcessSosChildAdded)
            {
                await sos_update(sosData);
            }
        });
      }catch (error) {
        console.error(error);
      }
    });

    watch (()=>selectedLocation.value, (value) => {
      if(value){
          fetchAllData();
      }
    })

    // Watch for date filter changes
    watch([dropAddressDateFrom, dropAddressDateTo], (newValues, oldValues) => {
      fetchPopularDropAddresses();
    }, { deep: true })


     // Fetch data for overall earnings chart
    const fetchAgentEarnings = async () => {
      try {
        const response = await axios.get('/dashboard/agent-earnings',{ params:{service_location_id : selectedLocation.value}});
        agentEarnings.value = response.data.earnings;
        agentOverall.value = [
          {
            name: t('agent_earnings'),
            data: response.data.earnings.values,
          },
        ];
        agentEarningsChartOptions.value = {
          chart: {
            height: 100,
            type: "area",
            toolbar: "false",
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            curve: "smooth",
            width: 3,
          },
          xaxis: {
            categories: response.data.earnings.months, // x Axis months
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value.toFixed(1);
              },
            },
            tickAmount: 5,
            min: 0,
            max: Math.max(...response.data.earnings.values) * 1.1, // Adjust max value dynamically
          },
          colors: getChartColorsArray('["--vz-success"]'),
          fill: {
            opacity: 0,
            colors: ["#0AB39C", "#F06548"],
            type: "solid",
          },
        };
      } catch (error) {
        console.error(t('error_fetching_agent_earnings'), error);
      }
    };

    // Fetch data for food statistics
    const fetchFoodStatistics = async () => {
      try {
        const response = await axios.get('/dashboard/food-statistics',{ params:{service_location_id : selectedLocation.value}});

        // Today's food trips
        foodSeries.value = [
          Number(response.data.today.completed),
          Number(response.data.today.cancelled),
          Number(response.data.today.scheduled),
        ];

        foodTodayEarnings.value = response.data.today.earnings;
        foodChartOptions.value = {
          labels: [t('completed'), t('cancelled'), t('scheduled')],
          chart: {
            type: "donut",
            height: 219,
          },
          plotOptions: {
            pie: {
              size: 100,
              donut: {
                size: "75%",
              },
            },
          },
          dataLabels: {
            enabled: false,
          },
          legend: {
            show: false,
            position: "bottom",
            horizontalAlign: "center",
            offsetX: 0,
            offsetY: 0,
            markers: {
              width: 20,
              height: 6,
              radius: 2,
            },
            itemMargin: {
              horizontal: 12,
              vertical: 0,
            },
          },
          stroke: {
            width: 0,
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value;
              },
            },
            tickAmount: 4,
            min: 0,
          },
          colors: getChartColorsArray('["--vz-warning", "--vz-danger", "--vz-info"]'),
        };

        // Overall food trips
        foodOverallSeries.value = [
          Number(response.data.overall.completed),
          Number(response.data.overall.cancelled),
          Number(response.data.overall.scheduled),
        ];
        foodOverallEarnings.value = response.data.overall.earnings;
        foodOverallChartOptions.value = {
          labels: [t('completed'), t('cancelled'), t('scheduled')],
          chart: {
            type: 'donut',
            height: 219,
          },
          plotOptions: {
            pie: {
              size: 100,
              donut: {
                size: '75%',
              },
            },
          },
          dataLabels: {
            enabled: false,
          },
          legend: {
            show: false,
            position: 'bottom',
            horizontalAlign: 'center',
            markers: {
              width: 20,
              height: 6,
              radius: 2,
            },
          },
          stroke: {
            width: 0,
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value;
              },
            },
            tickAmount: 4,
            min: 0,
          },
          colors: getChartColorsArray('["--vz-warning", "--vz-danger", "--vz-info"]'),
        };

        // Food earnings chart
        foodOverall.value = [
          {
            name: t('food_earnings'),
            data: response.data.earnings_chart.values,
          },
        ];
        foodEarningsChartOptions.value = {
          chart: {
            height: 100,
            type: "area",
            toolbar: "false",
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            curve: "smooth",
            width: 3,
          },
          xaxis: {
            categories: response.data.earnings_chart.months,
          },
          yaxis: {
            labels: {
              formatter: function (value) {
                return value.toFixed(1);
              },
            },
            tickAmount: 5,
            min: 0,
            max: Math.max(...response.data.earnings_chart.values) * 1.1,
          },
          colors: getChartColorsArray('["--vz-warning"]'),
          fill: {
            opacity: 0,
            colors: ["#f59e0b", "#F06548"],
            type: "solid",
          },
        };
      } catch (error) {
        console.error(t('error_fetching_food_statistics'), error);
      }
    };

    // Fetch data for popular drop addresses
    const fetchPopularDropAddresses = async () => {
      try {
        const params = {
          service_location_id: selectedLocation.value
        };

        // Add date filters if they are set
        if (dropAddressDateFrom.value) {
          params.date_from = dropAddressDateFrom.value;
        }
        if (dropAddressDateTo.value) {
          params.date_to = dropAddressDateTo.value;
        }

        const response = await axios.get('/dashboard/popular-drop-addresses', {
          params: params
        });

        popularDropAddressesData.value = [{
          name: t('drop_off_count'),
          data: response.data.data,
        }];

        popularDropAddressesChartOptions.value = {
          chart: {
            height: 380,
            type: 'bar',
            toolbar: {
              show: false,
            },
            background: 'transparent',
          },
          plotOptions: {
            bar: {
              borderRadius: 8,
              horizontal: true,
              distributed: true,
              dataLabels: {
                position: 'top',
                style: {
                  colors: ['#6c757d'],
                  fontSize: '11px',
                  fontWeight: '500',
                },
                orientation: 'horizontal',
              },
            },
          },
          grid: {
            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--vz-border-color').trim() || '#f1f1f1',
            strokeDashArray: 3,
            xaxis: {
              lines: {
                show: true
              }
            },
            yaxis: {
              lines: {
                show: false
              }
            }
          },
          dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
              // Get the category label (drop address) for this bar
              const category = opts.w.config.xaxis.categories[opts.dataPointIndex];
              // Return the drop address name
              return category;
            },
            style: {
              colors: ['#6c757d'],
              fontSize: '10px',
              fontWeight: '500',
            },
            dropShadow: {
              enabled: false,
            },
            textAnchor: 'start',
            offsetX: 5,
          },
          xaxis: {
            categories: response.data.labels,
            labels: {
              style: {
                colors: getComputedStyle(document.documentElement).getPropertyValue('--vz-body-color').trim() || '#6c757d',
                fontSize: '11px',
                fontWeight: '500',
              },
            },
          },
          yaxis: {
            labels: {
              show: false,
            },
          },
          colors: getChartColorsArray('["--vz-primary", "--vz-success", "--vz-warning", "--vz-danger", "--vz-info", "--vz-secondary", "--vz-dark", "--vz-light", "--vz-primary", "--vz-success"]'),
          tooltip: {
            theme: 'dark',
            y: {
              formatter: function (val) {
                return val + " " + t('trips');
              },
            },
            style: {
              fontSize: '12px',
              fontFamily: 'Helvetica, Arial, sans-serif',
            },
          },
          states: {
            hover: {
              filter: {
                type: 'darken',
                value: 0.1,
              }
            }
          },
          responsive: [
            {
              breakpoint: 768,
              options: {
                chart: {
                  height: 300,
                },
                plotOptions: {
                  bar: {
                    borderRadius: 6,
                  }
                }
              }
            }
          ]
        };
      } catch (error) {
        console.error(t('error_fetching_popular_drop_addresses'), error);
      }
    };

    // Helper functions for statistics
    const getTotalDropOffs = () => {
      if (!popularDropAddressesData.value[0]?.data) return 0;
      return popularDropAddressesData.value[0].data.reduce((sum, val) => sum + val, 0);
    };

    // Clear date filters
    const clearDateFilters = () => {
      dropAddressDateFrom.value = '';
      dropAddressDateTo.value = '';
      fetchPopularDropAddresses();
    };

    const getUniqueLocations = () => {
      if (!popularDropAddressesData.value[0]?.data) return 0;
      return popularDropAddressesData.value[0].data.length;
    };

    const getTopLocation = () => {
      if (!popularDropAddressesData.value[0]?.data?.length) return t('no_data');
      const maxIndex = popularDropAddressesData.value[0].data.indexOf(Math.max(...popularDropAddressesData.value[0].data));
      return popularDropAddressesChartOptions.value.xaxis?.categories?.[maxIndex] || t('no_data');
    };

    // Print chart function
    const printChart = () => {
      try {
        // Create a simple print window with chart data
        const data = popularDropAddressesData.value[0]?.data || [];
        const categories = popularDropAddressesChartOptions.value.xaxis?.categories || [];

        let chartHTML = '<div style="margin: 20px 0; text-align: left;">';
        chartHTML += '<h3 style="margin-bottom: 15px;">Drop Addresses Statistics</h3>';

        for (let i = 0; i < Math.min(data.length, categories.length); i++) {
          const percentage = data.length > 0 ? ((data[i] / Math.max(...data)) * 100).toFixed(1) : 0;
          chartHTML += `
            <div style="margin-bottom: 10px;">
              <div style="display: flex; align-items: center; margin-bottom: 5px;">
                <span style="min-width: 200px; font-size: 12px;">${categories[i]}</span>
                <span style="margin-left: 10px; font-weight: bold;">${data[i]} trips</span>
              </div>
              <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #007bff, #0056b3); width: ${percentage}%; height: 100%; border-radius: 10px;"></div>
              </div>
            </div>
          `;
        }

        chartHTML += '</div>';

        const printContent = `<!DOCTYPE html>
<html>
<head>
  <title>${t('popular_drop_addresses')}</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', 'Poppins', sans-serif;
      margin: 20px;
      text-align: center;
    }
    h1 {
      color: #333;
      margin-bottom: 10px;
      font-weight: 600;
    }
    .subtitle {
      color: #666;
      margin-bottom: 30px;
      font-size: 14px;
    }
    .stats-container {
      display: flex;
      justify-content: space-around;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    .stat-card {
      text-align: center;
      margin: 10px;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      min-width: 150px;
    }
    .stat-title {
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
      font-weight: 600;
    }
    .stat-value {
      color: #666;
      font-size: 18px;
    }
    .footer {
      margin-top: 30px;
      font-size: 12px;
      color: #999;
    }
    @media print {
      body { margin: 10px; }
    }
  </style>
</head>
<body>
  <h1>${t('popular_drop_addresses')}</h1>
  <div class="subtitle">${t('top_drop_locations_subtitle')}</div>

  ${chartHTML}

  <div class="stats-container">
    <div class="stat-card">
      <div class="stat-title">${t('total_drop_offs')}</div>
      <div class="stat-value">${getTotalDropOffs()}</div>
    </div>
    <div class="stat-card">
      <div class="stat-title">${t('unique_locations')}</div>
      <div class="stat-value">${getUniqueLocations()}</div>
    </div>
    <div class="stat-card">
      <div class="stat-title">${t('top_location')}</div>
      <div class="stat-value">${getTopLocation()}</div>
    </div>
  </div>

  <div class="footer">
    ${t('generated_on')}: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
  </div>

  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        window.onafterprint = function() {
          window.close();
        };
      }, 500);
    };
  <\/script>
</body>
</html>`;

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
          alert('Please allow popups for this site to use the print feature.');
          return;
        }

        printWindow.document.write(printContent);
        printWindow.document.close();

      } catch (error) {
        console.error('Error in printChart:', error);
        alert('Unable to generate print content. Please try again.');
      }
    };

    return {
      series,
      chartOptions,
      overall,
      sosRequests,
      overallChartOptions,
      cancellation,
      cancelChartOptions,
      seriesOverallTrip,
      chartOptionsOverallTrip,
      todayEarnings,
      overallEarnings,
      totalDrivers,
      totalUsers,
      foodDriversCount,
      cancelledtrips,
      currencySymbol,
      agentEarningsChartOptions,
      agentEarnings,
      agentOverall,
      // Food statistics
      foodSeries,
      foodChartOptions,
      foodOverallSeries,
      foodOverallChartOptions,
      foodEarningsChartOptions,
      foodOverall,
      foodTodayEarnings,
      foodOverallEarnings,
      // Popular drop addresses
      popularDropAddressesData,
      popularDropAddressesChartOptions,
      getTotalDropOffs,
      getUniqueLocations,
      getTopLocation,
      printChart,
      fetchPopularDropAddresses,
      clearDateFilters,
      // Date filters
      dropAddressDateFrom,
      dropAddressDateTo,
      dropAddressDateConfig
    };
  },

  methods: {},
};
</script>


<template>
  <Layout>
    <Warning />
    <PageHeader :title="$t('dashboard')" :pageTitle="$t('dashboard')" />
        <BRow>
            <BCol xl="3" md="12">
              <BCard no-body class="card-animate">
                <BCardBody>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                        {{ $t("drivers_registered") }}
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <h5 class="text-success fs-14 mb-0">
                        <i class="ri-arrow-right-up-line fs-13 align-middle"></i>
                        100%
                      </h5>
                    </div>
                  </div>
                  <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                      <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                        {{ totalDrivers.total }}
                      </h4><br>
                      <!-- <Link href="/approved-drivers" class="text-decoration-underline">{{ $t("view_all") }}</Link> -->
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                      <span class="avatar-title bg-success-subtle rounded fs-3">
                        <i class="bx bx-user-circle text-success"></i>
                      </span>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>

            <BCol xl="3" md="12">
              <BCard no-body class="card-animate">
                <BCardBody>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                        {{ $t("approved_drivers") }}
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <h5 class="text-success fs-14 mb-0">
                        <i class="ri-arrow-right-up-line fs-13 align-middle"></i>
                         {{ totalDrivers.approve_percentage }} %
                      </h5>
                    </div>
                  </div>
                  <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                      <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                        {{ totalDrivers.approved }}
                      </h4>
                      <Link href="/approved-drivers" class="text-decoration-underline" v-if="permissions.includes('view-approved-drivers')">{{ $t("view_all") }}</Link>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                      <span class="avatar-title bg-info-subtle rounded fs-3">
                        <i class="bx bx-user-circle text-info"></i>
                      </span>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>

            <!-- <BCol xl="3" md="6">
              <BCard no-body class="card-animate">
                <BCardBody>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                        {{ $t("drivers_approval_waiting") }}
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <h5 class="text-danger fs-14 mb-0">
                        <i class="ri-arrow-right-down-line fs-13 align-middle"></i>
                         {{ totalDrivers.decline_percentage }} %
                      </h5>
                    </div>
                  </div>
                  <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                      <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                         {{ totalDrivers.declined }}
                      </h4>
                      <Link href="/pending-drivers" class="text-decoration-underline" v-if="permissions.includes('view-approval-pending-drivers')">{{ $t("view_all") }}</Link>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                      <span class="avatar-title bg-warning-subtle rounded fs-3">
                        <i class="bx bx-user-circle text-warning"></i>
                      </span>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol> -->

            <BCol xl="3" md="12">
              <BCard no-body class="card-animate">
                <BCardBody>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                        {{ $t("food_drivers") }}
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <h5 class="text-success fs-14 mb-0">
                        <i class="ri-arrow-right-up-line fs-13 align-middle"></i>
                        Active
                      </h5>
                    </div>
                  </div>
                  <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                      <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                         {{ foodDriversCount }}
                      </h4>
                      <Link href="/approved-drivers" class="text-decoration-underline" v-if="permissions.includes('view-approved-drivers')">{{ $t("view_all") }}</Link>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                      <span class="avatar-title bg-warning-subtle rounded fs-3">
                        <i class="bx bx-food-menu text-warning"></i>
                      </span>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>

            <BCol xl="3" md="12">
              <BCard no-body class="card-animate">
                <BCardBody>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                        {{ $t("users_registered") }}
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                    </div>
                  </div>
                  <div class="d-flex align-items-end justify-content-between mt-4">
                    <div>
                      <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                         {{ totalUsers }}
                      </h4>
                      <Link href="/users" class="text-decoration-underline" v-if="permissions.includes('view-users')">{{ $t("view_all") }}</Link>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                      <span class="avatar-title bg-primary-subtle rounded fs-3">
                        <i class="bx bx-user-circle text-primary"></i>
                      </span>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>

<!-- Notified sos -->
          <!-- <BRow>
            <BCard no-body>
              <div class="card-header align-items-center d-flex">
                  <h4 class="card-title mb-0 flex-grow-1">{{ $t("notified_sos") }}</h4>
              </div>
              <BCardBody>
                <BCol v-if="sosRequests.length>0">
                  <div class="table-responsive">
                      <table class="table align-middle position-relative table-nowrap">
                          <thead class="table-active">
                              <tr>
                                  <th scope="col"> {{$t("date")}}</th>
                                  <th scope="col"> {{$t("user_name")}}</th>
                                  <th scope="col"> {{$t("driver_name")}}</th>
                                  <th scope="col"> {{$t("user_type")}}</th>
                                  <th scope="col"> {{$t("action")}}</th>
                              </tr>
                          </thead>
                          <tbody v-if="sosRequests.length > 0">
                              <tr v-for="(result, index) in sosRequests" :key="index">
                                  <td>{{ result.date}}</td>
                                  <td>{{ result.userName}}</td>
                                  <td>{{ result.driverName }}</td>
                                  <td>
                                      <BBadge class="text-uppercase">{{ result.is_user ? $t('user') : $t('driver') }} </BBadge>
                                  </td>
                                  <td>
                                      <div class="dropdown">
                                          <a class="text-reset" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                              <span class="text-muted fs-18"><i class="mdi mdi-dots-vertical"></i></span>
                                          </a>
                                          <div class="dropdown-menu dropdown-menu-end">
                                              <Link class="dropdown-item" type="button" :href="`rides-request/view/${result.request_id}`" >
                                                  <i class=" bx bx-show-alt align-center text-muted me-2"></i>  {{$t("view")}}
                                              </Link>
                                          </div>
                                      </div>
                                  </td>
                                </tr>
                          </tbody>
                          <tbody v-else>
                              <tr>
                                  <td colspan="10" class="text-center">
                                      <img src="@assets/images/search-file.gif" alt="Loading..." style="width:100px" />
                                      <h5> {{$t("no_data_found")}}</h5>
                                  </td>
                              </tr>
                          </tbody>
                      </table>
                  </div>

                </BCol>
                <BCol xl="12" v-else>
                    <div class="mt-auto text-center">
                        <img src="@assets/images/search-file.gif" width="120"alt="no-data" class="img-fluid">
                        <h5>{{ $t("no_data_found") }}</h5>
                    </div>
                </BCol>
              </BCardBody>
            </BCard>
          </BRow> -->

<!-- Food Request Statistics -->
<BRow class="mt-4">
    <BCol xl="12">
      <h5 class="mb-3">{{ $t("food_request_statistics") }}</h5>
    </BCol>
    <BCol xl="6" md="12" lg="12">
          <BCard no-body class="card-height-100">
    <BCardHeader class="align-items-center d-flex py-0">
      <BCardTitle class="mb-0 flex-grow-1 p-3">{{ $t("today_food_requests") }}</BCardTitle>
    </BCardHeader>
    <BCardBody>
      <apexchart class="apex-charts" dir="ltr" height="219" :series="foodSeries" :options="foodChartOptions"></apexchart>

      <div class="table-responsive mt-3">
        <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
          <tbody class="border-0">
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-warning me-2"></i>{{ $t("completed_food_requests") }}
                </h4>
              </td>
            </tr>
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-danger me-2"></i>{{ $t("cancelled_food_requests") }}
                </h4>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </BCardBody>
  </BCard>
</BCol>

<BCol xl="6" md="12" lg="12">
<div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("today_food_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ foodTodayEarnings.total.toFixed(2) }} </h2>
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-food-menu text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("food_requests_by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ foodTodayEarnings.cash.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <!-- <div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("food_requests_by_wallet") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ foodTodayEarnings.wallet.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div> -->
</BCol>
</BRow>

<!-- Overall Food Requests Chart -->
<BRow>
  <BCol xl="12" md="12">
    <BCard no-body class="card-height-100">
      <BCardHeader class="align-items-center d-flex py-0">
        <BCardTitle class="mb-0 flex-grow-1 p-3">{{ $t("overall_food_requests") }}</BCardTitle>
      </BCardHeader>
      <BCardBody>
        <apexchart class="apex-charts" dir="ltr" height="219" :series="foodOverallSeries" :options="foodOverallChartOptions"></apexchart>
        <div class="table-responsive mt-3">
          <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
            <tbody class="border-0">
              <tr>
                <td>
                  <h4 class="text-truncate fs-14 fs-medium mb-0">
                    <i class="ri-stop-fill align-middle fs-18 text-warning me-2"></i>{{ $t("completed_food_requests") }}
                  </h4>
                </td>
              </tr>
              <tr>
                <td>
                  <h4 class="text-truncate fs-14 fs-medium mb-0">
                    <i class="ri-stop-fill align-middle fs-18 text-danger me-2"></i>{{ $t("cancelled_food_requests") }}
                  </h4>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>
  </BCol>
</BRow>

<!-- Food Earnings Chart -->
<BRow>
  <BCol xl="6" md="12" lg="12">
    <BCard no-body>
    <BCardBody class="p-0">
      <BRow class="g-0">
        <BCol xxl="12">
          <div class="">
            <BCardHeader class="align-items-center d-flex">
              <BCardTitle class="mb-0 flex-grow-1">{{ $t("food_earnings") }}</BCardTitle>
            </BCardHeader>
            <apexchart class="apex-charts" height="350" dir="ltr" :series="foodOverall" :options="foodEarningsChartOptions"></apexchart>
          </div>
        </BCol>
      </BRow>
    </BCardBody>
  </BCard>
  </BCol>
  <BCol xl="6" md="12" lg="12">
<div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("overall_food_earnings") }}</p>
                         <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ foodOverallEarnings.total.toFixed(2) }} </h2>
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-food-menu text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("food_earnings_by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ foodOverallEarnings.cash.toFixed(2) }} </h2>
                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
</BCol>
</BRow>
  <BRow>
    <BCol xl="6" md="12" lg="12">
          <BCard no-body class="card-height-100">
    <BCardHeader class="align-items-center d-flex py-0">
      <BCardTitle class="mb-0 flex-grow-1 p-3">{{ $t("today_trips") }}</BCardTitle>
    </BCardHeader>
    <BCardBody>
      <apexchart class="apex-charts" dir="ltr" height="219" :series="series" :options="chartOptions"></apexchart>

      <div class="table-responsive mt-3">
        <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
          <tbody class="border-0">
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-primary me-2"></i>{{ $t("completed_rides") }}
                </h4>
              </td>
            </tr>
            <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-warning me-2"></i>{{ $t("cancelled_rides") }}
                </h4>
              </td>
            </tr>
            <!-- <tr>
              <td>
                <h4 class="text-truncate fs-14 fs-medium mb-0">
                  <i class="ri-stop-fill align-middle fs-18 text-info me-2"></i>{{ $t("scheduled_rides") }}
                </h4>
              </td>
            </tr> -->
          </tbody>
        </table>
      </div>
    </BCardBody>
  </BCard>
</BCol>

<BCol xl="6" md="12" lg="12">
<div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("today_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.total.toFixed(2) }} </h2>
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.cash.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-success icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_wallet") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.wallet.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <!-- <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_card") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.card.toFixed(2) }} </h2>
                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-danger icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>  -->
  </div>
  <div class="row">
      <!-- <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("admin_commission") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.admin_commision.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-primary icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> -->

      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("drivers_earnings") }}</p>
                         <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ todayEarnings.driver_commision.toFixed(2) }} </h2>                                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-dark-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-dark icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
</Bcol>

<!-- Overall Trips Chart -->
<BCol xl="12" md="12">
    <BCard no-body class="card-height-100">
      <BCardHeader class="align-items-center d-flex py-0">
        <BCardTitle class="mb-0 flex-grow-1 p-3">{{ $t("overall_trips") }}</BCardTitle>
      </BCardHeader>
      <BCardBody>
        <apexchart class="apex-charts" dir="ltr" height="219" :series="seriesOverallTrip" :options="chartOptionsOverallTrip"></apexchart>
        <div class="table-responsive mt-3">
          <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
            <tbody class="border-0">
              <tr>
                <td>
                  <h4 class="text-truncate fs-14 fs-medium mb-0">
                    <i class="ri-stop-fill align-middle fs-18 text-secondary me-2"></i>{{ $t("completed_rides") }}
                  </h4>
                </td>
              </tr>
              <tr>
                <td>
                  <h4 class="text-truncate fs-14 fs-medium mb-0">
                    <i class="ri-stop-fill align-middle fs-18 text-danger me-2"></i>{{ $t("cancelled_rides") }}
                  </h4>
                </td>
              </tr>
              <!-- <tr>
                <td>
                  <h4 class="text-truncate fs-14 fs-medium mb-0">
                    <i class="ri-stop-fill align-middle fs-18 text-success me-2"></i>{{ $t("scheduled_rides") }}
                  </h4>
                </td>
              </tr> -->
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>
  </BCol>
</BRow>


<!-- total earnings -->

<BRow>
  <BCol xl="6" md="12" lg="12">
    <BCard no-body>
    <BCardBody class="p-0">
      <BRow class="g-0">
        <BCol xxl="12">
          <div class="">
            <BCardHeader class="align-items-center d-flex">
              <BCardTitle class="mb-0 flex-grow-1">{{ $t("overall_earnings") }}</BCardTitle>
            </BCardHeader>
            <apexchart class="apex-charts" height="350" dir="ltr" :series="overall" :options="overallChartOptions"></apexchart>
          </div>
        </BCol>
      </BRow>
    </BCardBody>
  </BCard>
  </BCol>
  <BCol xl="6" md="12" lg="12">
<div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("overall_earnings") }}</p>
                         <h2 class="mt-4 ff-secondary fw-semibold">
                              <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.total.toFixed(2) }} </h2>
                            </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-danger icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_cash") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.cash.toFixed(2) }} </h2>
                           </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
  </div>
  <div class="row">
      <div class="col-md-12">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_wallet") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.wallet.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-success icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

      <!-- <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("by_card") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.card.toFixed(2) }} </h2>
                          </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> -->
  </div>
  <div class="row">
      <!-- <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("admin_commission") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.admin_commision.toFixed(2) }} </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-dark-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-dark icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> -->

      <!-- <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("drivers_earnings") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                          <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                             {{ overallEarnings.driver_commision.toFixed(2) }} </h2>
                          </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                          <i class="bx bx-money text-primary icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> -->
  </div>
</Bcol>
</BRow>


<!-- Cancellation Chart -->
<BRow>
  <BCol xl="6" md="12" lg="12">
    <BCard no-body>
    <BCardBody class="p-0">
      <BRow class="g-0">
        <BCol xxl="12">
          <div class="">
            <BCardHeader class="align-items-center d-flex">
              <BCardTitle class="mb-0 flex-grow-1">{{ $t("cancellation_chart") }}</BCardTitle>
            </BCardHeader>
            <apexchart class="apex-charts" height="350" dir="ltr" :series="cancellation" :options="cancelChartOptions"></apexchart>
          </div>
        </BCol>
      </BRow>
    </BCardBody>
  </BCard>
  </BCol>
  <BCol xl="6" md="12" lg="12">
<div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("total_request_cancelled") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                                {{ cancelledtrips.total_cancelled }}
                          </h2>
                      </div>
                      <div>
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                          <i class="bx bx-user text-success icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
<!--
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("cancelled_due_to_no_drivers") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                                {{ cancelledtrips.auto_cancelled }}
                          </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                          <i class="bx bx-user text-danger icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> -->
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("cancelled_by_users") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                                {{ cancelledtrips.user_cancelled }}
                          </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                          <i class="bx bx-user text-warning icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->

    </div>
  <div class="row">
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("cancelled_by_drivers") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                                {{ cancelledtrips.driver_cancelled }}
                          </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-user text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->
      <div class="col-md-6">
          <div class="card card-animate">
              <div class="card-body">
                  <div class="d-flex justify-content-between">
                      <div>
                          <p class="fw-medium text-muted mb-0">{{ $t("dispatcher_cancelled") }}</p>
                          <h2 class="mt-4 ff-secondary fw-semibold">
                                {{ cancelledtrips.dispatcher_cancelled }}
                          </h2>
                      </div>
                      <div>
                        <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                          <i class="bx bx-user text-info icon-lg"></i>
                        </span>
                      </div>
                      </div>
                  </div>
              </div><!-- end card body -->
          </div> <!-- end card-->
      </div> <!-- end col-->


  </div>
</Bcol>
</BRow>

<!-- Popular Drop Addresses Chart -->
<BRow class="mt-4">
  <BCol xl="12" md="12">
    <BCard no-body class="card-animate">
      <BCardHeader class="align-items-center d-flex justify-content-between border-bottom bg-light-subtle">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0 me-3">
            <div class="avatar-sm bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center">
              <i class="bx bx-map-pin text-white fs-4"></i>
            </div>
          </div>
          <div class="flex-grow-1">
            <BCardTitle class="mb-0 fs-16 fw-semibold">{{ $t("popular_drop_addresses") }}</BCardTitle>
            <p class="text-muted mb-0 fs-12">{{ $t("top_drop_locations_subtitle") }}</p>
          </div>
        </div>
        <div class="flex-shrink-0 d-flex align-items-center gap-2">
          <!-- Date Filters -->
          <div class="d-flex align-items-center gap-2">
            <div class="date-filter-wrapper">
              <flatPickr
                v-model="dropAddressDateFrom"
                :config="dropAddressDateConfig"
                class="form-control form-control-sm"
                :placeholder="$t('from_date')"
              />
            </div>
            <div class="date-filter-wrapper">
              <flatPickr
                v-model="dropAddressDateTo"
                :config="dropAddressDateConfig"
                class="form-control form-control-sm"
                :placeholder="$t('to_date')"
              />
            </div>
            <BButton
              variant="outline-secondary"
              size="sm"
              @click="clearDateFilters"
              :title="$t('clear_filters')"
            >
              <i class="bx bx-x"></i>
            </BButton>
          </div>
          <BDropdown toggle-class="btn-sm" menu-class="dropdown-menu-end">
            <template #button-content>
              <i class="bx bx-dots-vertical-rounded"></i>
            </template>
            <BDropdownItem @click="printChart">
              <i class="bx bx-printer me-2"></i>
              {{ $t("print") }}
            </BDropdownItem>
            <BDropdownItem @click="fetchPopularDropAddresses">
              <i class="bx bx-refresh me-2"></i>
              {{ $t("refresh") }}
            </BDropdownItem>
          </BDropdown>
        </div>
      </BCardHeader>
      <BCardBody class="p-4">
        <div class="chart-container" style="position: relative;">
          <apexchart
            id="popularDropAddressesChart"
            class="apex-charts"
            dir="ltr"
            height="380"
            :series="popularDropAddressesData"
            :options="popularDropAddressesChartOptions"
          ></apexchart>
        </div>
        <div class="mt-3 text-center">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="card border-0 bg-light-subtle rounded-3">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <div class="avatar-sm bg-success bg-gradient rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-trending-up text-white fs-5"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <h6 class="mb-0 fw-semibold">{{ $t("total_drop_offs") }}</h6>
                      <p class="text-muted mb-0 fs-12">{{ getTotalDropOffs() }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 bg-light-subtle rounded-3">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <div class="avatar-sm bg-info bg-gradient rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-map text-white fs-5"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <h6 class="mb-0 fw-semibold">{{ $t("unique_locations") }}</h6>
                      <p class="text-muted mb-0 fs-12">{{ getUniqueLocations() }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 bg-light-subtle rounded-3">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <div class="avatar-sm bg-warning bg-gradient rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-star text-white fs-5"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <h6 class="mb-0 fw-semibold">{{ $t("top_location") }}</h6>
                      <p class="text-muted mb-0 fs-12 text-truncate">{{ getTopLocation() }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BCardBody>
    </BCard>
  </BCol>
</BRow>

<!-- agent earnings -->

  <!-- <BRow v-if="agent_addons == 1">
    <BCol xl="6" md="12" lg="12">
      <BCard no-body>
        <BCardBody class="p-0">
          <BRow class="g-0">
            <BCol xxl="12">
              <div class="">
                <BCardHeader class="align-items-center d-flex">
                  <BCardTitle class="mb-0 flex-grow-1">{{ $t("agents_earnings") }}</BCardTitle>
                </BCardHeader>
                <apexchart class="apex-charts" height="350" dir="ltr" :series="agentOverall" :options="agentEarningsChartOptions"></apexchart>
              </div>
            </BCol>
         </BRow>
        </BCardBody>
      </BCard>
    </BCol>
    <BCol xl="6" md="12" lg="12">
      <div class="row">
        <div class="col-md-6 col-lg-6 col-xl-12">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="fw-medium text-muted mb-0">{{ $t("agent_overall_earnings") }}</p>
                  <h2 class="mt-4 ff-secondary fw-semibold">
                    <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                    {{ agentEarnings.agent_overall_earnngs?.total}}
                  </h2>
                </div>
                <div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                      <i class="bx bx-money text-danger icon-lg"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-6 col-xl-12">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="fw-medium text-muted mb-0">{{ $t("agent_today_earnings") }}</p>
                  <h2 class="mt-4 ff-secondary fw-semibold">
                    <span class="counter-value" data-target="97.66">{{currencySymbol}}</span>
                    {{ agentEarnings.agent_today_earnings?.total}}
                  </h2>
                </div>
                <div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                      <i class="bx bx-money text-warning icon-lg"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
</Bcol>
</BRow> -->
  </Layout>
</template>

<style scoped>
.date-filter-wrapper {
  min-width: 150px;
}

.date-filter-wrapper .form-control {
  font-size: 0.875rem;
  padding: 0.375rem 0.75rem;
}

@media (max-width: 768px) {
  .date-filter-wrapper {
    min-width: 120px;
  }

  .date-filter-wrapper .form-control {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
