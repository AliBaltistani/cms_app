<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * TestimonialLikesDislike Model
 * 
 * Represents user reactions (likes/dislikes) to testimonials
 * 
 * @property int $id
 * @property int $testimonial_id
 * @property int $user_id
 * @property bool $like
 * @property bool $dislike
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Testimonial $testimonial
 * @property-read User $user
 */
class TestimonialLikesDislike extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     */
    protected $table = 'testimonial_likes_dislikes';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'testimonial_id',
        'user_id',
        'like',
        'dislike'
    ];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'like' => 'boolean',
        'dislike' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the testimonial that this reaction belongs to.
     * 
     * @return BelongsTo
     */
    public function testimonial(): BelongsTo
    {
        return $this->belongsTo(Testimonial::class);
    }
    
    /**
     * Get the user that made this reaction.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Set a like reaction (removes dislike if exists).
     * 
     * @return void
     */
    public function setLike(): void
    {
        $this->update([
            'like' => true,
            'dislike' => false
        ]);
    }
    
    /**
     * Set a dislike reaction (removes like if exists).
     * 
     * @return void
     */
    public function setDislike(): void
    {
        $this->update([
            'like' => false,
            'dislike' => true
        ]);
    }
    
    /**
     * Remove all reactions.
     * 
     * @return void
     */
    public function removeReaction(): void
    {
        $this->update([
            'like' => false,
            'dislike' => false
        ]);
    }
}
