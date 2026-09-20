<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\ChangeDriversToTrips;
use App\Console\Commands\UnlockAccount;
use App\Console\Commands\OfflineUnAvailableDrivers;
use App\Console\Commands\NotifyDriverDocumentExpiry;
use App\Console\Commands\AssignDriversForScheduledRides;
use App\Console\Commands\AssignDriversForRegularRides;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ClearDemoDatabase;
use App\Console\Commands\ClearRequestTable;
use App\Console\Commands\ClearOtp;
use App\Console\Commands\CancelRequests;
use App\Console\Commands\ExpireSubscription;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ChangeDriversToTrips::class,
        NotifyDriverDocumentExpiry::class,
        AssignDriversForScheduledRides::class,
        OfflineUnAvailableDrivers::class,
        AssignDriversForRegularRides::class,
        ClearDemoDatabase::class,
        ClearRequestTable::class,
        ExpireSubscription::class,
        ClearOtp::class,
        CancelRequests::class,
        UnlockAccount::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // withoutOverlapping() on the per-minute tasks: each run used to be able
        // to start while the previous one was still going, so a slow run under
        // load piled up processes and double-assigned drivers. The lock uses the
        // cache, so CACHE_DRIVER must not be "array".
         $schedule->command('drivers:totrip')
                 ->everyMinute()
                 ->withoutOverlapping(5);
         $schedule->command('assign_drivers:for_regular_rides')
                 ->everyMinute()
                 ->withoutOverlapping(5);
         $schedule->command('assign_drivers:for_schedule_rides')
                 ->everyFiveMinutes()
                 ->withoutOverlapping(10);
         $schedule->command('offline:drivers')
                 ->everyFiveMinutes()
                 ->withoutOverlapping(10);
         $schedule->command('notify:document:expires')
                 ->daily();
        $schedule->command('expire:subscription')
                 ->everyFiveMinutes()
                 ->withoutOverlapping(10);
         $schedule->command('clear:otp')
                 ->everyFiveMinutes()
                 ->withoutOverlapping(10);
         $schedule->command('cancel:request')
                 ->everyMinute()
                 ->withoutOverlapping(5);
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
