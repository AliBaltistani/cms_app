<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Goal;
use App\Models\User;

/**
 * Goals Controller
 * 
 * Handles CRUD operations and statistics for fitness goals management
 * 
 * @package     Go Globe CMS
 * @subpackage  Controllers
 * @category    Admin Module
 * @author      Admin
 * @since       1.0.0
 */
class GoalsController extends Controller
{
    /**
     * Display goals index with statistics and DataTables support
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Handle DataTable AJAX request
            if ($request->ajax() || $request->has('draw')) {
                return $this->getDataTableData($request);
            }

            // Calculate comprehensive statistics
            $stats = $this->calculateGoalStatistics();
            
            // Get the authenticated user
            $user = Auth::user();

            // Prepare dashboard data
            $dashboardData = [
                'stats' => $stats,
                'user' => $user,
                'login_time' => session('login_time', now()),
                'total_users' => User::count(),
                'user_since' => $user->created_at->format('F Y'),
            ];

            return view('admin.goals.index', $dashboardData);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve goals: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to retrieve goals');
        }
    }

    /**
     * Handle DataTable AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDataTableData(Request $request)
    {
        try {
            $query = Goal::query()->with(['user:id,name,email']);

            // Handle DataTable search
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Get total count before pagination
            $totalRecords = Goal::count();
            $filteredRecords = $query->count();

            // Handle DataTable ordering
            if ($request->filled('order.0.column')) {
                $columns = ['id', 'name', 'user', 'status', 'created_at', 'updated_at', 'actions'];
                $orderColumn = $columns[$request->input('order.0.column')] ?? 'id';
                $orderDirection = $request->input('order.0.dir', 'desc');
                
                if ($orderColumn === 'user') {
                    $query->join('users', 'goals.user_id', '=', 'users.id')
                          ->orderBy('users.name', $orderDirection)
                          ->select('goals.*');
                } elseif (in_array($orderColumn, ['id', 'name', 'status', 'created_at', 'updated_at'])) {
                    $query->orderBy($orderColumn, $orderDirection);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Handle pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $goals = $query->skip($start)->take($length)->get();

            // Format data for DataTable
            $data = $goals->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'user' => $goal->user ? $goal->user->name : 'Unassigned',
                    'status' => $goal->status,
                    'created_at' => $goal->created_at->format('M d, Y'),
                    'updated_at' => $goal->updated_at->format('M d, Y'),
                    'actions' => $this->getActionButtons($goal->id)
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('DataTable request failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to retrieve goals'
            ]);
        }
    }

    /**
     * Generate action buttons for DataTable
     * 
     * @param  int  $goalId
     * @return string
     */
    private function getActionButtons(int $goalId): string
    {
        return '
            <div class="d-flex justify-content-end">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-info" onclick="window.location.href=\'/admin/goals/' . $goalId . '\'" title="View">
                    <i class="ri-eye-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href=\'/admin/goals/' . $goalId . '/edit\'" title="Edit">
                    <i class="ri-edit-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="toggleStatus(' . $goalId . ')" title="Toggle Status">
                    <i class="ri-toggle-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteGoal(' . $goalId . ')" title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
            </div>
        ';
    }

    /**
     * Calculate comprehensive goal statistics
     * 
     * @return array
     */
    private function calculateGoalStatistics()
    {
        try {
            $totalGoals = Goal::count();
            $activeGoals = Goal::where('status', 1)->count();
            $inactiveGoals = Goal::where('status', 0)->count();
            $goalsWithUsers = Goal::whereNotNull('user_id')->count();
            $systemGoals = Goal::whereNull('user_id')->count();
            
            // Recent goals (last 30 days)
            $recentGoals = Goal::where('created_at', '>=', now()->subDays(30))->count();
            
            // Goals by user type
            $trainerGoals = Goal::whereHas('user', function($query) {
                $query->where('role', 'trainer');
            })->count();
            
            $clientGoals = Goal::whereHas('user', function($query) {
                $query->where('role', 'client');
            })->count();

            return [
                'total_goals' => $totalGoals,
                'active_goals' => $activeGoals,
                'inactive_goals' => $inactiveGoals,
                'goals_with_users' => $goalsWithUsers,
                'system_goals' => $systemGoals,
                'recent_goals' => $recentGoals,
                'trainer_goals' => $trainerGoals,
                'client_goals' => $clientGoals,
                'active_percentage' => $totalGoals > 0 ? round(($activeGoals / $totalGoals) * 100, 1) : 0,
            ];
            
        } catch (\Exception $e) {
            // Return default stats if calculation fails
            return [
                'total_goals' => 0,
                'active_goals' => 0,
                'inactive_goals' => 0,
                'goals_with_users' => 0,
                'system_goals' => 0,
                'recent_goals' => 0,
                'trainer_goals' => 0,
                'client_goals' => 0,
                'active_percentage' => 0,
            ];
        }
    }

    /**
     * Toggle goal status (active/inactive)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        try {
            $goal = Goal::findOrFail($id);
            $goal->status = !$goal->status;
            $goal->save();

            $statusText = $goal->status ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "Goal has been {$statusText} successfully.",
                'new_status' => $goal->status
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update goal status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
         // Get the authenticated user
        $user = Auth::user();
        

        // Prepare dashboard data
        $dashboardData = [
            'user' => $user,
            'login_time' => session('login_time', now()),
            'total_users' => \App\Models\User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];
        return view('admin.goals.create', $dashboardData);
    }

    public function store(Request $request)
    {
        $data = $request->all();

            // Validate the goal data
            $this->validator($data)->validate();

            // Create the goal
            Goal::create([
                'name' => $data['name'],
                'user_id' => Auth::id(),
                'status' => $data['status']
            ]);

            // Redirect to the goals index with success message
            return redirect()->route('goals.index')->with('success', 'Goal created successfully.');

    }

    public function show($id)
    {
        return view('admin.goals.show', compact('id'));
    }


    public function edit($id)
    {
        $user = Auth::user();
        

        $goal = Goal::findOrFail($id);
        // Prepare dashboard data
        $dashboardData = [
            'user' => $user,
            'goal' => $goal,
            'login_time' => session('login_time', now()),
            'total_users' => \App\Models\User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];
        return view('admin.goals.edit', $dashboardData);
    }

    public function update(Request $request, $id)
    {
            $data = $request->all();
    
                // Validate the goal data
                $this->validator($data)->validate();
    
                $goal = Goal::findOrFail($id);
                $goal->update([
                    'name' => $data['name'],
                    'status' => $data['status']
                ]);
    
                // Redirect to the goals index with success message
                return redirect()->route('goals.index')->with('success', 'Goal updated successfully.');
    }

    /**
     * Delete a goal (AJAX endpoint)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            $goal = Goal::findOrFail($id);
            $goal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Goal deleted successfully.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete goal: ' . $e->getMessage()
            ], 500);
        }
    }

     protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'status' => ['required']
        ], [
            // Custom error messages
            'name.required' => 'Please enter name.',
            'name.min' => 'Name must be at least 3 characters long.',
            'status.required' => 'Please select a status.',
        ]);
    }

}
