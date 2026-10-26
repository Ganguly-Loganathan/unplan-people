<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    private function employeeId(Request $request): int
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->is_active, 403, 'Active employee profile required.');
        return $employee->id;
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => LeaveRequest::where('employee_id', $this->employeeId($request))
                ->latest()->paginate(30),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'in:casual,sick,earned,permission,other'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $leave = LeaveRequest::create([
            ...$data,
            'employee_id' => $this->employeeId($request),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Leave request submitted.', 'data' => $leave], 201);
    }
}
