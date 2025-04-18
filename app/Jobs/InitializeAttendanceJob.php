<?php

namespace App\Jobs;

use App\Models\AttendanceSummary;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InitializeAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $date;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $today = now()->toDateString();
        Log::info("[InitializeAttendanceJob] Starting midnight prepopulation for {$today}");

        // Get all active employees
        $employees = Employee::all();
        Log::info("Total employees found: " . $employees->count());

        $processed = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            try {
                // Use DB::table for direct insertion without model events
                $result = DB::table('attendance_summaries')->updateOrInsert(
                    [
                        'employee_id' => $employee->id,
                        'date' => $today
                    ],
                    [
                        'morning_status' => 'x',
                        'afternoon_status' => 'x',
                        'evening_status' => 'x',
                        'final_status' => 'Pending',
                        'total_work_hours' => 0,
                        'is_manual_edit' => false,
                        'remarks' => 'Auto-initialized',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                $result ? $processed++ : $skipped++;
                Log::debug("Processed employee {$employee->id}");
            } catch (\Exception $e) {
                Log::error("Failed employee {$employee->id}: " . $e->getMessage());
                continue;
            }
        }

        Log::info("Prepopulation completed. Processed: {$processed}, Skipped: {$skipped}");

        // Final verification
        $createdCount = DB::table('attendance_summaries')
                        ->whereDate('date', $today)
                        ->count();
        
        Log::info("Verification: {$createdCount} records exist for {$today}");
        Log::info("Expected: {$employees->count()} employees in system");
    }
}
