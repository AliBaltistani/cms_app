<?php

namespace App\Http\Requests\ProgramBuilder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateExerciseRequest
 *
 * Validates input for updating an existing Exercise details.
 *
 * @package     App\Http\Requests\ProgramBuilder
 * @subpackage  Requests
 * @category    Validation
 * @author      TRAE Assistant
 * @since       1.0.0
 */
class UpdateExerciseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'           => ['nullable', 'string', 'max:255'],
            'tempo'          => ['nullable', 'string', 'max:50'],
            'rest_interval'  => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string'],
            'sets'           => ['required', 'array', 'min:1'],
            'sets.*.set_number' => ['required', 'integer', 'min:1'],
            'sets.*.reps'    => ['nullable', 'integer', 'min:0'],
            'sets.*.weight'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}