<?php

/**
 * Specialization Model
 * 
 * Represents trainer specializations that can be managed by Admin
 * Trainers can only select from these predefined specializations
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Trainer Specializations
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-19
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialization extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'specializations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at, not updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * Get the trainers that have this specialization.
     * 
     * Many-to-many relationship with User model through trainer_specializations pivot table
     *
     * @return BelongsToMany
     */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'trainer_specializations',
            'specialization_id',
            'trainer_id'
        )->withPivot(['created_at']);
    }

    /**
     * Scope a query to only include active specializations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive specializations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Get the status as a human-readable string.
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    /**
     * Get the count of trainers with this specialization.
     *
     * @return int
     */
    public function getTrainersCountAttribute(): int
    {
        return $this->trainers()->count();
    }

    /**
     * Check if the specialization is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status == 1;
    }

    /**
     * Check if the specialization is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status == 0;
    }

    /**
     * Activate the specialization.
     *
     * @return bool
     */
    public function activate(): bool
    {
        $this->status = 1;
        return $this->save();
    }

    /**
     * Deactivate the specialization.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->status = 0;
        return $this->save();
    }
}