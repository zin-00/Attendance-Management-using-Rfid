<?php

namespace App\Providers;

use App\Jobs\InitializeAttendanceJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ScheduleProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new InitializeAttendanceJob())
                ->dailyAt('01:36')
                ->timezone('Asia/Manila')
                ->name('midnight-attendance-init')
                ->before(function () {
                    Log::info('Starting midnight attendance prepopulation');
                })
                ->after(function () {
                    Log::info('Completed midnight attendance prepopulation');
                })
                ->onFailure(function () {
                    Log::error('Midnight attendance job failed!');
                    // Add notification here if needed
                });
        });
    }
}
