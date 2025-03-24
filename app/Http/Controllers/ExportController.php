<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function export_attendance(Request $request)
    {
        $query = Attendance::with('employee');
    
        // Apply filters (same logic as your original function)
        if ($request->filled('month') || $request->filled('year')) {
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $query->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }
    
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
        }
    
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
    
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(LOWER(first_name), ' ', LOWER(last_name)) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(first_name) LIKE ?", ["%$search%"])
                ->orWhereRaw("LOWER(last_name) LIKE ?", ["%$search%"]);
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
    
        $attendances = $query->orderBy('created_at', 'desc')->get();
    
        // Generate the PDF
        $pdf = Pdf::loadView('pdf.attendance', compact('attendances'));
    
        // Return the generated PDF as a downloadable file
        return $pdf->download('attendance_report.pdf');
    }
}
