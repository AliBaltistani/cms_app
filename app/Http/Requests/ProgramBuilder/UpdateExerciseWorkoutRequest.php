<?php

namespace App\Http\Requests\ProgramBuilder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateExerciseWorkoutRequest
 *
 * Validates input for updating an Exercise's associated workout.
 *
 * @package     App\Http\Requests\ProgramBuilder
 * @subpackage  Requests
 * @category    Validation
 * @author      TRAE Assistant
 * @since       1.0.0
 */
class UpdateExerciseWorkoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'workout_id' => ['nullable', 'integer', 'exists:workouts,id'],
        ];
    }
}