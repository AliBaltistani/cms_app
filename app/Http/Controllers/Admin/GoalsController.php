<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Validator;
use App\Models\Goal;

class GoalsController extends Controller
{
    
    public function index()
    {
         // Get the authenticated user
        $user = Auth::user();

        // $goals = Goal::where('user_id', $user->id)->get();

          $goals = Goal::all(); // Fetch all goals for demonstration
        // Prepare dashboard data
        $dashboardData = [
            'goals' => $goals,
            'user' => $user,
            'login_time' => session('login_time', now()),
            'total_users' => \App\Models\User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];

        return view('admin.goals.index', $dashboardData);
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

    public function delete($id)
    {
            $goal = Goal::findOrFail($id);
            $goal->delete();
    
            return redirect()->route('goals.index')->with('success', 'Goal deleted successfully.');
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
