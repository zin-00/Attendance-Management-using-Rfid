<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\DailyWorkSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index(){
        return Inertia::render('Attendances/Attendance');
    }
    public function history(Request $request){
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
    
    // Filter by attendance status
    if ($request->filled('status') && $request->status !== 'all') {
        $query->where('status', $request->status);
    }
    
    // Filter by attendance type (check-in/check-out)
    if ($request->filled('type') && $request->type !== 'all') {
        $query->where('type', $request->type);
    }
    
    // Filter by time of day
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
    
    $attendances = $query->paginate($request->input('per_page', 10));
    
    return response()->json($attendances);
}

    
    public function fetch_attendance(){
        $today = Carbon::today()->toDateString();
        $attendances = Attendance::whereDate('created_at', $today)->with('employee')->paginate(10);
        return response()->json(
           $attendances
        );
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'rfid_tag' => ['required', 'string', 'exists:employees,rfid_tag'],
            ]);

            $employee = Employee::where('rfid_tag', $request->rfid_tag)->first();
            if (!$employee) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            $current_time = now()->setTimezone('Asia/Manila');
            $time = $current_time->format('H:i');
            $date = $current_time->toDateString();

            $scan_type = $this->determineScanType($time);

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

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('created_at', $date)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'day_type' => $this->determineDayType($current_time),
                    'status' => 'Absent',
                ]);
            }
            
            $scan_field = $this->determineScanField($scan_type, $time);
            
            if ($scan_field) {
                $attendance->$scan_field = $time;
                
                $attendance->status = $this->determineAttendanceStatus($attendance);
            }

            $attendance->save();

            $attendance->work_hours = $this->calculateWorkHours($attendance);
            $attendance->save();

            return response()->json([
                'message' => 'Attendance recorded successfully for ' . $employee->first_name,
                'attendance' => $attendance->load('employee'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function determineScanType($time)
    {
        // Morning Shift
        if ($time >= '06:30' && $time <= '08:30') return 'IN';
        if ($time >= '11:30' && $time <= '12:30') return 'OUT';
        
        // Afternoon Shift
        if ($time >= '12:30' && $time <= '14:00') return 'IN';
        if ($time >= '16:30' && $time <= '18:00') return 'OUT';
        
        // Evening Shift
        if ($time >= '19:00' && $time <= '20:30') return 'IN';
        if ($time >= '23:30' && $time <= '24:00') return 'OUT';

        return 'UNKNOWN';
    }

    private function determineScanField($scan_type, $time)
    {
        // Morning Shift
        if ($scan_type === 'IN' && $time >= '06:30' && $time <= '08:30') return 'morning_in';
        if ($scan_type === 'OUT' && $time >= '11:30' && $time <= '12:30') return 'lunch_out';
        
        // Afternoon Shift
        if ($scan_type === 'IN' && $time >= '12:30' && $time <= '14:00') return 'afternoon_in';
        if ($scan_type === 'OUT' && $time >= '16:30' && $time <= '18:00') return 'afternoon_out';
        
        // Evening Shift
        if ($scan_type === 'IN' && $time >= '19:00' && $time <= '20:30') return 'evening_in';
        if ($scan_type === 'OUT' && $time >= '23:30' && $time <= '24:00') return 'evening_out';

        return null;
    }

    private function determineAttendanceStatus(Attendance $attendance)
    {
        if ($attendance->morning_in || $attendance->afternoon_in || $attendance->evening_in) {
            $morning_late = $attendance->morning_in && Carbon::parse($attendance->morning_in)->format('H:i') > '08:00';
            $afternoon_late = $attendance->afternoon_in && Carbon::parse($attendance->afternoon_in)->format('H:i') > '13:30';
            $evening_late = $attendance->evening_in && Carbon::parse($attendance->evening_in)->format('H:i') > '20:00';

            return ($morning_late || $afternoon_late || $evening_late) ? 'Late' : 'Present';
        }

        return 'Absent';
    }

    private function calculateWorkHours(Attendance $attendance)
    {
        $workMinutes = 0;

        // Morning work hours
        if ($attendance->morning_in && $attendance->lunch_out) {
            $workMinutes += Carbon::parse($attendance->morning_in)->diffInMinutes(Carbon::parse($attendance->lunch_out));
        }

        // Afternoon work hours
        if ($attendance->afternoon_in && $attendance->afternoon_out) {
            $workMinutes += Carbon::parse($attendance->afternoon_in)->diffInMinutes(Carbon::parse($attendance->afternoon_out));
        }

        // Evening work hours
        if ($attendance->evening_in && $attendance->evening_out) {
            $workMinutes += Carbon::parse($attendance->evening_in)->diffInMinutes(Carbon::parse($attendance->evening_out));
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
            'IN' => ['morning_in', 'afternoon_in', 'evening_in'],
            'OUT' => ['lunch_out', 'afternoon_out', 'evening_out']
        ];

        // Check for duplicate scans within 5 minutes for relevant fields
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

        // Holiday checkr
        $holidays = ['2025-01-01', '2025-12-25']; 
        if (in_array($date->toDateString(), $holidays)) {
            return 'Holiday';
        }

        return 'Regular';
    }
    // For Monthly attendance ni.
    public function get_monthly_attendances(Request $request)
    {
        $month = $request->query('month');
    
        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json(['error' => 'Invalid or missing month parameter. Format: YYYY-MM'], 400);
        }
    
        try {
            $startDate = Carbon::parse("$month-01")->startOfMonth();
            $endDate = Carbon::parse("$month-01")->endOfMonth();
            
            $totalDays = (int) $startDate->diffInDays($endDate) + 1;
    
            $attendance = Attendance::whereBetween('created_at', [$startDate, $endDate])
                ->with('employee')
                ->get();
    
            $groupedAttendance = $attendance->groupBy('employee_id')->map(function ($records, $employeeId) use ($totalDays) {
                $employee = $records->first()->employee ?? (object) ['id' => null, 'first_name' => 'Unknown', 'last_name' => ''];
    
                $daysPresent = (int) $records->where('status', 'Present')->count();
                $daysAbsent = max(0, $totalDays - $daysPresent);
    
                $attendanceRate = round(($daysPresent / $totalDays) * 100, 2);
    
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'days_present' => $daysPresent,
                    'days_absent' => $daysAbsent,
                    'attendance_rate' => $attendanceRate,
                    'working_days' => $totalDays
                ];
            })->values();
    
            return response()->json($groupedAttendance);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format: ' . $e->getMessage()], 400);
        }
    }
    
    // For Summanry ni.
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
    
        $weeklyAbsent = $totalEmployees - $weeklyPresent;
        $monthlyAbsent = $totalEmployees - $monthlyPresent;
    
        return response()->json([
            'weeklyPresent' => $weeklyPresent,
            'weeklyAbsent' => $weeklyAbsent,
            'monthlyPresent' => $monthlyPresent,
            'monthlyAbsent' => $monthlyAbsent,
        ]);
    }
        public function getMonthlyAttendance()
        {
            $year = Carbon::now()->year;

            $attendanceData = DB::table('attendance')
                ->selectRaw('MONTH(created_at) as month, 
                            SUM(CASE WHEN date_status = "Present" THEN 1 ELSE 0 END) as present,
                            SUM(CASE WHEN date_status = "Absent" THEN 1 ELSE 0 END) as absent,
                            SUM(CASE WHEN date_status = "Late" THEN 1 ELSE 0 END) as late')
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


