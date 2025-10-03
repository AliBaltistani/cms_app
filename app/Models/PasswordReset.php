<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Password Reset Model
 * 
 * Handles password reset tokens and OTP functionality
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Authentication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class PasswordReset extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_reset_tokens';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone',
        'token',
        'otp',
        'otp_expires_at',
        'is_used',
        'attempts',
        'otp_type',
        'created_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * OTP type constants
     */
    const OTP_TYPE_EMAIL = 'email';
    const OTP_TYPE_PHONE = 'phone';

    /**
     * Generate a 6-digit OTP
     *
     * @return string
     */
    public static function generateOTP(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if OTP is expired
     *
     * @return bool
     */
    public function isOTPExpired(): bool
    {
        return $this->otp_expires_at && Carbon::now()->isAfter($this->otp_expires_at);
    }

    /**
     * Check if OTP is valid (not used and not expired)
     *
     * @return bool
     */
    public function isOTPValid(): bool
    {
        return !$this->is_used && !$this->isOTPExpired() && $this->attempts < 3;
    }

    /**
     * Mark OTP as used
     *
     * @return bool
     */
    public function markOTPAsUsed(): bool
    {
        $this->is_used = true;
        return $this->save();
    }

    /**
     * Increment failed attempts
     *
     * @return bool
     */
    public function incrementAttempts(): bool
    {
        $this->attempts++;
        return $this->save();
    }

    /**
     * Create or update password reset record with OTP for email
     *
     * @param string $email
     * @param string $token
     * @return PasswordReset
     */
    public static function createWithOTP(string $email, string $token): PasswordReset
    {
        $otp = self::generateOTP();
        $otpExpiresAt = Carbon::now()->addMinutes(15); // OTP expires in 15 minutes

        return self::updateOrCreate(
            ['email' => $email, 'otp_type' => self::OTP_TYPE_EMAIL],
            [
                'token' => Hash::make($token),
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'is_used' => false,
                'attempts' => 0,
                'otp_type' => self::OTP_TYPE_EMAIL,
                'created_at' => Carbon::now()
            ]
        );
    }

    /**
     * Create or update password reset record with OTP for phone
     *
     * @param string $phone
     * @param string $token
     * @return PasswordReset
     */
    public static function createWithPhoneOTP(string $phone, string $token): PasswordReset
    {
        $otp = self::generateOTP();
        $otpExpiresAt = Carbon::now()->addMinutes(15); // OTP expires in 15 minutes

        return self::updateOrCreate(
            ['phone' => $phone, 'otp_type' => self::OTP_TYPE_PHONE],
            [
                'email' => null, // Explicitly set email to null for phone-based resets
                'token' => Hash::make($token),
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'is_used' => false,
                'attempts' => 0,
                'otp_type' => self::OTP_TYPE_PHONE,
                'created_at' => Carbon::now()
            ]
        );
    }

    /**
     * Verify OTP for given email
     *
     * @param string $email
     * @param string $otp
     * @return PasswordReset|null
     */
    public static function verifyOTP(string $email, string $otp): ?PasswordReset
    {
        $resetRecord = self::where('email', $email)
            ->where('otp', $otp)
            ->where('otp_type', self::OTP_TYPE_EMAIL)
            ->first();

        if (!$resetRecord || !$resetRecord->isOTPValid()) {
            if ($resetRecord) {
                $resetRecord->incrementAttempts();
            }
            return null;
        }

        return $resetRecord;
    }

    /**
     * Verify OTP for given phone number
     *
     * @param string $phone
     * @param string $otp
     * @return PasswordReset|null
     */
    public static function verifyPhoneOTP(string $phone, string $otp): ?PasswordReset
    {
        $resetRecord = self::where('phone', $phone)
            ->where('otp', $otp)
            ->where('otp_type', self::OTP_TYPE_PHONE)
            ->first();

        if (!$resetRecord || !$resetRecord->isOTPValid()) {
            if ($resetRecord) {
                $resetRecord->incrementAttempts();
            }
            return null;
        }

        return $resetRecord;
    }
}
