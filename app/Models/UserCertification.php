<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * UserCertification Model
 * 
 * Represents trainer certifications with document uploads
 * 
 * @property int $id
 * @property int $user_id
 * @property string $certificate_name
 * @property string|null $doc
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read User $user
 */
class UserCertification extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     */
    protected $table = 'user_certifications';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'credential_id',
        'credential_url',
        'certificate_name', // Keep for backward compatibility
        'doc'
    ];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the user that owns the certification.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the full URL for the certificate document.
     * 
     * @return string|null
     */
    public function getDocUrlAttribute(): ?string
    {
        return $this->doc ? asset('storage/' . $this->doc) : null;
    }
}
