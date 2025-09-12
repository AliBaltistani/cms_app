<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Goal;

class ApiGoalController extends ApiBaseController
{
    
    public function index(Request $request)
    {
        $id = $request->input('id');
        if ($id) {
            $goal = \App\Models\Goal::find($id);
            if ($goal) {
                $success = $goal;
                return $this->sendResponse($success, 'Goal retrieved successfully', 200);
            }
            return $this->sendError('Not Found', ['error' => 'Goal not found'], 404);
        } else {
            $perPage = (int) $request->input('per_page', 10);
            $goals = \App\Models\Goal::paginate($perPage);

            $success['data'] = $goals->items();
            $success['pagination'] = [
                'total' => $goals->total(),
                'per_page' => $goals->perPage(),
                'current_page' => $goals->currentPage(),
                'last_page' => $goals->lastPage(),
                'from' => $goals->firstItem(),
                'to' => $goals->lastItem(),
            ];
            return $this->sendResponse($success, 'Goals retrieved successfully', 200);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $goal = Goal::create($request->all());

        $success['id'] = $goal->id;
        $success['name'] = $goal->name;
        $success['status'] = $goal->status;

        return $this->sendResponse($success, 'New Record created successfully', 201);
    }
}
