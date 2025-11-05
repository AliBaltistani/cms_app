<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ProgramColumnConfig
 *
 * Stores the Program Builder column configuration per Program.
 *
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Program Builder Configuration
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class ProgramColumnConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'columns',
    ];

    protected $casts = [
        'columns' => 'array',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}