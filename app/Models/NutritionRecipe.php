<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionRecipe Model
 * 
 * Manages individual recipes within nutrition plans
 * Contains simplified recipe information: image, title, description
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionRecipe extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_recipes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'title',
        'description',
        'image_url',
        'sort_order'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the nutrition plan this recipe belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class, 'plan_id');
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the image URL with full path
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // If it's already a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        
        // Return storage URL
        return asset('storage/' . $value);
    }

    /**
     * Get truncated description for listing views
     */
    public function getShortDescriptionAttribute()
    {
        if (!$this->description) {
            return 'No description available';
        }
        
        return strlen($this->description) > 100 
            ? substr($this->description, 0, 100) . '...' 
            : $this->description;
    }

    /**
     * Check if recipe has an image
     */
    public function getHasImageAttribute()
    {
        return !empty($this->attributes['image_url']);
    }

    /**
     * Get formatted creation date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M d, Y');
    }

    /**
     * Get formatted creation time
     */
    public function getFormattedTimeAttribute()
    {
        return $this->created_at->format('h:i A');
    }
}