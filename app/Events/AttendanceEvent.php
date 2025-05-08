<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\AttendanceSummary;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AttendanceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendance;
    public $summary;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance->load('employee');
        $this->summary = $this->updateAttendanceSummary($attendance);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('attendance'),
            // new Channel('attendance.' . $this->attendance->employee_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'attendance' => $this->attendance,
            'summary' => $this->summary,
            'employee' => $this->attendance->employee,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    // for updating the x mark into check in each swipe
    protected function updateAttendanceSummary(Attendance $attendance): AttendanceSummary
    {
        $date = Carbon::parse($attendance->date)->format('Y-m-d');

        $summary = AttendanceSummary::firstOrCreate(
            [
                'employee_id' => $attendance->employee_id,
                'date' => $date,
            ],
            [
                'morning_status' => 'x',
                'afternoon_status' => 'x',
                'evening_status' => 'x',
                'final_status' => 'Pending',
                'total_work_hours' => 0,
                'is_manual_edit' => false,
            ]
        );

        Log::info("[Summary] BEFORE: {$summary->morning_status}, {$summary->afternoon_status}, {$summary->evening_status}");

        if (!empty($attendance->morning_in)) {
            $summary->morning_status = '✓';
        }

        if (!empty($attendance->afternoon_in)) {
            $summary->afternoon_status = '✓';
        }

        if (!empty($attendance->evening_in)) {
            $summary->evening_status = '✓';
        }

        $summary->final_status = $attendance->status;
        $summary->total_work_hours = $attendance->work_hours;

        $summary->save();

        Log::info("[Summary] UPDATED: {$summary->morning_status}, {$summary->afternoon_status}, {$summary->evening_status}");

        return $summary;
    }
}
