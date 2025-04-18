<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceSummary extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'employee_id',
    //     'date',
    //     'morning_status',
    //     'afternoon_status',
    //     'evening_status',
    //     'final_status',
    //     'remarks',
    //     'total_work_hours',
    //     'is_manual_edit',
    // ];
    protected $fillable = [
        'employee_id',
        'date',
        'morning_status',
        'afternoon_status',
        'evening_status',
        'final_status',
        'total_work_hours',
        'is_manual_edit',
        'remarks'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'total_work_hours' => 'decimal:2',
        'is_manual_edit' => 'boolean',
    ];

    /**
     * Get the employee that owns the attendance summary.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the related attendance records for this summary.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id')
            ->whereDate('date', $this->date);
    }

    /**
     * Scope a query to only include summaries for a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include summaries for today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    /**
     * Scope a query to only include summaries for a specific month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $year
     * @param  int  $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
    }

    /**
     * Scope a query to only include summaries with complete attendance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComplete($query)
    {
        return $query->where('morning_status', '✓')
                    ->where('afternoon_status', '✓')
                    ->where('evening_status', '✓');
    }

    /**
     * Scope a query to only include summaries with incomplete attendance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncomplete($query)
    {
        return $query->where(function($q) {
            $q->where('morning_status', 'x')
              ->orWhere('afternoon_status', 'x')
              ->orWhere('evening_status', 'x');
        });
    }

    /**
     * Get the formatted date attribute.
     *
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        return $this->date->format('F d, Y');
    }

    /**
     * Get the day of week attribute.
     *
     * @return string
     */
    public function getDayOfWeekAttribute()
    {
        return $this->date->format('l');
    }

    /**
     * Get the is complete attribute.
     *
     * @return bool
     */
    public function getIsCompleteAttribute()
    {
        return $this->morning_status === '✓' && 
               $this->afternoon_status === '✓' && 
               $this->evening_status === '✓';
    }

    /**
     * Get the completion percentage attribute.
     *
     * @return int
     */
    public function getCompletionPercentageAttribute()
    {
        $total = 3; // morning, afternoon, evening
        $completed = 0;
        
        if ($this->morning_status === '✓') $completed++;
        if ($this->afternoon_status === '✓') $completed++;
        if ($this->evening_status === '✓') $completed++;
        
        return ($completed / $total) * 100;
    }

    /**
     * Update the summary based on attendance records.
     *
     * @param  Attendance|null  $attendance
     * @return $this
     */
    public function updateFromAttendance($attendance = null)
    {
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $this->employee_id)
                ->whereDate('date', $this->date)
                ->first();
        }

        if ($attendance) {
            // Update morning status
            if ($attendance->morning_in) {
                $this->morning_status = '✓';
            }
            
            // Update afternoon status
            if ($attendance->afternoon_in) {
                $this->afternoon_status = '✓';
            }
            
            // Update evening status
            if ($attendance->evening_in) {
                $this->evening_status = '✓';
            }
            
            // Update final status
            $this->final_status = $attendance->status;
            
            // Update work hours
            $this->total_work_hours = $attendance->work_hours;
            
            $this->save();
        }
        
        return $this;
    }

    /**
     * Create or update attendance summary for an employee on a specific date.
     *
     * @param  int  $employeeId
     * @param  string|Carbon  $date
     * @param  array  $attributes
     * @return AttendanceSummary
     */
    public static function createOrUpdateForEmployee($employeeId, $date, array $attributes = [])
    {
        $summary = self::firstOrNew([
            'employee_id' => $employeeId,
            'date' => $date instanceof Carbon ? $date : Carbon::parse($date),
        ]);
        
        $summary->fill($attributes);
        $summary->save();
        
        return $summary;
    }

    /**
     * Initialize attendance summaries for all employees for a specific date.
     *
     * @param  string|Carbon  $date
     * @return int Number of records created
     */
    public static function initializeForAllEmployees($date = null)
    {
        $date = $date instanceof Carbon ? $date : ($date ? Carbon::parse($date) : Carbon::today());
        $count = 0;
        
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            $exists = self::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->exists();
                
            if (!$exists) {
                self::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'morning_status' => 'x',
                    'afternoon_status' => 'x',
                    'evening_status' => 'x',
                    'final_status' => 'Pending',
                    'total_work_hours' => 0,
                ]);
                
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get monthly attendance summary for an employee.
     *
     * @param  int  $employeeId
     * @param  int  $year
     * @param  int  $month
     * @return array
     */
    public static function getMonthlyEmployeeSummary($employeeId, $year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $endDate->day;
        
        $summaries = self::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(function ($item) {
                return $item->date->day;
            });
            
        $result = [
            'days_present' => 0,
            'days_absent' => 0,
            'days_late' => 0,
            'total_work_hours' => 0,
            'attendance_rate' => 0,
            'daily_records' => [],
        ];
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
            
            // Skip weekends if needed
            $isWeekend = $currentDate->isWeekend();
            
            if ($isWeekend) {
                $result['daily_records'][$day] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $currentDate->format('l'),
                    'is_weekend' => true,
                    'status' => 'Weekend',
                    'morning' => '-',
                    'afternoon' => '-',
                    'evening' => '-',
                    'work_hours' => 0,
                ];
                continue;
            }
            
            $summary = $summaries[$day] ?? null;
            
            if ($summary) {
                $status = $summary->final_status ?? 'Absent';
                
                if ($status === 'Present') {
                    $result['days_present']++;
                } elseif ($status === 'Late') {
                    $result['days_late']++;
                } else {
                    $result['days_absent']++;
                }
                
                $result['total_work_hours'] += $summary->total_work_hours;
                
                $result['daily_records'][$day] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $currentDate->format('l'),
                    'is_weekend' => false,
                    'status' => $status,
                    'morning' => $summary->morning_status,
                    'afternoon' => $summary->afternoon_status,
                    'evening' => $summary->evening_status,
                    'work_hours' => $summary->total_work_hours,
                ];
            } else {
                $result['days_absent']++;
                
                $result['daily_records'][$day] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $currentDate->format('l'),
                    'is_weekend' => false,
                    'status' => 'Absent',
                    'morning' => 'x',
                    'afternoon' => 'x',
                    'evening' => 'x',
                    'work_hours' => 0,
                ];
            }
        }
        
        // Calculate attendance rate (excluding weekends)
        $workDays = collect($result['daily_records'])->filter(function ($record) {
            return !$record['is_weekend'];
        })->count();
        
        $result['attendance_rate'] = $workDays > 0 
            ? round(($result['days_present'] + $result['days_late']) / $workDays * 100, 2) 
            : 0;
            
        return $result;
    }
}