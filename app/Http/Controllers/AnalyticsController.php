<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function getAttendanceData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $periodType = $request->input('period_type', 'monthly');
        
        if ($periodType === 'monthly') {
            return $this->getMonthlyAttendanceData($year);
        } else {
            return $this->getYearlyAttendanceData();
        }
    }
    
    /**
     * Get monthly attendance data for a specific year
     */
    private function getMonthlyAttendanceData($year)
    {
        $months = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        
        // Get months in English
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create($year, $i, 1)->format('M');
        }
        
        // Get present counts for each month
        $presentCounts = DB::table('attendance')
            ->select(DB::raw('MONTH(date) as month, COUNT(*) as count'))
            ->whereYear('date', $year)
            ->where('status', 'Present')
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('count', 'month')
            ->toArray();
            
        // Get absent counts for each month
        $absentCounts = DB::table('attendance')
            ->select(DB::raw('MONTH(date) as month, COUNT(*) as count'))
            ->whereYear('date', $year)
            ->where('status', 'Absent')
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('count', 'month')
            ->toArray();
            
        // Get late counts for each month
        $lateCounts = DB::table('attendance')
            ->select(DB::raw('MONTH(date) as month, COUNT(*) as count'))
            ->whereYear('date', $year)
            ->where('status', 'Late')
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('count', 'month')
            ->toArray();
        
        // Fill in the data arrays
        for ($i = 1; $i <= 12; $i++) {
            $presentData[] = $presentCounts[$i] ?? 0;
            $absentData[] = $absentCounts[$i] ?? 0;
            $lateData[] = $lateCounts[$i] ?? 0;
        }
        
        return response()->json([
            'labels' => $months,
            'present' => $presentData,
            'absent' => $absentData,
            'late' => $lateData
        ]);
    }
    
    /**
     * Get yearly attendance data for multiple years
     */
    private function getYearlyAttendanceData()
    {
        // Get the earliest year in the database
        $earliestDate = DB::table('attendance')->min('date');
        $earliestYear = $earliestDate ? Carbon::parse($earliestDate)->year : Carbon::now()->year;
        $currentYear = Carbon::now()->year;
        
        $years = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        
        // Create the year labels
        for ($year = $earliestYear; $year <= $currentYear; $year++) {
            $years[] = (string)$year;
        }
        
        // Get present counts for each year
        $presentCounts = DB::table('attendance')
            ->select(DB::raw('YEAR(date) as year, COUNT(*) as count'))
            ->where('status', 'Present')
            ->groupBy(DB::raw('YEAR(date)'))
            ->pluck('count', 'year')
            ->toArray();
            
        // Get absent counts for each year
        $absentCounts = DB::table('attendance')
            ->select(DB::raw('YEAR(date) as year, COUNT(*) as count'))
            ->where('status', 'Absent')
            ->groupBy(DB::raw('YEAR(date)'))
            ->pluck('count', 'year')
            ->toArray();
            
        // Get late counts for each year
        $lateCounts = DB::table('attendance')
            ->select(DB::raw('YEAR(date) as year, COUNT(*) as count'))
            ->where('status', 'Late')
            ->groupBy(DB::raw('YEAR(date)'))
            ->pluck('count', 'year')
            ->toArray();
        
        // Fill in the data arrays
        for ($year = $earliestYear; $year <= $currentYear; $year++) {
            $presentData[] = $presentCounts[$year] ?? 0;
            $absentData[] = $absentCounts[$year] ?? 0;
            $lateData[] = $lateCounts[$year] ?? 0;
        }
        
        return response()->json([
            'labels' => $years,
            'present' => $presentData,
            'absent' => $absentData,
            'late' => $lateData
        ]);
    }
    
    /**
     * Get employee status data for graphing
     */
    public function getEmployeeStatusData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $periodType = $request->input('period_type', 'monthly');
        
        if ($periodType === 'monthly') {
            return $this->getMonthlyEmployeeStatusData($year);
        } else {
            return $this->getYearlyEmployeeStatusData();
        }
    }

    /**
     * Get monthly employee status data for a specific year
     */
    private function getMonthlyEmployeeStatusData($year)
    {
        $months = [];
        $newHiresData = [];
        $existingData = [];
        $resignedData = [];
        
        // Get months in English
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create($year, $i, 1)->format('M');
        }
        
        // Get new hires for each month (employees marked as Active with start_date in that month)
        $newHiresCounts = DB::table('employees')
            ->select(DB::raw('MONTH(start_date) as month, COUNT(*) as count'))
            ->whereYear('start_date', $year)
            ->where('status', 'Active')
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->pluck('count', 'month')
            ->toArray();
            
        // Get existing employees for each month (active employees not hired in that month)
        // This calculation requires joining multiple queries or doing some logic to count
        // For simplicity, we'll use the Active count minus new hires
        $activeCounts = DB::table('employees')
            ->select(DB::raw('MONTH(status_date) as month, COUNT(*) as count'))
            ->whereYear('status_date', $year)
            ->where('status', 'Active')
            ->groupBy(DB::raw('MONTH(status_date)'))
            ->pluck('count', 'month')
            ->toArray();
            
        // Get resigned employee counts for each month
        $resignedCounts = DB::table('employees')
            ->select(DB::raw('MONTH(status_date) as month, COUNT(*) as count'))
            ->whereYear('status_date', $year)
            ->where('status', 'Terminated')
            ->groupBy(DB::raw('MONTH(status_date)'))
            ->pluck('count', 'month')
            ->toArray();
        
        // Fill in the data arrays
        for ($i = 1; $i <= 12; $i++) {
            $newHiresData[] = $newHiresCounts[$i] ?? 0;
            // Existing employees = active - new hires
            $existingData[] = ($activeCounts[$i] ?? 0) - ($newHiresCounts[$i] ?? 0);
            if ($existingData[count($existingData) - 1] < 0) {
                $existingData[count($existingData) - 1] = 0; // Ensure we don't have negative values
            }
            $resignedData[] = $resignedCounts[$i] ?? 0;
        }
        
        return response()->json([
            'labels' => $months,
            'new_hires' => $newHiresData,
            'existing' => $existingData,
            'resigned' => $resignedData
        ]);
    }
    
    /**
     * Get yearly employee status data for multiple years
     */
    private function getYearlyEmployeeStatusData()
    {
        // Get the earliest year in the database
        $earliestDate = DB::table('employees')->min('start_date');
        $earliestYear = $earliestDate ? Carbon::parse($earliestDate)->year : Carbon::now()->year;
        $currentYear = Carbon::now()->year;
        
        $years = [];
        $newHiresData = [];
        $existingData = [];
        $resignedData = [];
        
        // Create the year labels
        for ($year = $earliestYear; $year <= $currentYear; $year++) {
            $years[] = (string)$year;
        }
        
        // Get new hires for each year
        $newHiresCounts = DB::table('employees')
            ->select(DB::raw('YEAR(start_date) as year, COUNT(*) as count'))
            ->where('status', 'Active')
            ->groupBy(DB::raw('YEAR(start_date)'))
            ->pluck('count', 'year')
            ->toArray();
            
        // Get active employees for each year
        $activeCounts = DB::table('employees')
            ->select(DB::raw('YEAR(status_date) as year, COUNT(*) as count'))
            ->where('status', 'Active')
            ->groupBy(DB::raw('YEAR(status_date)'))
            ->pluck('count', 'year')
            ->toArray();
            
        // Get resigned employees for each year
        $resignedCounts = DB::table('employees')
            ->select(DB::raw('YEAR(status_date) as year, COUNT(*) as count'))
            ->where('status', 'Terminated')
            ->groupBy(DB::raw('YEAR(status_date)'))
            ->pluck('count', 'year')
            ->toArray();
        
        // Fill in the data arrays
        for ($year = $earliestYear; $year <= $currentYear; $year++) {
            $newHiresData[] = $newHiresCounts[$year] ?? 0;
            $existingData[] = ($activeCounts[$year] ?? 0) - ($newHiresCounts[$year] ?? 0);
            if ($existingData[count($existingData) - 1] < 0) {
                $existingData[count($existingData) - 1] = 0;
            }
            $resignedData[] = $resignedCounts[$year] ?? 0;
        }
        
        return response()->json([
            'labels' => $years,
            'new_hires' => $newHiresData,
            'existing' => $existingData,
            'resigned' => $resignedData
        ]);
    }
    
    /**
     * Get available years for filtering
     */
    public function getAvailableYears()
    {
        $attendanceYears = DB::table('attendance')
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->pluck('year')
            ->toArray();
            
        $employeeYears = DB::table('employees')
            ->selectRaw('DISTINCT YEAR(status_date) as year')
            ->pluck('year')
            ->toArray();
            
        $allYears = array_unique(array_merge($attendanceYears, $employeeYears));
        sort($allYears);
        
        return response()->json([
            'years' => $allYears
        ]);
    }
    
    /**
     * Export analytics data
     */
    public function exportData(Request $request)
    {
        $format = $request->input('format', 'csv');
        $year = $request->input('year', Carbon::now()->year);
        $periodType = $request->input('period_type', 'monthly');
        
        // Implementation would depend on what export libraries you're using
        // This is just a placeholder
        return response()->json([
            'success' => true,
            'message' => 'Export functionality would be implemented here'
        ]);
    }
}