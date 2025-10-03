# SMS Communication API Documentation

## Overview

The SMS Communication API provides secure messaging functionality between trainers and clients in the Go Globe CMS application. Built on Laravel with Twilio integration, it ensures reliable message delivery with comprehensive error handling and authentication.

## Base URL
```
https://your-domain.com/api/sms
```

## Authentication

All SMS endpoints require authentication using Laravel Sanctum tokens, except for webhook endpoints which are publicly accessible for Twilio callbacks.

### Headers Required
```
Authorization: Bearer {your-sanctum-token}
Content-Type: application/json
Accept: application/json
```

## Endpoints

### 1. Send SMS Message

**POST** `/api/sms/send`

Send an SMS message to another user (trainer-client communication only).

#### Request Body
```json
{
    "recipient_id": 123,
    "message": "Hello! This is a test message.",
    "message_type": "conversation"
}
```

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| recipient_id | integer | Yes | ID of the user to send message to |
| message | string | Yes | Message content (max 1600 characters) |
| message_type | string | No | Type of message (default: "conversation") |

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "SMS sent successfully",
    "data": {
        "id": 456,
        "sender_id": 789,
        "recipient_id": 123,
        "message_content": "Hello! This is a test message.",
        "status": "sent",
        "direction": "outbound",
        "message_type": "conversation",
        "sent_at": "2025-01-20T10:30:00Z",
        "created_at": "2025-01-20T10:30:00Z"
    }
}
```

#### Response (Validation Error - 422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "recipient_id": ["The recipient id field is required."],
        "message": ["The message field is required."]
    }
}
```

#### Response (Unauthorized - 403)
```json
{
    "success": false,
    "message": "You are not authorized to send messages to this user"
}
```

### 2. Get Conversations

**GET** `/api/sms/conversations`

Retrieve all conversations for the authenticated user with latest message and unread count.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number for pagination (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Conversations retrieved successfully",
    "data": [
        {
            "user": {
                "id": 123,
                "name": "John Doe",
                "phone": "+1234567890",
                "profile_image": "https://example.com/profile.jpg",
                "role": "client"
            },
            "latest_message": {
                "id": 456,
                "message_content": "Latest message content",
                "sender_id": 789,
                "recipient_id": 123,
                "created_at": "2025-01-20T10:30:00Z",
                "read_at": null
            },
            "unread_count": 3
        }
    ]
}
```

### 3. Get Conversation Messages

**GET** `/api/sms/conversation`

Retrieve messages between authenticated user and another specific user.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | ID of the other user in conversation |
| page | integer | No | Page number for pagination (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Conversation retrieved successfully",
    "data": {
        "data": [
            {
                "id": 456,
                "sender_id": 789,
                "recipient_id": 123,
                "message_content": "Hello! How are you?",
                "status": "delivered",
                "direction": "outbound",
                "message_type": "conversation",
                "sent_at": "2025-01-20T10:30:00Z",
                "read_at": null,
                "created_at": "2025-01-20T10:30:00Z",
                "sender": {
                    "id": 789,
                    "name": "Jane Smith",
                    "phone": "+1987654321"
                },
                "recipient": {
                    "id": 123,
                    "name": "John Doe",
                    "phone": "+1234567890"
                }
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 15,
        "last_page": 1
    }
}
```

### 4. Mark Messages as Read

**PATCH** `/api/sms/mark-read`

Mark all messages from a specific user as read.

#### Request Body
```json
{
    "user_id": 123
}
```

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | ID of the user whose messages to mark as read |

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Messages marked as read successfully",
    "data": {
        "marked_count": 5
    }
}
```

### 5. Get Message Status

**GET** `/api/sms/status/{message_id}`

Get the delivery status of a specific message.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| message_id | integer | Yes | ID of the message to check status |

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Message status retrieved successfully",
    "data": {
        "id": 456,
        "status": "delivered",
        "sent_at": "2025-01-20T10:30:00Z",
        "delivered_at": "2025-01-20T10:30:15Z",
        "read_at": null,
        "message_sid": "SM1234567890abcdef"
    }
}
```

