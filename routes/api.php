<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/attendance/store', [AttendanceController::class, 'store']);
Route::get('/attendance/employees', [AttendanceController::class, 'fetch_attendance']);
Route::get('/attendance/employees/history', [AttendanceController::class, 'fetch_history']);

Route::get('/employees/present',[EmployeeController::class, 'get_present_employees']);
Route::get('/employees/count',[EmployeeController::class, 'fetch_employees']);

Route::get('/attendance/{range}', [EmployeeController::class, 'getAttendance']);Route::get('/employees/attendance/monthly', [AttendanceController::class, 'get_monthly_attendances']);
Route::get('/summary', [AttendanceController::class, 'attendance_summary']);
Route::get('/employees/attendance/today', [AttendanceController::class, 'getTodayAttendance']);

Route::get('/attendance/filter', [EmployeeController::class, 'filterAttendance']);
Route::get('/employees', [EmployeeController::class, 'getEmployees']);



