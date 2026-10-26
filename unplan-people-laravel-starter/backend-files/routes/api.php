<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (\Illuminate\Http\Request $request) => response()->json([
        'user' => $request->user(),
        'employee' => $request->user()->employee,
    ]));

    Route::get('/dashboard', DashboardController::class);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->middleware('throttle:10,1');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->middleware('throttle:10,1');
    Route::get('/leaves', [LeaveRequestController::class, 'index']);
    Route::post('/leaves', [LeaveRequestController::class, 'store'])->middleware('throttle:10,1');
});