## Webhook Endpoints (Public)

### 1. Incoming SMS Webhook

**POST** `/api/sms/webhook/incoming`

Twilio webhook endpoint for receiving incoming SMS messages.

#### Request Body (Twilio Format)
```
From=+1234567890
To=+1987654321
Body=Hello, this is an incoming message
MessageSid=SM1234567890abcdef
```

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "SMS received successfully"
}
```

### 2. Status Update Webhook

**POST** `/api/sms/webhook/status`

Twilio webhook endpoint for receiving message status updates.

#### Request Body (Twilio Format)
```
MessageSid=SM1234567890abcdef
MessageStatus=delivered
```

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Status updated successfully"
}
```

## Error Responses

### Common Error Codes

| Status Code | Description |
|-------------|-------------|
| 400 | Bad Request - Invalid request format |
| 401 | Unauthorized - Missing or invalid authentication token |
| 403 | Forbidden - User not authorized for this action |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 500 | Internal Server Error - Server-side error |

### Error Response Format
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Specific validation error"]
    }
}
```

## User Relationship Validation

The SMS system enforces communication rules:

- **Trainers** can communicate with **Clients**
- **Clients** can communicate with **Trainers**  
- **Admins** can communicate with **anyone**
- Users with the same role cannot communicate with each other

## Message Status Types

| Status | Description |
|--------|-------------|
| pending | Message queued for sending |
| sent | Message sent to Twilio |
| delivered | Message delivered to recipient |
| failed | Message delivery failed |
| undelivered | Message could not be delivered |

## Message Direction Types

| Direction | Description |
|-----------|-------------|
| inbound | Incoming message (received) |
| outbound | Outgoing message (sent) |

## Rate Limiting

- **Send SMS**: 10 requests per minute per user
- **Get Conversations**: 30 requests per minute per user
- **Get Messages**: 60 requests per minute per user
- **Mark as Read**: 20 requests per minute per user

## SDK Examples

### JavaScript (Axios)
```javascript
// Send SMS
const response = await axios.post('/api/sms/send', {
    recipient_id: 123,
    message: 'Hello from JavaScript!'
}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});

// Get conversations
const conversations = await axios.get('/api/sms/conversations', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
```

### PHP (cURL)
```php
// Send SMS
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://your-domain.com/api/sms/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'recipient_id' => 123,
        'message' => 'Hello from PHP!'
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);
$response = curl_exec($curl);
curl_close($curl);
```

### Python (Requests)
```python
import requests

# Send SMS
response = requests.post(
    'https://your-domain.com/api/sms/send',
    json={
        'recipient_id': 123,
        'message': 'Hello from Python!'
    },
    headers={
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
)
```

## Testing

The SMS API includes comprehensive test coverage. Run tests with:

```bash
php artisan test tests/Feature/SmsIntegrationTest.php
```

## Configuration

### Environment Variables
```env
TWILIO_SID=your_twilio_account_sid
TWILIO_SECRET=your_twilio_auth_token
TWILIO_PHONE_NUMBER=your_twilio_phone_number
```

### Service Configuration
Add to `config/services.php`:
```php
'twilio' => [
    'sid' => env('TWILIO_SID'),
    'secret' => env('TWILIO_SECRET'),
    'phone_number' => env('TWILIO_PHONE_NUMBER'),
],
```

## Security Considerations

1. **Authentication**: All endpoints require valid Sanctum tokens
2. **Authorization**: User relationship validation prevents unauthorized communication
3. **Input Validation**: All inputs are validated and sanitized
4. **Rate Limiting**: Prevents abuse and spam
5. **Webhook Security**: Implement Twilio signature validation for production
6. **Data Encryption**: Sensitive data is encrypted in transit and at rest

## Support

For technical support or questions about the SMS API:
- Email: support@go-globe.com
- Documentation: https://your-domain.com/docs/sms-api
- GitHub Issues: https://github.com/your-repo/issues

---

**Version**: 1.0.0  
**Last Updated**: January 20, 2025  
**Maintained by**: Go Globe CMS Team