<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Goal;
use App\Models\User;

class GoalsController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax() || $request->has('draw')) {
                return $this->getDataTableData($request);
            }

            $stats = $this->calculateGoalStatistics();
            $user = Auth::user();

            $dashboardData = [
                'stats' => $stats,
                'user' => $user,
                'login_time' => session('login_time', now()),
                'total_users' => User::count(),
                'user_since' => $user->created_at->format('F Y'),
            ];

            return view('trainer.goals.index', $dashboardData);
        } catch (\Exception $e) {
            Log::error('Trainer goals index failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'params' => $request->all(),
            ]);
            return redirect()->back()->with('error', 'Failed to retrieve goals');
        }
    }

    private function getDataTableData(Request $request)
    {
        try {
            $query = Goal::query()
                ->with(['user:id,name,email'])
                ->where('user_id', Auth::id());

            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $totalRecords = Goal::where('user_id', Auth::id())->count();
            $filteredRecords = $query->count();

            if ($request->filled('order.0.column')) {
                $columns = ['id', 'name', 'user', 'status', 'created_at', 'updated_at', 'actions'];
                $orderColumn = $columns[$request->input('order.0.column')] ?? 'id';
                $orderDirection = $request->input('order.0.dir', 'desc');

                if (in_array($orderColumn, ['id', 'name', 'status', 'created_at', 'updated_at'])) {
                    $query->orderBy($orderColumn, $orderDirection);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $goals = $query->skip($start)->take($length)->get();

            $data = $goals->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'user' => $goal->user ? $goal->user->name : 'Me',
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
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Trainer goals datatable failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'params' => $request->all(),
            ]);
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to retrieve goals',
            ]);
        }
    }

    private function getActionButtons(int $goalId): string
    {
        return '
            <div class="d-flex justify-content-end">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href=\'' . route('trainer.goals.edit', $goalId) . '\'" title="Edit">
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

    private function calculateGoalStatistics(): array
    {
        try {
            $userId = Auth::id();

            $totalGoals = Goal::where('user_id', $userId)->count();
            $activeGoals = Goal::where('user_id', $userId)->where('status', 1)->count();
            $inactiveGoals = Goal::where('user_id', $userId)->where('status', 0)->count();
            $goalsWithUsers = Goal::where('user_id', $userId)->count();
            $recentGoals = Goal::where('user_id', $userId)->where('created_at', '>=', now()->subDays(30))->count();

            return [
                'total_goals' => $totalGoals,
                'active_goals' => $activeGoals,
                'inactive_goals' => $inactiveGoals,
                'goals_with_users' => $goalsWithUsers,
                'recent_goals' => $recentGoals,
                'trainer_goals' => $totalGoals,
                'client_goals' => 0,
                'active_percentage' => $totalGoals > 0 ? round(($activeGoals / $totalGoals) * 100, 1) : 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_goals' => 0,
                'active_goals' => 0,
                'inactive_goals' => 0,
                'goals_with_users' => 0,
                'recent_goals' => 0,
                'trainer_goals' => 0,
                'client_goals' => 0,
                'active_percentage' => 0,
            ];
        }
    }

    public function create()
    {
        $user = Auth::user();
        $dashboardData = [
            'user' => $user,
            'login_time' => session('login_time', now()),
            'total_users' => User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];
        return view('trainer.goals.create', $dashboardData);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $this->validator($data)->validate();
        Goal::create([
            'name' => $data['name'],
            'user_id' => Auth::id(),
            'status' => $data['status'],
        ]);
        return redirect()->route('trainer.goals.index')->with('success', 'Goal created successfully.');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $goal = Goal::where('user_id', Auth::id())->findOrFail($id);
        $dashboardData = [
            'user' => $user,
            'goal' => $goal,
            'login_time' => session('login_time', now()),
            'total_users' => User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];
        return view('trainer.goals.edit', $dashboardData);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $this->validator($data)->validate();
        $goal = Goal::where('user_id', Auth::id())->findOrFail($id);
        $goal->update([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);
        return redirect()->route('trainer.goals.index')->with('success', 'Goal updated successfully.');
    }

    public function delete($id)
    {
        try {
            $goal = Goal::where('user_id', Auth::id())->findOrFail($id);
            $goal->delete();
            return response()->json(['success' => true, 'message' => 'Goal deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete goal: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $goal = Goal::where('user_id', Auth::id())->findOrFail($id);
            $goal->status = !$goal->status;
            $goal->save();
            $statusText = $goal->status ? 'activated' : 'deactivated';
            return response()->json(['success' => true, 'message' => "Goal has been {$statusText} successfully.", 'new_status' => $goal->status]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update goal status: ' . $e->getMessage()], 500);
        }
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'status' => ['required'],
        ], [
            'name.required' => 'Please enter name.',
            'name.min' => 'Name must be at least 3 characters long.',
            'status.required' => 'Please select a status.',
        ]);
    }
}