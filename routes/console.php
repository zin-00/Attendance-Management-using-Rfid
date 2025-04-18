<?php

use App\Console\Commands\InitialRecordCommand;
use App\Jobs\InitializeAttendanceJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::job(new InitializeAttendanceJob());