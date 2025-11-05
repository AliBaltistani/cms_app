<?php

namespace App\Http\Requests\ProgramBuilder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreDayRequest
 *
 * Validates input for creating a new Day under a Week.
 *
 * @package     App\Http\Requests\ProgramBuilder
 * @subpackage  Requests
 * @category    Validation
 * @author      TRAE Assistant
 * @since       1.0.0
 */
class StoreDayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'day_number'    => ['required', 'integer', 'min:1'],
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'cool_down'     => ['nullable', 'string'],
        ];
    }
}