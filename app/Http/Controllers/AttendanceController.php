<?php

namespace App\Http\Controllers;

use App\Events\AttendanceEvent;
use App\Models\Attendance;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    
        return Inertia::render('Attendances/Index', [
            'attendance' => $attendance
        ]);
    }

    public function record()
    {
        $today = now()->toDateString();
        
        // Get paginated results (15 per page by default)
        $summaries = AttendanceSummary::with('employee')
            ->whereDate('date', $today)
            ->orderBy('date', 'desc')
            ->paginate()
            ->through(function($summary) {
                return [
                    'id' => $summary->id,
                    'employee_id' => $summary->employee_id,
                    'employee_name' => $summary->employee->first_name . ' ' . $summary->employee->last_name,
                    'date' => $summary->date->format('Y-m-d'),
                    'morning_status' => $summary->morning_status,
                    'afternoon_status' => $summary->afternoon_status,
                    'evening_status' => $summary->evening_status,
                    'final_status' => $summary->final_status,
                    'total_work_hours' => $summary->total_work_hours
                ];
            });
    
        return Inertia::render('Attendances/Records', [
            'summaries' => $summaries,
            'date' => $today
        ]);
    }

    public function history(Request $request)
    {
        return Inertia::render('Attendances/AttendanceHistory', [
            'initialFilters' => [
                'status' => $request->query('status', 'all'),
                'date' => $request->query('date'),
            ]
        ]);
    }

    public function fetch_history(Request $request)
    {
        $query = Attendance::with('employee');
        
        $request->validate([
            'month'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'        => ['nullable', 'integer', 'min:2000', 'max:' . date('Y')],
            'search'      => ['nullable', 'string', 'max:255'],
            'status'      => ['nullable', 'string', 'in:present,late,absent,all'], 
            'type'        => ['nullable', 'string', 'in:check-in,check-out,all'], 
            'time_of_day' => ['nullable', 'string', 'in:morning,afternoon,evening,all'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        
        if ($request->filled('month') || $request->filled('year')) {
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            
            $query->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
        }
        
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
        }
        
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(LOWER(first_name), ' ', LOWER(last_name)) LIKE ?", ["%".strtolower($search)."%"])
                  ->orWhereRaw("LOWER(first_name) LIKE ?", ["%".strtolower($search)."%"])
                  ->orWhereRaw("LOWER(last_name) LIKE ?", ["%".strtolower($search)."%"]);
            });
        }
        
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('time_of_day') && $request->time_of_day !== 'all') {
            switch ($request->time_of_day) {
                case 'morning':
                    $query->whereRaw('HOUR(created_at) >= 0 AND HOUR(created_at) < 12');
                    break;
                case 'afternoon':
                    $query->whereRaw('HOUR(created_at) >= 12 AND HOUR(created_at) < 17');
                    break;
                case 'evening':
                    $query->whereRaw('HOUR(created_at) >= 17 AND HOUR(created_at) < 24');
                    break;
            }
        }
        
        $query->orderBy('created_at', 'desc');
        
        return $query->paginate($request->input('per_page', 10));
    }
    
    public function fetch_attendance()
    {
        $today = Carbon::today()->toDateString();
        return Attendance::whereDate('created_at', $today)
            ->with('employee')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'rfid_tag' => ['required', 'string', 'exists:employees,rfid_tag'],
            ]);
    
            $employee = Employee::where('rfid_tag', $request->rfid_tag)->firstOrFail();
    
            $current_time = now()->setTimezone('Asia/Manila');
            $time = $current_time->format('H:i');
            $date = $current_time->toDateString();
    
            $schedule = Schedule::where('isSet', true)->firstOrFail();
    
            $scan_type = $this->determineScanType($time, $schedule);
            if ($scan_type === 'UNKNOWN') {
                return response()->json([
                    'error' => 'Restricted to scan. Your scan time does not match the schedule.'
                ], 400);
            }
            
            if ($this->checkForDuplicateScan($employee->id, $date, $scan_type, $time)) {
                return response()->json([
                    'warning' => 'Duplicate scan detected. Please wait before scanning again.'
                ], 400);
            }
    
            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'date' => $date
            ]);
            
            if (!$attendance->exists) {
                $attendance->fill([
                    'day_type' => $this->determineDayType($current_time),
                    'status' => 'Absent',
                ]);
            }
            
            $scan_field = $this->determineScanField($scan_type, $time, $schedule);
            
            if ($scan_field) {
                $attendance->$scan_field = $time;
                $attendance->status = $this->determineAttendanceStatus($attendance, $schedule);
            }
    
            $attendance->work_hours = $this->calculateWorkHours($attendance);
            $attendance->save();
    
            AttendanceEvent::dispatch($attendance);

            return response()->json([
                'message' => 'Attendance recorded successfully for ' . $employee->first_name,
                'attendance' => $attendance->load('employee'),
                'formatted_datetime' => $current_time->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper methods
    private function determineScanType($time, Schedule $schedule)
    {
        $time = Carbon::parse($time);
        $morning_in = Carbon::parse($schedule->morning_in);
        $morning_out = Carbon::parse($schedule->morning_out);
        $afternoon_in = Carbon::parse($schedule->afternoon_in);
        $afternoon_out = Carbon::parse($schedule->afternoon_out);
    
        $morning_in_range = [$morning_in->copy()->subMinutes(30), $morning_in->copy()->addMinutes(30)];
        $morning_out_range = [$morning_out->copy()->subMinutes(30), $morning_out->copy()->addMinutes(30)];
        $afternoon_in_range = [$afternoon_in->copy()->subMinutes(30), $afternoon_in->copy()->addMinutes(30)];
        $afternoon_out_range = [$afternoon_out->copy()->subMinutes(30), $afternoon_out->copy()->addMinutes(30)];
    
        if ($time->between(...$morning_in_range)) return 'IN';
        if ($time->between(...$morning_out_range)) return 'OUT';
        if ($time->between(...$afternoon_in_range)) return 'IN';
        if ($time->between(...$afternoon_out_range)) return 'OUT';
    
        return 'UNKNOWN';
    }
    
    private function determineScanField($scan_type, $time, Schedule $schedule)
    {
        $time = Carbon::parse($time);
        $morning_in = Carbon::parse($schedule->morning_in);
        $morning_out = Carbon::parse($schedule->morning_out);
        $afternoon_in = Carbon::parse($schedule->afternoon_in);
        $afternoon_out = Carbon::parse($schedule->afternoon_out);
    
        $morning_in_range = [$morning_in->copy()->subMinutes(30), $morning_in->copy()->addMinutes(30)];
        $morning_out_range = [$morning_out->copy()->subMinutes(30), $morning_out->copy()->addMinutes(30)];
        $afternoon_in_range = [$afternoon_in->copy()->subMinutes(30), $afternoon_in->copy()->addMinutes(30)];
        $afternoon_out_range = [$afternoon_out->copy()->subMinutes(30), $afternoon_out->copy()->addMinutes(30)];
    
        if ($scan_type === 'IN') {
            if ($time->between(...$morning_in_range)) return 'morning_in';
            if ($time->between(...$afternoon_in_range)) return 'afternoon_in';
        } else if ($scan_type === 'OUT') {
            if ($time->between(...$morning_out_range)) return 'lunch_out';
            if ($time->between(...$afternoon_out_range)) return 'afternoon_out';
        }
    
        return null;
    }
    
    private function determineAttendanceStatus(Attendance $attendance, Schedule $schedule)
    {
        if ($attendance->morning_in || $attendance->afternoon_in) {
            $morning_late = $attendance->morning_in && 
                Carbon::parse($attendance->morning_in)->gt(Carbon::parse($schedule->morning_in)->addMinutes(15));
            
            $afternoon_late = $attendance->afternoon_in && 
                Carbon::parse($attendance->afternoon_in)->gt(Carbon::parse($schedule->afternoon_in)->addMinutes(15));
    
            return ($morning_late || $afternoon_late) ? 'Late' : 'Present';
        }
    
        return 'Absent';
    }
    
    private function calculateWorkHours(Attendance $attendance)
    {
        $workMinutes = 0;
    
        if ($attendance->morning_in && $attendance->lunch_out) {
            $workMinutes += Carbon::parse($attendance->morning_in)
                ->diffInMinutes(Carbon::parse($attendance->lunch_out));
        }
    
        if ($attendance->afternoon_in && $attendance->afternoon_out) {
            $workMinutes += Carbon::parse($attendance->afternoon_in)
                ->diffInMinutes(Carbon::parse($attendance->afternoon_out));
        }
    
        return round($workMinutes / 60, 2);
    }
    
    private function checkForDuplicateScan($employee_id, $date, $scan_type, $time)
    {
        $attendance = Attendance::where('employee_id', $employee_id)
            ->whereDate('created_at', $date)
            ->first();

        if (!$attendance) return false;

        $scan_fields = [
            'IN' => ['morning_in', 'afternoon_in'],
            'OUT' => ['lunch_out', 'afternoon_out']
        ];

        foreach ($scan_fields[$scan_type] as $field) {
            if ($attendance->$field) {
                $lastScanTime = Carbon::parse($attendance->$field);
                $currentScanTime = Carbon::parse($time);

                if ($lastScanTime->diffInMinutes($currentScanTime) < 5) {
                    return true;
                }
            }
        }

        return false;
    }

    private function determineDayType($date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        
        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
            return 'Weekend';
        }

        $holidays = ['2025-01-01', '2025-12-25']; 
        if (in_array($date->toDateString(), $holidays)) {
            return 'Holiday';
        }

        return 'Regular';
    }

    public function get_monthly_attendances(Request $request)
    {
        $request->validate([
            'month' => ['required', 'date_format:Y-m']
        ]);
        
        $startDate = Carbon::parse($request->month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $attendances = Attendance::whereBetween('created_at', [$startDate, $endDate])
            ->with('employee')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($records, $employeeId) use ($totalDays) {
                $employee = $records->first()->employee ?? (object) ['id' => null, 'first_name' => 'Unknown', 'last_name' => ''];
    
                $daysPresent = $records->where('status', 'Present')->count();
                $daysAbsent = max(0, $totalDays - $daysPresent);
    
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'days_present' => $daysPresent,
                    'days_absent' => $daysAbsent,
                    'attendance_rate' => round(($daysPresent / $totalDays) * 100, 2),
                    'working_days' => $totalDays
                ];
            })->values();
    
        return response()->json($attendances);
    }
    
    public function attendance_summary()
    {
        $totalEmployees = Employee::count();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
    
        $weeklyPresent = Attendance::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->distinct('employee_id')
            ->count('employee_id');
    
        $monthlyPresent = Attendance::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->distinct('employee_id')
            ->count('employee_id');
    
        return response()->json([
            'weeklyPresent' => $weeklyPresent,
            'weeklyAbsent' => $totalEmployees - $weeklyPresent,
            'monthlyPresent' => $monthlyPresent,
            'monthlyAbsent' => $totalEmployees - $monthlyPresent,
        ]);
    }

    public function getMonthlyAttendance()
    {
        $year = Carbon::now()->year;

        $attendanceData = Attendance::selectRaw('MONTH(created_at) as month, 
                SUM(CASE WHEN status = "Present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "Absent" THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = "Late" THEN 1 ELSE 0 END) as late')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyData = array_fill(1, 12, ['present' => 0, 'absent' => 0, 'late' => 0]);
        
        foreach ($attendanceData as $data) {
            $monthlyData[$data->month] = [
                'present' => $data->present,
                'absent' => $data->absent,
                'late' => $data->late,
            ];
        }

        return response()->json($monthlyData);
    }
}