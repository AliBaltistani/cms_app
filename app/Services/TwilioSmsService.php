<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Twilio SMS Service
 * 
 * Handles SMS operations using Twilio API for trainer-client communication
 * Provides secure and reliable SMS functionality with comprehensive error handling
 * 
 * @package     Laravel CMS App
 * @subpackage  Services
 * @category    SMS Communication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class TwilioSmsService
{
    /**
     * Validate phone number format
     * 
     * @param string $phoneNumber Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Remove all non-digit characters except +
        $cleanNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Check if it's empty after cleaning
        if (empty($cleanNumber)) {
            return false;
        }
        
        // Check for minimum length (at least 10 digits)
        $digitCount = strlen(preg_replace('/[^\d]/', '', $cleanNumber));
        if ($digitCount < 10) {
            return false;
        }
        
        // Check for maximum length (no more than 15 digits as per E.164)
        if ($digitCount > 15) {
            return false;
        }
        
        // Basic format validation
        // Should start with + for international or be 10+ digits for domestic
        if (strpos($cleanNumber, '+') === 0) {
            // International format: +[country code][number]
            return preg_match('/^\+\d{10,14}$/', $cleanNumber);
        } else {
            // Domestic format: 10+ digits
            return preg_match('/^\d{10,15}$/', $cleanNumber);
        }
    }

    /**
     * Twilio client instance
     * 
     * @var Client
     */
    protected $twilioClient;

    /**
     * Twilio phone number for sending SMS
     * 
     * @var string
     */
    protected $twilioPhoneNumber;

    /**
     * Constructor - Initialize Twilio client with credentials
     * 
     * @throws \Exception When Twilio credentials are not configured
     */
    public function __construct()
    {
        try {
            // Get Twilio credentials from environment
            $twilioSid = config('services.twilio.sid');
            $twilioSecret = config('services.twilio.secret');
            $this->twilioPhoneNumber = config('services.twilio.phone_number');

            // Validate credentials are present
            if (empty($twilioSid) || empty($twilioSecret) || empty($this->twilioPhoneNumber)) {
                throw new \Exception('Twilio credentials not properly configured in environment');
            }

            // Initialize Twilio client
            $this->twilioClient = new Client($twilioSid, $twilioSecret);

            Log::info('TwilioSmsService initialized successfully');

        } catch (\Exception $e) {
            Log::error('Failed to initialize TwilioSmsService: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send SMS message to a single recipient
     * 
     * @param  string $toPhoneNumber Recipient phone number (E.164 format recommended)
     * @param  string $message SMS message content (max 1600 characters)
     * @param  array $options Additional options for SMS sending
     * @return array Response with success status and message details
     * @throws \Exception When SMS sending fails
     */
    public function sendSms(string $toPhoneNumber, string $message, array $options = []): array
    {
        try {
            // Validate input parameters
            if (empty($toPhoneNumber) || empty($message)) {
                throw new \Exception('Phone number and message are required');
            }

            // Validate message length (Twilio limit is 1600 characters)
            if (strlen($message) > 1600) {
                throw new \Exception('Message exceeds maximum length of 1600 characters');
            }

            // Format phone number if needed
            $formattedPhoneNumber = $this->formatPhoneNumber($toPhoneNumber);

            // Prepare SMS parameters
            $smsParams = [
                'from' => $this->twilioPhoneNumber,
                'body' => $message
            ];

            // Add optional parameters
            if (isset($options['statusCallback'])) {
                $smsParams['statusCallback'] = $options['statusCallback'];
            }

            // Send SMS via Twilio
            $twilioMessage = $this->twilioClient->messages->create(
                $formattedPhoneNumber,
                $smsParams
            );

            // Log successful SMS
            Log::info('SMS sent successfully', [
                'message_sid' => $twilioMessage->sid,
                'to' => $formattedPhoneNumber,
                'status' => $twilioMessage->status,
                'message_length' => strlen($message)
            ]);

            return [
                'success' => true,
                'message_sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
                'to' => $formattedPhoneNumber,
                'from' => $this->twilioPhoneNumber,
                'message' => 'SMS sent successfully',
                'sent_at' => now()->toISOString()
            ];

        } catch (TwilioException $e) {
            // Handle Twilio-specific errors
            Log::error('Twilio SMS error', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'to_number' => $toPhoneNumber ?? 'unknown',
                'message_preview' => substr($message ?? '', 0, 50) . '...'
            ]);

            return [
                'success' => false,
                'error' => 'SMS delivery failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'to' => $toPhoneNumber
            ];

        } catch (\Exception $e) {
            // Handle general errors
            Log::error('SMS service error', [
                'error_message' => $e->getMessage(),
                'to_number' => $toPhoneNumber ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => 'SMS service error: ' . $e->getMessage(),
                'to' => $toPhoneNumber
            ];
        }
    }

    /**
     * Send SMS to multiple recipients (bulk SMS)
     * 
     * @param  array $phoneNumbers Array of recipient phone numbers
     * @param  string $message SMS message content
     * @param  array $options Additional options for bulk SMS
     * @return array Response with results for each recipient
     */
    public function sendBulkSms(array $phoneNumbers, string $message, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        try {
            // Validate input
            if (empty($phoneNumbers) || empty($message)) {
                throw new \Exception('Phone numbers array and message are required');
            }

            // Process each phone number
            foreach ($phoneNumbers as $phoneNumber) {
                $result = $this->sendSms($phoneNumber, $message, $options);
                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Add small delay to avoid rate limiting
                if (count($phoneNumbers) > 1) {
                    usleep(100000); // 100ms delay
                }
            }

            Log::info('Bulk SMS completed', [
                'total_recipients' => count($phoneNumbers),
                'successful' => $successCount,
                'failed' => $failureCount
            ]);

            return [
                'success' => true,
                'total_recipients' => count($phoneNumbers),
                'successful_sends' => $successCount,
                'failed_sends' => $failureCount,
                'results' => $results,
                'message' => "Bulk SMS completed: {$successCount} successful, {$failureCount} failed"
            ];

        } catch (\Exception $e) {
            Log::error('Bulk SMS error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Bulk SMS failed: ' . $e->getMessage(),
                'results' => $results
            ];
        }
    }

    /**
     * Get SMS message status by message SID
     * 
     * @param  string $messageSid Twilio message SID
     * @return array Message status information
     */
    public function getMessageStatus(string $messageSid): array
    {
        try {
            if (empty($messageSid)) {
                throw new \Exception('Message SID is required');
            }

            // Fetch message details from Twilio
            $message = $this->twilioClient->messages($messageSid)->fetch();

            return [
                'success' => true,
                'message_sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'body' => $message->body,
                'date_created' => $message->dateCreated->format('Y-m-d H:i:s'),
                'date_sent' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage
            ];

        } catch (TwilioException $e) {
            Log::error('Failed to fetch message status', [
                'message_sid' => $messageSid,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch message status: ' . $e->getMessage(),
                'message_sid' => $messageSid
            ];
        }
    }

    /**
     * Format phone number to E.164 standard
     * 
     * @param  string $phoneNumber Raw phone number
     * @return string Formatted phone number
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Add country code if not present (assuming US +1 for now)
        if (strlen($cleaned) === 10) {
            $cleaned = '1' . $cleaned;
        }

        // Add + prefix for E.164 format
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Validate phone number format
     * 
     * @param  string $phoneNumber Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        // Basic validation for E.164 format
        $pattern = '/^\+[1-9]\d{1,14}$/';
        return preg_match($pattern, $this->formatPhoneNumber($phoneNumber));
    }

    /**
     * Get account information and SMS usage statistics
     * 
     * @return array Account information
     */
    public function getAccountInfo(): array
    {
        try {
            $account = $this->twilioClient->api->v2010->accounts(
                $this->twilioClient->getAccountSid()
            )->fetch();

            return [
                'success' => true,
                'account_sid' => $account->sid,
                'friendly_name' => $account->friendlyName,
                'status' => $account->status,
                'type' => $account->type,
                'date_created' => $account->dateCreated->format('Y-m-d H:i:s')
            ];

        } catch (TwilioException $e) {
            Log::error('Failed to fetch account info: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to fetch account information: ' . $e->getMessage()
            ];
        }
    }
}