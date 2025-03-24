<?php

use App\Http\Controllers\ExportController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\AttendanceController;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/rfid-scan', function(){
    return Inertia::render('RfidScan');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    //Employees
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::delete('employee/{id}', [EmployeeController::class, 'destroy'])->name('employee.destroy');
    Route::get('/employees/create', [PositionController::class, 'index'])->name('employeeShowForm');
    Route::get('/employees/edit/{id}', [EmployeeController::class, 'edit'])->name('employee.edit');
    Route::get('/employees/view/{id}', [EmployeeController::class, 'view'])->name('employee.view');
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employee.update');
    Route::post('/employees/register', [EmployeeController::class, 'store'])->name('employee.store');

    // Attendance
    Route::get('/attendance/view', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('history.list');


    Route::get('/api/attendance/export/pdf', [ExportController::class, 'export_attendance']);

});


require __DIR__.'/auth.php';
