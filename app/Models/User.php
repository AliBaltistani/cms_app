<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'profile_image',
        'designation',
        'experience',
        'about',
        'training_philosophy',
        'sms_notifications_enabled',
        'sms_marketing_enabled',
        'sms_quiet_start',
        'sms_quiet_end',
        'sms_notification_types',
        'timezone',
        'google_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sms_notifications_enabled' => 'boolean',
            'sms_marketing_enabled' => 'boolean',
            'sms_notification_types' => 'array',
            'google_token' => 'array',
        ];
    }

    public static function isAdmin(): bool
    {
        return Auth::user() && Auth::user()->role === 'admin';
    }


    public static function isClient(): bool
    {
        return Auth::user() && Auth::user()->role === 'client';
    }


    public static function isTrainer(): bool
    {
        return Auth::user() && Auth::user()->role === 'trainer';
    }
    


    /**
     * Get the location for the user.
     * 
     * @return HasOne
     */
    public function location(): HasOne
    {
        return $this->hasOne(UserLocation::class);
    }
    
    /**
     * Get the certifications for the trainer.
     * 
     * @return HasMany
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(UserCertification::class);
    }
    
    /**
     * Get testimonials received by this trainer.
     * 
     * @return HasMany
     */
    public function receivedTestimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class, 'trainer_id');
    }
    
    /**
     * Get testimonials written by this client.
     * 
     * @return HasMany
     */
    public function writtenTestimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class, 'client_id');
    }
    
    /**
     * Get reactions made by this user.
     * 
     * @return HasMany
     */
    public function testimonialReactions(): HasMany
    {
        return $this->hasMany(TestimonialLikesDislike::class);
    }
    
    /**
     * Check if user is a trainer.
     * 
     * @return bool
     */
    public function isTrainerRole(): bool
    {
        return $this->role === 'trainer';
    }
    
    /**
     * Check if user is a client.
     * 
     * @return bool
     */
    public function isClientRole(): bool
    {
        return $this->role === 'client';
    }
    
    /**
     * Check if user is an admin.
     * 
     * @return bool
     */
    public function isAdminRole(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Get the goals for the user.
     * 
     * @return HasMany
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
    
    /**
     * Get the workouts for the user.
     * 
     * @return HasMany
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
    
    /**
     * Get schedules where user is the trainer.
     * 
     * @return HasMany
     */
    public function trainerSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'trainer_id');
    }
    
    /**
     * Get schedules where user is the client.
     * 
     * @return HasMany
     */
    public function clientSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'client_id');
    }

    /**
     * Get workout assignments where user is assigned.
     * 
     * @return HasMany
     */
    public function workoutAssignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'assigned_to');
    }

    /**
     * Get workout assignments made by this user.
     * 
     * @return HasMany
     */
    public function assignedWorkouts(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'assigned_by');
    }

    /**
     * Get video progress for this user.
     * 
     * @return HasMany
     */
    public function videoProgress(): HasMany
    {
        return $this->hasMany(WorkoutVideoProgress::class);
    }
    
    /**
     * Get all schedules for the user (trainer or client).
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSchedules()
    {
        if ($this->isTrainerRole()) {
            return $this->trainerSchedules;
        } elseif ($this->isClientRole()) {
            return $this->clientSchedules;
        }
        
        return collect();
    }
    
    /**
     * Get trainer availability settings.
     * 
     * @return HasMany
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'trainer_id');
    }
    
    /**
     * Get trainer blocked times.
     * 
     * @return HasMany
     */
    public function blockedTimes(): HasMany
    {
        return $this->hasMany(BlockedTime::class, 'trainer_id');
    }
    
    /**
     * Get trainer session capacity settings.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sessionCapacity()
    {
        return $this->hasOne(SessionCapacity::class, 'trainer_id');
    }
    
    /**
     * Get trainer booking settings.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bookingSettings()
    {
        return $this->hasOne(BookingSetting::class, 'trainer_id');
    }
    
    /**
     * Get the specializations for the trainer.
     * 
     * Many-to-many relationship with Specialization model through trainer_specializations pivot table
     * Only applicable for users with 'trainer' role
     * 
     * @return BelongsToMany
     */
    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(
            Specialization::class,
            'trainer_specializations',
            'trainer_id',
            'specialization_id'
        )->withPivot(['created_at']);
    }
    
    /**
     * Check if trainer has a specific specialization.
     * 
     * @param int|Specialization $specialization
     * @return bool
     */
    public function hasSpecialization($specialization): bool
    {
        if (!$this->isTrainerRole()) {
            return false;
        }
        
        $specializationId = $specialization instanceof Specialization 
            ? $specialization->id 
            : $specialization;
            
        return $this->specializations()->where('specialization_id', $specializationId)->exists();
    }
    
    /**
     * Get trainer's specialization names as comma-separated string.
     * 
     * @return string
     */
    public function getSpecializationNamesAttribute(): string
    {
        if (!$this->isTrainerRole()) {
            return '';
        }
        
        return $this->specializations()->pluck('name')->implode(', ');
    }
    
    /**
     * Get nutrition plans assigned to this client.
     * 
     * @return HasMany
     */
    public function nutritionPlans(): HasMany
    {
        return $this->hasMany(NutritionPlan::class, 'client_id');
    }
    
    /**
     * Get nutrition plans created by this trainer.
     * 
     * @return HasMany
     */
    public function assignedNutritionPlans(): HasMany
    {
        return $this->hasMany(NutritionPlan::class, 'trainer_id');
    }
    
    /**
     * Check if SMS notifications are enabled for this user
     * 
     * @return bool
     */
    public function canReceiveSms(): bool
    {
        return $this->sms_notifications_enabled ?? true;
    }
    
    /**
     * Check if marketing SMS are enabled for this user
     * 
     * @return bool
     */
    public function canReceiveMarketingSms(): bool
    {
        return $this->sms_marketing_enabled ?? false;
    }
    
    /**
     * Check if current time is within quiet hours for SMS
     * 
     * @return bool
     */
    public function isInQuietHours(): bool
    {
        if (!$this->sms_quiet_start || !$this->sms_quiet_end) {
            return false;
        }
        
        // Get current time in user's timezone
        $userTimezone = $this->timezone ?? 'UTC';
        $currentTime = now()->setTimezone($userTimezone)->format('H:i:s');
        
        $quietStart = $this->sms_quiet_start;
        $quietEnd = $this->sms_quiet_end;
        
        // Handle cases where quiet hours span midnight
        if ($quietStart < $quietEnd) {
            // Same day: 14:00 - 16:00
            return $currentTime >= $quietStart && $currentTime < $quietEnd;
        } else {
            // Spans midnight: 22:00 - 08:00
            return $currentTime >= $quietStart || $currentTime < $quietEnd;
        }
    }
    
    /**
     * Check if user can receive a specific type of SMS notification
     * 
     * @param string $notificationType Type of notification (e.g., 'workout', 'appointment', 'general')
     * @return bool
     */
    public function canReceiveSmsType(string $notificationType): bool
    {
        if (!$this->canReceiveSms()) {
            return false;
        }
        
        // If no specific types are set, allow all
        if (!$this->sms_notification_types) {
            return true;
        }
        
        return in_array($notificationType, $this->sms_notification_types);
    }
    
    /**
     * Get default SMS notification types
     * 
     * @return array
     */
    public static function getDefaultSmsNotificationTypes(): array
    {
        return [
            'conversation' => 'Direct Messages',
            'workout' => 'Workout Reminders',
            'appointment' => 'Appointment Notifications',
            'progress' => 'Progress Updates',
            'general' => 'General Notifications'
        ];
    }
    
    /**
     * Get food diary entries for this client.
     * 
     * @return HasMany
     */
    public function foodDiaryEntries(): HasMany
    {
        return $this->hasMany(FoodDiary::class, 'client_id');
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone
     * @return bool
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-digit characters
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        // Check if phone number has valid length (10-15 digits)
        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
            return false;
        }
        
        // Basic format validation - should start with country code or local format
        return preg_match('/^(\+?[1-9]\d{1,14})$/', $cleanPhone);
    }
    
    /**
     * Format phone number for storage
     * 
     * @param string $phone
     * @return string
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure it starts with + if it doesn't already
        if (!str_starts_with($cleanPhone, '+')) {
            // If it's a 10-digit number, assume it's a local number and add default country code
            if (strlen($cleanPhone) === 10) {
                $cleanPhone = '+92' . $cleanPhone; // Pakistan country code
            } else {
                $cleanPhone = '+' . $cleanPhone;
            }
        }
        
        return $cleanPhone;
    }
    
    /**
     * Mutator for phone attribute - format phone number before saving
     * 
     * @param string $value
     * @return void
     */
    public function setPhoneAttribute($value): void
    {
        if ($value) {
            $this->attributes['phone'] = self::formatPhoneNumber($value);
        } else {
            $this->attributes['phone'] = null;
        }
    }
    
    /**
     * Accessor for phone attribute - return formatted phone number
     * 
     * @param string $value
     * @return string|null
     */
    public function getPhoneAttribute($value): ?string
    {
        return $value;
    }
}
