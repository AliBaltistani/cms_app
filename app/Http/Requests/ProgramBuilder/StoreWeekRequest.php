<?php

namespace App\Http\Requests\ProgramBuilder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreWeekRequest
 *
 * Validates input for creating a new Program Week.
 *
 * @package     App\Http\Requests\ProgramBuilder
 * @subpackage  Requests
 * @category    Validation
 * @author      TRAE Assistant
 * @since       1.0.0
 */
class StoreWeekRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'week_number'   => ['required', 'integer', 'min:1'],
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
        ];
    }
}