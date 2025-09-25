<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkoutAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WorkoutAssignmentController extends Controller
{
    /**
     * Update assignment status
     */
    public function updateStatus(Request $request, WorkoutAssignment $assignment)
    {
        try {
            $request->validate([
                'status' => 'required|in:assigned,in_progress,completed'
            ]);

            $oldStatus = $assignment->status;
            $assignment->update([
                'status' => $request->status
            ]);

            Log::info('Assignment status updated', [
                'assignment_id' => $assignment->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment status updated successfully',
                'data' => [
                    'status' => $assignment->status,
                    'old_status' => $oldStatus
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update assignment status: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment status'
            ], 500);
        }
    }

    /**
     * Remove assignment
     */
    public function destroy(WorkoutAssignment $assignment)
    {
        try {
            $assignmentData = [
                'id' => $assignment->id,
                'workout_id' => $assignment->workout_id,
                'assigned_to' => $assignment->assigned_to,
                'assigned_to_type' => $assignment->assigned_to_type
            ];

            $assignment->delete();

            Log::info('Assignment removed', [
                'assignment_data' => $assignmentData,
                'removed_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove assignment: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment'
            ], 500);
        }
    }
}