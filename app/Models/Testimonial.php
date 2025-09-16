<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Testimonial Model
 * 
 * Represents client testimonials for trainers
 * 
 * @property int $id
 * @property int $trainer_id
 * @property int $client_id
 * @property string $name
 * @property string $date
 * @property int $rate
 * @property string $comments
 * @property int $likes
 * @property int $dislikes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read User $trainer
 * @property-read User $client
 * @property-read \Illuminate\Database\Eloquent\Collection|TestimonialLikesDislike[] $reactions
 */
class Testimonial extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     */
    protected $table = 'testimonials';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'trainer_id',
        'client_id',
        'name',
        'date',
        'rate',
        'comments',
        'likes',
        'dislikes'
    ];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'rate' => 'integer',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the trainer that received the testimonial.
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
    
    /**
     * Get the client that wrote the testimonial.
     * 
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    
    /**
     * Get all reactions (likes/dislikes) for this testimonial.
     * 
     * @return HasMany
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(TestimonialLikesDislike::class);
    }
    
    /**
     * Get likes reactions for this testimonial.
     * 
     * @return HasMany
     */
    public function likesReactions(): HasMany
    {
        return $this->hasMany(TestimonialLikesDislike::class)->where('like', true);
    }
    
    /**
     * Get dislikes reactions for this testimonial.
     * 
     * @return HasMany
     */
    public function dislikesReactions(): HasMany
    {
        return $this->hasMany(TestimonialLikesDislike::class)->where('dislike', true);
    }
    
    /**
     * Increment the likes count.
     * 
     * @return void
     */
    public function incrementLikes(): void
    {
        $this->increment('likes');
    }
    
    /**
     * Decrement the likes count.
     * 
     * @return void
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes');
    }
    
    /**
     * Increment the dislikes count.
     * 
     * @return void
     */
    public function incrementDislikes(): void
    {
        $this->increment('dislikes');
    }
    
    /**
     * Decrement the dislikes count.
     * 
     * @return void
     */
    public function decrementDislikes(): void
    {
        $this->decrement('dislikes');
    }
}
