<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Location Model
 * 
 * Manages location information for all user types (admin, trainer, trainee)
 * Provides relationship with User model and location-specific functionality
 * 
 * @package     Go Globe CMS
 * @subpackage  Models
 * @category    User Management
 * @author      System Administrator
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $user_id
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $address
 * @property string|null $zipcode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 */
class UserLocation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'country',
        'state',
        'city',
        'address',
        'zipcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the location.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full address as a formatted string.
     * 
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->country,
            $this->zipcode
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Get the short address (city, state, country).
     * 
     * @return string
     */
    public function getShortAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->city,
            $this->state,
            $this->country
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Scope a query to filter by country.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $country
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope a query to filter by state.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $state
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Scope a query to filter by city.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $city
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Check if the location has complete address information.
     * 
     * @return bool
     */
    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && 
               !empty($this->city) && 
               !empty($this->state) && 
               !empty($this->country);
    }

    /**
     * Get locations within a specific area (city/state/country).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInArea($query, array $filters)
    {
        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        
        if (isset($filters['state'])) {
            $query->where('state', $filters['state']);
        }
        
        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        return $query;
    }
}