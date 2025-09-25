<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Week Model
 * 
 * Represents weeks within workout programs (Week 1, Week 2, etc.)
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class Week extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'week_number',
        'title',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'week_number' => 'integer',
    ];

    /**
     * Get the program that owns the week.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the days for the week.
     */
    public function days(): HasMany
    {
        return $this->hasMany(Day::class)->orderBy('day_number');
    }

    /**
     * Get the formatted week title.
     */
    public function getFormattedTitleAttribute(): string
    {
        return $this->title ?: "Week {$this->week_number}";
    }

    /**
     * Scope a query to filter by program.
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}