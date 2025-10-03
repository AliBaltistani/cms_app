<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

/**
 * Phone OTP Password Reset Test Suite
 * 
 * Tests the complete phone-based OTP password reset flow
 * 
 * @package Tests\Feature
 * @author  [Your Name]
 * @since   1.0.0
 */
class PhoneOtpTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user for phone OTP testing
     * 
     * @var User
     */
    private User $testUser;

    /**
     * Setup test environment
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with phone number
        $this->testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+923001234567',
            'password' => Hash::make('password123'),
            'role' => 'client'
        ]);
    }

    /**
     * Test forgot password phone endpoint
     * 
     * @return void
     */
    public function test_forgot_password_phone_with_valid_phone(): void
    {
        $response = $this->postJson('/api/auth/forgot-password-phone', [
            'phone' => $this->testUser->phone
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'message_sid'
                    ]
                ]);

        // Verify OTP record was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'phone' => $this->testUser->phone,
            'otp_type' => PasswordReset::OTP_TYPE_PHONE
        ]);
    }

    /**
     * Test forgot password phone with invalid phone
     * 
     * @return void
     */
    public function test_forgot_password_phone_with_invalid_phone(): void
    {
        $response = $this->postJson('/api/auth/forgot-password-phone', [
            'phone' => '+923009999999' // Non-existent phone
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test forgot password phone with missing phone
     * 
     * @return void
     */
    public function test_forgot_password_phone_with_missing_phone(): void
    {
        $response = $this->postJson('/api/auth/forgot-password-phone', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test verify phone OTP with valid OTP
     * 
     * @return void
     */
    public function test_verify_phone_otp_with_valid_otp(): void
    {
        // Create OTP record
        $passwordReset = PasswordReset::createWithPhoneOTP($this->testUser->phone, 'test-token');

        $response = $this->postJson('/api/auth/verify-phone-otp', [
            'phone' => $this->testUser->phone,
            'otp' => $passwordReset->otp
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'reset_token',
                        'phone',
                        'expires_in'
                    ]
                ]);
    }

    /**
     * Test verify phone OTP with invalid OTP
     * 
     * @return void
     */
    public function test_verify_phone_otp_with_invalid_otp(): void
    {
        $response = $this->postJson('/api/auth/verify-phone-otp', [
            'phone' => $this->testUser->phone,
            'otp' => '999999' // Invalid OTP
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ]);
    }

    /**
     * Test reset password phone with valid data
     * 
     * @return void
     */
    public function test_reset_password_phone_with_valid_data(): void
    {
        // Create OTP record
        $passwordReset = PasswordReset::createWithPhoneOTP($this->testUser->phone, 'test-token');
        $newPassword = 'newpassword123';

        $response = $this->postJson('/api/auth/reset-password-phone', [
            'phone' => $this->testUser->phone,
            'otp' => $passwordReset->otp,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password reset successfully! Please login with your new password.'
                ]);

        // Verify password was updated
        $this->testUser->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->testUser->password));
    }

    /**
     * Test reset password phone with mismatched passwords
     * 
     * @return void
     */
    public function test_reset_password_phone_with_mismatched_passwords(): void
    {
        // Create OTP record
        $passwordReset = PasswordReset::createWithPhoneOTP($this->testUser->phone, 'test-token');

        $response = $this->postJson('/api/auth/reset-password-phone', [
            'phone' => $this->testUser->phone,
            'otp' => $passwordReset->otp,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test resend phone OTP
     * 
     * @return void
     */
    public function test_resend_phone_otp(): void
    {
        $response = $this->postJson('/api/auth/resend-phone-otp', [
            'phone' => $this->testUser->phone
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'message_sid'
                    ]
                ]);

        // Verify new OTP record was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'phone' => $this->testUser->phone,
            'otp_type' => PasswordReset::OTP_TYPE_PHONE
        ]);
    }

    /**
     * Test resend phone OTP rate limiting
     * 
     * @return void
     */
    public function test_resend_phone_otp_rate_limiting(): void
    {
        // First request
        $this->postJson('/api/auth/resend-phone-otp', [
            'phone' => $this->testUser->phone
        ]);

        // Immediate second request should be rate limited
        $response = $this->postJson('/api/auth/resend-phone-otp', [
            'phone' => $this->testUser->phone
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Rate Limited'
                ]);
    }

    /**
     * Test phone number validation in User model
     * 
     * @return void
     */
    public function test_user_phone_validation(): void
    {
        // Test valid phone numbers
        $this->assertTrue(User::isValidPhoneNumber('+923001234567'));
        $this->assertTrue(User::isValidPhoneNumber('03001234567'));
        $this->assertTrue(User::isValidPhoneNumber('+1234567890'));

        // Test invalid phone numbers
        $this->assertFalse(User::isValidPhoneNumber('123')); // Too short
        $this->assertFalse(User::isValidPhoneNumber('12345678901234567890')); // Too long
        $this->assertFalse(User::isValidPhoneNumber('abc123')); // Contains letters
    }

    /**
     * Test phone number formatting in User model
     * 
     * @return void
     */
    public function test_user_phone_formatting(): void
    {
        // Test formatting local Pakistani number
        $this->assertEquals('+923001234567', User::formatPhoneNumber('03001234567'));
        $this->assertEquals('+923001234567', User::formatPhoneNumber('+923001234567'));
        $this->assertEquals('+923001234567', User::formatPhoneNumber('92 300 123 4567'));

        // Test formatting international number
        $this->assertEquals('+1234567890', User::formatPhoneNumber('1234567890'));
        $this->assertEquals('+1234567890', User::formatPhoneNumber('+1234567890'));
    }

    /**
     * Test PasswordReset model phone OTP methods
     * 
     * @return void
     */
    public function test_password_reset_phone_otp_methods(): void
    {
        $phone = '+923001234567';
        $token = 'test-token';

        // Test creating phone OTP
        $passwordReset = PasswordReset::createWithPhoneOTP($phone, $token);
        
        $this->assertNotNull($passwordReset);
        $this->assertEquals($phone, $passwordReset->phone);
        $this->assertEquals(PasswordReset::OTP_TYPE_PHONE, $passwordReset->otp_type);
        $this->assertNotNull($passwordReset->otp);
        $this->assertEquals(6, strlen($passwordReset->otp));

        // Test verifying phone OTP
        $verifiedReset = PasswordReset::verifyPhoneOTP($phone, $passwordReset->otp);
        $this->assertNotNull($verifiedReset);
        $this->assertEquals($passwordReset->id, $verifiedReset->id);

        // Test verifying invalid OTP
        $invalidReset = PasswordReset::verifyPhoneOTP($phone, '999999');
        $this->assertNull($invalidReset);
    }
}