<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SmsMessage;
use App\Services\TwilioSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Mockery;

/**
 * SMS Integration Test Suite
 * 
 * Comprehensive testing for SMS functionality including:
 * - Twilio service integration
 * - API endpoints for SMS communication
 * - Database operations and relationships
 * - Message status tracking and validation
 * 
 * @package     Laravel CMS App
 * @subpackage  Tests\Feature
 * @category    SMS Communication Testing
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class SmsIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Trainer user instance for testing
     * 
     * @var User
     */
    protected $trainer;

    /**
     * Client user instance for testing
     * 
     * @var User
     */
    protected $client;

    /**
     * Mock Twilio SMS service
     * 
     * @var \Mockery\MockInterface
     */
    protected $mockTwilioService;

    /**
     * Set up test environment before each test
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with proper roles and phone numbers
        $this->trainer = User::factory()->create([
            'role' => 'trainer',
            'phone' => '+1234567890',
            'email' => 'trainer@test.com',
            'name' => 'Test Trainer'
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'phone' => '+0987654321',
            'email' => 'client@test.com',
            'name' => 'Test Client'
        ]);

        // Mock Twilio service for testing
        $this->mockTwilioService = Mockery::mock(TwilioSmsService::class);
        $this->app->instance(TwilioSmsService::class, $this->mockTwilioService);

        // Set up Twilio configuration for testing
        Config::set('services.twilio.sid', 'test_sid');
        Config::set('services.twilio.token', 'test_token');
        Config::set('services.twilio.from', '+1234567890');
    }

    /**
     * Test Twilio SMS Service initialization
     * 
     * @return void
     */
    public function test_twilio_service_initialization()
    {
        $service = new TwilioSmsService();
        $this->assertInstanceOf(TwilioSmsService::class, $service);
    }

    /**
     * Test phone number formatting functionality
     * 
     * @return void
     */
    public function test_phone_number_formatting()
    {
        $service = new TwilioSmsService();

        // Test various phone number formats
        $this->assertEquals('+11234567890', $service->formatPhoneNumber('1234567890'));
        $this->assertEquals('+11234567890', $service->formatPhoneNumber('(123) 456-7890'));
        $this->assertEquals('+12345678900', $service->formatPhoneNumber('+1 234 567 8900'));
    }

    /**
     * Test phone number validation
     * 
     * @return void
     */
    public function test_phone_number_validation()
    {
        $service = new TwilioSmsService();
        
        // Valid phone numbers
        $this->assertTrue($service->isValidPhoneNumber('+1234567890'));
        $this->assertTrue($service->isValidPhoneNumber('1234567890'));
        
        // Invalid phone numbers
        $this->assertFalse($service->isValidPhoneNumber('123'));
        $this->assertFalse($service->isValidPhoneNumber('invalid'));
        $this->assertFalse($service->isValidPhoneNumber(''));
    }

    /**
     * Test SMS message model creation
     * 
     * @return void
     */
    public function test_sms_message_model_creation()
    {
        $message = SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Test message',
            'status' => SmsMessage::STATUS_PENDING,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        $this->assertInstanceOf(SmsMessage::class, $message);
        $this->assertEquals('Test message', $message->message_content);
        $this->assertEquals(SmsMessage::STATUS_PENDING, $message->status);
    }

    /**
     * Test SMS message relationships
     * 
     * @return void
     */
    public function test_sms_message_relationships()
    {
        $message = SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Test message',
            'status' => SmsMessage::STATUS_PENDING,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        // Test relationships
        $this->assertEquals($this->trainer->id, $message->sender->id);
        $this->assertEquals($this->client->id, $message->recipient->id);
    }

    /**
     * Test send SMS API endpoint - successful request
     * 
     * @return void
     */
    public function test_send_sms_api_success()
    {
        Sanctum::actingAs($this->trainer);

        // Mock successful SMS sending
        $this->mockTwilioService->shouldReceive('sendSms')
            ->once()
            ->with($this->client->phone, 'Hello from trainer!')
            ->andReturn([
                'success' => true,
                'message_sid' => 'SM123456789',
                'status' => 'queued'
            ]);

        $response = $this->postJson('/api/sms/send', [
            'recipient_id' => $this->client->id,
            'message' => 'Hello from trainer!'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'SMS sent successfully'
                 ]);

        // Verify SMS message was stored in database
        $this->assertDatabaseHas('sms_messages', [
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'message_content' => 'Hello from trainer!',
            'status' => SmsMessage::STATUS_SENT,
            'direction' => SmsMessage::DIRECTION_OUTBOUND
        ]);
    }

    /**
     * Test send SMS API endpoint - validation errors
     * 
     * @return void
     */
    public function test_send_sms_api_validation_errors()
    {
        Sanctum::actingAs($this->trainer);

        // Test missing recipient_id
        $response = $this->postJson('/api/sms/send', [
            'message' => 'Hello!'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['recipient_id']);

        // Test missing message
        $response = $this->postJson('/api/sms/send', [
            'recipient_id' => $this->client->id
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);

        // Test empty message
        $response = $this->postJson('/api/sms/send', [
            'recipient_id' => $this->client->id,
            'message' => ''
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test get conversations API endpoint
     * 
     * @return void
     */
    public function test_get_conversations_api()
    {
        Sanctum::actingAs($this->trainer);

        // Create some test messages
        SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Hello client!',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        $response = $this->getJson('/api/sms/conversations');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'user' => [
                        'id',
                        'name',
                        'phone',
                        'profile_image',
                        'role'
                    ],
                    'latest_message',
                    'unread_count'
                ]
            ]
        ]);
    }

    /**
     * Test get conversation messages API endpoint
     * 
     * @return void
     */
    public function test_get_conversation_messages_api()
    {
        Sanctum::actingAs($this->trainer);

        // Create test messages
        SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Hello client!',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        $response = $this->getJson("/api/sms/conversation?user_id={$this->client->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'message_content',
                        'sender_id',
                        'recipient_id',
                        'status',
                        'direction',
                        'created_at'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]
        ]);
    }

    /**
     * Test mark messages as read API endpoint
     * 
     * @return void
     */
    public function test_mark_messages_read_api()
    {
        Sanctum::actingAs($this->trainer);

        // Create unread message
        $message = SmsMessage::create([
            'sender_id' => $this->client->id,
            'recipient_id' => $this->trainer->id,
            'sender_phone' => $this->client->phone,
            'recipient_phone' => $this->trainer->phone,
            'message_content' => 'Hello trainer!',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_INBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION,
            'read_at' => null
        ]);

        $response = $this->patchJson("/api/sms/mark-read", [
            'user_id' => $this->client->id
        ]);
        $response->assertStatus(200);

        // Verify message is marked as read
        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    /**
     * Test unauthorized access to SMS endpoints
     * 
     * @return void
     */
    public function test_unauthorized_access_to_sms_endpoints()
    {
        // Test without authentication
        $response = $this->postJson('/api/sms/send', [
            'recipient_id' => $this->client->id,
            'message' => 'Hello!'
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/sms/conversations');
        $response->assertStatus(401);

        $response = $this->getJson("/api/sms/conversation?user_id={$this->client->id}");
        $response->assertStatus(401);
    }

    /**
     * Test SMS message scopes
     * 
     * @return void
     */
    public function test_sms_message_scopes()
    {
        // Create messages with different statuses
        SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Delivered message',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Failed message',
            'status' => SmsMessage::STATUS_FAILED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION
        ]);

        // Test delivered scope
        $deliveredMessages = SmsMessage::successful()->get();
        $this->assertEquals(1, $deliveredMessages->count());
        $this->assertEquals('Delivered message', $deliveredMessages->first()->message_content);

        // Test failed scope
        $failedMessages = SmsMessage::failed()->get();
        $this->assertEquals(1, $failedMessages->count());
        $this->assertEquals('Failed message', $failedMessages->first()->message_content);
    }

    /**
     * Test conversation grouping functionality
     * 
     * @return void
     */
    public function test_conversation_grouping()
    {
        // Create multiple messages between trainer and client with explicit timestamps
        $firstMessage = SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'First message',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION,
        ]);
        $firstMessage->created_at = now()->subHours(2);
        $firstMessage->save();

        $replyMessage = SmsMessage::create([
            'sender_id' => $this->client->id,
            'recipient_id' => $this->trainer->id,
            'sender_phone' => $this->client->phone,
            'recipient_phone' => $this->trainer->phone,
            'message_content' => 'Reply message',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_INBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION,
        ]);
        $replyMessage->created_at = now()->subHour();
        $replyMessage->save();

        $latestMessage = SmsMessage::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->client->id,
            'sender_phone' => $this->trainer->phone,
            'recipient_phone' => $this->client->phone,
            'message_content' => 'Latest message',
            'status' => SmsMessage::STATUS_DELIVERED,
            'direction' => SmsMessage::DIRECTION_OUTBOUND,
            'message_type' => SmsMessage::TYPE_CONVERSATION,
        ]);
        $latestMessage->created_at = now();
        $latestMessage->save();

        // Test conversation retrieval
        $messages = SmsMessage::where(function($query) {
            $query->where('sender_id', $this->trainer->id)
                  ->where('recipient_id', $this->client->id);
        })->orWhere(function($query) {
            $query->where('sender_id', $this->client->id)
                  ->where('recipient_id', $this->trainer->id);
        })->orderBy('created_at', 'asc')->get();

        $this->assertEquals(3, $messages->count());
        $this->assertEquals('First message', $messages->first()->message_content);
        $this->assertEquals('Reply message', $messages->get(1)->message_content);
        $this->assertEquals('Latest message', $messages->last()->message_content);
    }
}