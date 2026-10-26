<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->is_active, 403, 'Active employee profile required.');

        $today = now()->toDateString();

        return response()->json([
            'employee' => $employee,
            'today' => Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $today)->first(),
            'history' => Attendance::where('employee_id', $employee->id)
                ->latest('work_date')->limit(7)->get(),
            // Organization-wide totals are deliberately omitted from this employee endpoint.
            // Add a separate admin-only endpoint protected by policies/roles.
        ]);
    }
}
