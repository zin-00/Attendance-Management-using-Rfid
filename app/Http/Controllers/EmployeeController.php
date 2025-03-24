<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Get search and filter parameters
        $search = $request->input('search');
        $status = $request->input('status');
        $position = $request->input('position');
        $country = $request->input('country');
        $city = $request->input('city');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

        $query = Employee::query()
            ->with('position'); 

        if ($search) {
            $query->where(function($q) use ($search) {
                // Search across multiple columns
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('position', function($posQuery) use ($search) {
                      $posQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply additional filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($position) {
            $query->where('position_id', $position);
        }

        if ($country) {
            $query->where('country', $country);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get paginated results
        $employees = $query->orderBy('id', 'desc')
                          ->paginate(10)
                          ->withQueryString();

        // Get data for dropdowns
        $positions = Position::all();
        $countries = Employee::select('country')->distinct()->whereNotNull('country')->pluck('country');
        
        // Get cities based on country filter
        $cities = [];
        if ($country) {
            $cities = Employee::where('country', $country)
                            ->distinct()
                            ->whereNotNull('city')
                            ->pluck('city');
        }

        // Return Inertia view with data
        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'positions' => $positions,
            'countries' => $countries,
            'cities' => $cities,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'position' => $position,
                'country' => $country,
                'city' => $city,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ],
            'loading' => false
        ]);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
          'rfid_tag'                  => ['required', 'string', 'max:255', 'unique:employees,rfid_tag'],
                'first_name'                => ['required', 'string', 'max:255'],
                'last_name'                 => ['required', 'string', 'max:255'],
                'birthdate'                 => ['required', 'date'],
                'contact_number'            => ['required', 'string', 'max:15'],
                'emergency_contact'         => ['nullable', 'string', 'max:255'], 
                'emergency_contact_number'  => ['nullable', 'string', 'max:15'],  
                'street_address'            => ['required', 'string'],
                'city'                      => ['required', 'string'],
                'state'                     => ['required', 'string'],
                'zip_code'                  => ['required', 'string', 'max:10'], 
                'country'                   => ['required', 'string'],
                'hire_date'                 => ['required', 'date'],
                'email'                     => ['required', 'email', 'unique:employees,email'],
                'password'                  => ['required', 'string', 'min:8', 'confirmed'],            
                'gender'                    => ['required', 'in:Male,Female,Other'],
                'status'                    => ['required', 'in:Active,Inactive,Resigned,Banned'],
                'position_id'               => ['required', 'exists:positions,id'],
                'profile_image'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

        ]);
            $validatedData['password'] = Hash::make($validatedData['password']);
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profile_images', 'public');
                $validatedData['profile_image'] = $imagePath;
            } else {
                $validatedData['profile_image'] = 'profile_images/default.png';
            }
        
            $employee = Employee::create($validatedData);

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Employee created successfully!'
            // ]);

           if($employee){
            return back()->with('success', 'Employee created successfully!');
           }else{
            return back()->with('error', 'Employee created successfully!');
           }
           
            
            // return redirect()->route('employees.index')
            // ->with('success', 'Employee created successfully!');
    }
    
    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
    
        return to_route('employees.index')->with('success', 'Employee deleted successfully!');
    }
    public function edit($id){
        $employee = Employee::with('position')->findOrFail($id);
        $positions = Position::all();
        return Inertia::render('Employees/ManageEmployee', [
            'employee' => $employee,
            'positions' => $positions,
        ]);
    }
    public function view($id){
        $employee = Employee::with('position')->findOrFail($id);
        $employee->profile_image = $employee->profile_image 
        ? asset('storage/' . $employee->profile_image)  
        : asset('images/default-avatar.png');

        return Inertia::render('Employees/ViewEmployee', [
            'employee' => $employee,
        ]);
    }
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
    
        $validatedData = $request->validate([
            'rfid_tag'                  => ['required', 'string', 'max:255', Rule::unique('employees', 'rfid_tag')->ignore($employee->id)],
            'first_name'                => ['required', 'string', 'max:255'],
            'last_name'                 => ['required', 'string', 'max:255'],
            'birthdate'                 => ['required', 'date'],
            'contact_number'            => ['required', 'string', 'max:15'],
            'emergency_contact'         => ['nullable', 'string', 'max:255'],
            'emergency_contact_number'  => ['nullable', 'string', 'max:15'],
            'street_address'            => ['required', 'string'],
            'city'                      => ['required', 'string'],
            'state'                     => ['required', 'string'],
            'zip_code'                  => ['required', 'string', 'max:10'],
            'hire_date'                 => ['required', 'date'],
            'email'                     => ['required', 'email', Rule::unique('employees', 'email')->ignore($employee->id)],
            'gender'                    => ['required', 'in:Male,Female,Other'],
            'status'                    => ['required', 'in:Active,Inactive,Resigned,Banned'],
            'position_id'               => ['required', 'exists:positions,id'],
            'profile_image'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);
    
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->password);
        } else {
            unset($validatedData['password']);
        }
    
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public'); 
            $validatedData['profile_image'] = $imagePath;
        }
    
        $employee->update($validatedData);
        if($employee){
            return back()->with('success', 'Employee updated successfully!');
        }else{
            return back()->with('error', 'Employee updated successfully!');
        }
    
    }

    public function fetch_employees()
    {
        $today = Carbon::today();

        $active_employees = Employee::where('status', 'Active')->count();
        $total_present = Attendance::whereDate('created_at', $today)->distinct('employee_id')->count();
        $total_employees = Employee::count();
        $total_absent = $total_employees - $total_present;
    
        return response()->json([
            'active_employees' => $active_employees,
            'total' => $total_employees,
            'total_present' => $total_present,
            'total_absent' => $total_absent,
        ]);
    }
    public function get_present_employees(){
        $employees = Employee::where('status', 'Active')->get();
        return response()->json([
            'employees' => $employees
        ]);

    }
    
    public function getAttendance($range)
    {
        $today = Carbon::today();
    
        switch ($range) {
            case 'week':
                $startDate = $today->copy()->startOfWeek();
                $endDate = $today->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                break;
            case 'today':
            default:
                $startDate = $today;
                $endDate = $today->copy()->endOfDay();
                break;
        }
    
        $attendanceRecords = Attendance::whereBetween('created_at', [$startDate, $endDate])
            ->with('employee')
            ->get();
    
        $totalPresent = $attendanceRecords->where('status', 'Present')->count();
        $totalEmployees = Employee::count();
        $totalAbsent = max(0, $totalEmployees - $totalPresent);
        
        return response()->json([
            'attendance' => $attendanceRecords,
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'totalEmployees' => $totalEmployees,
        ]);
    }
    // public function getAttendance($range)
    //     {
    //         $attendanceQuery = Attendance::with('employee')->orderBy('created_at', 'desc');

    //         if ($range === 'today') {
    //             $attendanceQuery->whereDate('created_at', Carbon::today());
    //         } elseif ($range === 'week') {
    //             $attendanceQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    //         } elseif ($range === 'month') {
    //             $attendanceQuery->whereMonth('created_at', Carbon::now()->month);
    //         }

    //         return response()->json([
    //             'attendance' => $attendanceQuery->get(),
    //             'totalPresent' => $attendanceQuery->count(),
    //             'totalAbsent' => Employee::count() - $attendanceQuery->count(),
    //             'totalEmployees' => Employee::count(),
    //         ]);
    //     }


    public function filterAttendance(Request $request)
    {
        $query = Attendance::query();
        
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('created_at', $date);
        }
        
        $attendanceRecords = $query->with('employee')->get();
        
        $totalPresent = $attendanceRecords->where('status', 'Present')->count();
        $totalEmployees = Employee::count();
        $totalAbsent = $request->filled('employee_id') ? 
            ($attendanceRecords->isEmpty() ? 1 : 0) : 
            max(0, $totalEmployees - $totalPresent);
        
        return response()->json([
            'attendance' => $attendanceRecords->map(function ($record) {
                $employee = $record->employee;
                return [
                    'id' => $record->id,
                    'employee_name' => optional($employee)->name ?? 'Unknown',
                    'first_name' => optional($employee)->first_name ?? 'Unknown',
                    'last_name' => optional($employee)->last_name ?? '',
                    'rfid_tag' => $record->rfid_tag,
                    'status' => $record->status,
                    'date' => $record->created_at->toDateString(),
                    'time' => $record->created_at->format('H:i:s'),
                ];
            }),
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent
        ]);
    }

    public function getEmployees()
    {
        $employees = Employee::select('id', 'first_name', 'last_name', 'rfid_tag')
            ->where('status', 'active')
            ->get();
            
        return response()->json([
            'employees' => $employees
        ]);
    }
    

}