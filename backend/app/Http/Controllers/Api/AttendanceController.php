<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private function employeeId(Request $request): int
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->is_active, 403, 'Active employee profile required.');
        return $employee->id;
    }

    public function index(Request $request): JsonResponse
    {
        $employeeId = $this->employeeId($request);

        return response()->json([
            'data' => Attendance::where('employee_id', $employeeId)
                ->latest('work_date')->paginate(30),
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $employeeId = $this->employeeId($request);
        $today = now()->toDateString();

        $attendance = DB::transaction(function () use ($employeeId, $today, $data) {
            $record = Attendance::where('employee_id', $employeeId)
                ->whereDate('work_date', $today)->lockForUpdate()->first();

            if ($record && $record->check_in_at) {
                abort(409, 'Already checked in for today.');
            }

            if (!$record) {
                $record = new Attendance([
                    'employee_id' => $employeeId,
                    'work_date' => $today,
                ]);
            }

            $record->fill([
                'check_in_at' => now(),
                'check_in_latitude' => $data['latitude'],
                'check_in_longitude' => $data['longitude'],
                'status' => 'present',
            ]);
            $record->save();

            return $record;
        });

        return response()->json(['message' => 'Checked in.', 'data' => $attendance], 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $employeeId = $this->employeeId($request);
        $record = Attendance::where('employee_id', $employeeId)
            ->whereDate('work_date', now()->toDateString())->first();

        if (!$record || !$record->check_in_at) {
            return response()->json(['message' => 'Check in before checking out.'], 422);
        }
        if ($record->check_out_at) {
            return response()->json(['message' => 'Already checked out.'], 409);
        }

        $record->update([
            'check_out_at' => now(),
            'check_out_latitude' => $data['latitude'],
            'check_out_longitude' => $data['longitude'],
        ]);

        return response()->json(['message' => 'Checked out.', 'data' => $record]);
    }
}
