<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset OTP Mail
 * 
 * Sends OTP via email for password reset functionality
 * 
 * @package     Laravel CMS App
 * @subpackage  Mail
 * @category    Authentication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class PasswordResetOTP extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The OTP code
     *
     * @var string
     */
    public $otp;

    /**
     * The user's name
     *
     * @var string
     */
    public $userName;

    /**
     * Create a new message instance.
     *
     * @param string $otp The OTP code
     * @param string $userName The user's name
     */
    public function __construct(string $otp, string $userName)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset OTP - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'otp' => $this->otp,
                'userName' => $this->userName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
