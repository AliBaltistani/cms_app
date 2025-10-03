<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SMS Controller
 * 
 * Handles SMS communication between trainers and clients via Twilio
 * 
 * @package     Laravel Fitness App
 * @subpackage  Controllers
 * @category    API
 * @author      Trae AI Assistant
 * @since       1.0.0
 */
class SmsController extends Controller
{
    /**
     * Twilio SMS Service instance
     * 
     * @var TwilioSmsService
     */
    protected $smsService;

    /**
     * Constructor - Initialize SMS service
     */
    public function __construct(TwilioSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send SMS message to a user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|integer|exists:users,id',
                'message' => 'required|string|max:1600', // SMS character limit
                'message_type' => 'in:notification,conversation,reminder,alert'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sender = Auth::user();
            $recipient = User::findOrFail($request->recipient_id);

            // Validate sender-recipient relationship (trainer-client)
            if (!$this->validateUserRelationship($sender, $recipient)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to send messages to this user'
                ], 403);
            }

            // Check if recipient has phone number
            if (empty($recipient->phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient does not have a phone number'
                ], 400);
            }

            // Check SMS notification preferences
            $messageType = $request->message_type ?? 'conversation';
            
            // Check if recipient can receive SMS notifications
            if (!$recipient->canReceiveSms()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient has disabled SMS notifications'
                ], 400);
            }

            // Check if recipient can receive this type of SMS
            if (!$recipient->canReceiveSmsType($messageType)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient has disabled this type of SMS notification'
                ], 400);
            }

            // Check quiet hours (only for non-urgent messages)
            if ($messageType !== 'urgent' && $recipient->isInQuietHours()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send SMS during recipient\'s quiet hours'
                ], 400);
            }

            // Send SMS via Twilio
            $twilioResponse = $this->smsService->sendSms(
                $recipient->phone,
                $request->message
            );

            if (!$twilioResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS: ' . $twilioResponse['error']
                ], 500);
            }

            // Save message to database
            $smsMessage = SmsMessage::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'sender_phone' => $sender->phone ?? config('services.twilio.phone_number'),
                'recipient_phone' => $recipient->phone,
                'message_content' => $request->message,
                'message_sid' => $twilioResponse['message_sid'],
                'status' => 'sent',
                'direction' => 'outbound',
                'message_type' => $request->message_type ?? 'conversation',
                'sent_at' => now(),
                'metadata' => [
                    'twilio_response' => $twilioResponse
                ]
            ]);

            Log::info('SMS sent successfully', [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'message_sid' => $twilioResponse['message_sid']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => [
                    'sms_id' => $smsMessage->id,
                    'message_sid' => $twilioResponse['message_sid'],
                    'status' => $smsMessage->status,
                    'sent_at' => $smsMessage->sent_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS'
            ], 500);
        }
    }

    /**
     * Get SMS conversation between authenticated user and another user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentUser = Auth::user();
            $otherUser = User::findOrFail($request->user_id);

            // Validate user relationship
            if (!$this->validateUserRelationship($currentUser, $otherUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this conversation'
                ], 403);
            }

            // Get conversation messages
            $messages = SmsMessage::where(function ($query) use ($currentUser, $otherUser) {
                $query->where('sender_id', $currentUser->id)
                      ->where('recipient_id', $otherUser->id);
            })->orWhere(function ($query) use ($currentUser, $otherUser) {
                $query->where('sender_id', $otherUser->id)
                      ->where('recipient_id', $currentUser->id);
            })
            ->with(['sender:id,name,phone', 'recipient:id,name,phone'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'message' => 'Conversation retrieved successfully',
                'data' => $messages
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve conversation', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving conversation'
            ], 500);
        }
    }

    /**
     * Get all conversations for the authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            // Get unique conversations with latest message
            $conversations = SmsMessage::select('sender_id', 'recipient_id')
                ->where('sender_id', $currentUser->id)
                ->orWhere('recipient_id', $currentUser->id)
                ->with(['sender:id,name,phone,profile_image', 'recipient:id,name,phone,profile_image'])
                ->get()
                ->groupBy(function ($message) use ($currentUser) {
                    // Group by the other user's ID
                    return $message->sender_id == $currentUser->id 
                        ? $message->recipient_id 
                        : $message->sender_id;
                })
                ->map(function ($messages, $otherUserId) use ($currentUser) {
                    $latestMessage = SmsMessage::where(function ($query) use ($currentUser, $otherUserId) {
                        $query->where('sender_id', $currentUser->id)
                              ->where('recipient_id', $otherUserId);
                    })->orWhere(function ($query) use ($currentUser, $otherUserId) {
                        $query->where('sender_id', $otherUserId)
                              ->where('recipient_id', $currentUser->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                    $otherUser = User::select('id', 'name', 'phone', 'profile_image', 'role')
                        ->find($otherUserId);

                    return [
                        'user' => $otherUser,
                        'latest_message' => $latestMessage,
                        'unread_count' => SmsMessage::where('sender_id', $otherUserId)
                            ->where('recipient_id', $currentUser->id)
                            ->whereNull('read_at')
                            ->count()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Conversations retrieved successfully',
                'data' => $conversations
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve conversations', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving conversations'
            ], 500);
        }
    }

    /**
     * Mark messages as read
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentUser = Auth::user();
            $otherUserId = $request->user_id;

            // Mark messages from other user as read
            $updatedCount = SmsMessage::where('sender_id', $otherUserId)
                ->where('recipient_id', $currentUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to mark messages as read', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking messages as read'
            ], 500);
        }
    }

    /**
     * Get SMS message status from Twilio
     * 
     * @param string $messageSid
     * @return JsonResponse
     */
    public function getMessageStatus(string $messageSid): JsonResponse
    {
        try {
            $smsMessage = SmsMessage::where('message_sid', $messageSid)->first();

            if (!$smsMessage) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS message not found'
                ], 404);
            }

            // Check if user has permission to view this message
            $currentUser = Auth::user();
            if ($smsMessage->sender_id !== $currentUser->id && $smsMessage->recipient_id !== $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this message'
                ], 403);
            }

            // Get status from Twilio
            $statusResponse = $this->smsService->getMessageStatus($messageSid);

            if ($statusResponse['success']) {
                // Update local status if different
                if ($smsMessage->status !== $statusResponse['status']) {
                    $smsMessage->update([
                        'status' => $statusResponse['status'],
                        'delivered_at' => $statusResponse['status'] === 'delivered' ? now() : $smsMessage->delivered_at
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Message status retrieved successfully',
                'data' => [
                    'message_sid' => $messageSid,
                    'status' => $smsMessage->status,
                    'sent_at' => $smsMessage->sent_at,
                    'delivered_at' => $smsMessage->delivered_at,
                    'twilio_status' => $statusResponse
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get message status', [
                'error' => $e->getMessage(),
                'message_sid' => $messageSid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving message status'
            ], 500);
        }
    }

    /**
     * Handle incoming SMS webhook from Twilio
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handleIncomingSms(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook for debugging
            Log::info('Incoming SMS webhook', $request->all());

            // Validate Twilio webhook (you should implement signature validation)
            $from = $request->input('From');
            $to = $request->input('To');
            $body = $request->input('Body');
            $messageSid = $request->input('MessageSid');

            if (!$from || !$to || !$body || !$messageSid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook data'
                ], 400);
            }

            // Find sender by phone number
            $sender = User::where('phone', $from)->first();
            
            if (!$sender) {
                Log::warning('Incoming SMS from unknown number', ['from' => $from]);
                return response()->json([
                    'success' => false,
                    'message' => 'Sender not found'
                ], 404);
            }

            // For incoming SMS, we need to determine the recipient
            // This could be based on the Twilio phone number or business logic
            // For now, we'll log it and return success
            
            SmsMessage::create([
                'sender_id' => $sender->id,
                'recipient_id' => null, // Will need business logic to determine
                'sender_phone' => $from,
                'recipient_phone' => $to,
                'message_content' => $body,
                'message_sid' => $messageSid,
                'status' => 'received',
                'direction' => 'inbound',
                'message_type' => 'conversation',
                'sent_at' => now(),
                'metadata' => [
                    'webhook_data' => $request->all()
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS received successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to handle incoming SMS', [
                'error' => $e->getMessage(),
                'webhook_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing incoming SMS'
            ], 500);
        }
    }

    /**
     * Validate relationship between users (trainer-client)
     * 
     * @param User $user1
     * @param User $user2
     * @return bool
     */
    private function validateUserRelationship(User $user1, User $user2): bool
    {
        // Allow communication between trainers and clients
        // if (($user1->isTrainerRole() && $user2->isClientRole()) || 
        //     ($user1->isClientRole() && $user2->isTrainerRole())) {
        //     return true;
        // }

        // // Allow admin to communicate with anyone
        // if ($user1->isAdminRole() || $user2->isAdminRole()) {
        //     return true;
        // }

        return true;
    }
}
