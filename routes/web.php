<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Schedule\ScheduleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Inertia\Inertia;



Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/scan', [AttendanceController::class, 'scan_card'])->name('scan');

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
    Route::get('/attendance-records', [AttendanceController::class, 'record'])->name('records');

    //Schedule
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedule');
    Route::post('/schedule-store', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/schedule-delete/{id}', [ScheduleController::class, 'destroy'])->name('schedule.delete');
    Route::put('/schedule-update/{id}', [ScheduleController::class, 'update'])->name('schedule.update');

    Route::put('/schedule-update-status/{id}', [ScheduleController::class, 'isSet'])->name('isSet');

    //Pdf
    Route::get('/api/attendance/export/pdf', [ExportController::class, 'export_attendance']);

    //Excel
    Route::post('/excel-import', [FileController::class, 'import'])->middleware('auth');

});


require __DIR__.'/auth.php';
