# Trainer Google Calendar Integration

## Overview

This document describes the comprehensive Google Calendar integration system for trainers, which allows individual trainers to connect their Google Calendar accounts and synchronize their availability with the booking system.

## Features

### 1. Trainer-Specific Google Calendar Connection
- Individual trainers can connect their own Google Calendar accounts
- Separate OAuth flow for each trainer
- Independent connection management per trainer

### 2. Graceful Fallback System
- When a trainer is not connected to Google Calendar, the system automatically falls back to local availability
- No disruption to the booking process
- Seamless user experience regardless of connection status

### 3. API Endpoints for Trainer Management
- RESTful API endpoints for trainer Google Calendar operations
- Support for mobile apps and external integrations

## Implementation Details

### Controllers

#### GoogleController
Located: `app/Http/Controllers/GoogleController.php`

**New Methods Added:**

1. **`trainerConnect(Request $request)`**
   - Initiates Google OAuth flow for a specific trainer
   - Parameters: `trainer_id`
   - Returns: Redirect to Google OAuth URL

2. **`trainerCallback(Request $request)`**
   - Handles Google OAuth callback for trainer connections
   - Exchanges authorization code for access token
   - Stores trainer-specific tokens

3. **`trainerStatus(Request $request)`**
   - Checks connection status for a specific trainer
   - Parameters: `trainer_id`
   - Returns: JSON with connection status and email

4. **`trainerDisconnect(Request $request)`**
   - Disconnects a trainer's Google Calendar
   - Revokes tokens and removes from database
   - Parameters: `trainer_id`

#### BookingController
Located: `app/Http/Controllers/Admin/BookingController.php`

**Enhanced Methods:**

1. **`getTrainerAvailableSlots(Request $request)`**
   - **Enhanced**: Now handles non-connected trainers gracefully
   - Falls back to local availability when Google Calendar is not connected
   - Maintains consistent response format

2. **`getLocalAvailableSlots($trainerId, $startDate, $endDate)`** *(New)*
   - Generates available slots using local trainer availability
   - Considers weekly availability, blocked times, and existing bookings
   - Used as fallback when Google Calendar is not connected

3. **`generateTimeSlots($startTime, $endTime, $slotDuration)`** *(New)*
   - Helper method to generate time slots
   - Configurable slot duration (default: 60 minutes)

### API Routes

Located: `routes/api.php`

```php
// Trainer Google Calendar Management
Route::prefix('google/trainer')->group(function () {
    Route::get('/connect', [GoogleController::class, 'trainerConnect']);
    Route::get('/status', [GoogleController::class, 'trainerStatus']);
    Route::post('/disconnect', [GoogleController::class, 'trainerDisconnect']);
});
```

### Database Schema

The existing `google_tokens` table is used with trainer-specific entries:

```sql
google_tokens
├── id (Primary Key)
├── user_id (Foreign Key to users table - trainer ID)
├── access_token (Encrypted)
├── refresh_token (Encrypted)
├── expires_at (Timestamp)
├── email (Google account email)
├── created_at
└── updated_at
```

## Usage Examples

### 1. Connect a Trainer to Google Calendar

**Web Interface:**
```php
// Redirect trainer to Google OAuth
$response = app(GoogleController::class)->trainerConnect(
    Request::create('/google/trainer/connect', 'GET', ['trainer_id' => 123])
);
```

**API Call:**
```bash
GET /api/google/trainer/connect?trainer_id=123
```

### 2. Check Trainer Connection Status

**API Call:**
```bash
GET /api/google/trainer/status?trainer_id=123
```

**Response:**
```json
{
    "connected": true,
    "email": "trainer@example.com",
    "expires_at": "2024-02-15T10:30:00Z"
}
```

### 3. Get Available Slots (with Fallback)

**Controller Method:**
```php
$request = Request::create('/admin/bookings/trainer-slots', 'GET', [
    'trainer_id' => 123,
    'start_date' => '2024-01-15',
    'end_date' => '2024-01-21'
]);

$response = app(BookingController::class)->getTrainerAvailableSlots($request);
```

**Response Format:**
```json
{
    "success": true,
    "data": {
        "available_slots": [
            {
                "start": "2024-01-15T09:00:00.000Z",
                "end": "2024-01-15T10:00:00.000Z",
                "start_time": "09:00",
                "end_time": "10:00",
                "date": "2024-01-15",
                "display": "9:00 AM - 10:00 AM"
            }
        ]
    }
}
```

### 4. Disconnect Trainer from Google Calendar

**API Call:**
```bash
POST /api/google/trainer/disconnect
Content-Type: application/json

{
    "trainer_id": 123
}
```

## Fallback Logic

### When Google Calendar is Connected:
1. Check trainer's Google Calendar connection
2. Refresh token if expired
3. Retrieve busy times from Google Calendar
4. Generate available slots excluding busy times

### When Google Calendar is NOT Connected:
1. Detect missing or invalid Google Calendar connection
2. Automatically switch to local availability system
3. Use trainer's weekly availability settings
4. Consider blocked times and existing bookings
5. Generate available slots based on local data

### Local Availability Calculation:
1. **Weekly Availability**: Check trainer's morning/evening availability for each day
2. **Blocked Times**: Exclude manually blocked time periods
3. **Existing Bookings**: Exclude already booked time slots
4. **Time Slots**: Generate hourly slots (configurable duration)

## Error Handling

### Connection Errors:
- Invalid or expired tokens → Automatic fallback to local availability
- Google API errors → Graceful degradation to local system
- Missing trainer → Proper error response

### Validation:
- Trainer ID validation
- Date range validation
- Token expiration handling

## Security Considerations

1. **Token Encryption**: All Google tokens are encrypted in the database
2. **Trainer Authorization**: Only authorized users can manage trainer connections
3. **Token Refresh**: Automatic token refresh with proper error handling
4. **Scope Limitation**: Minimal required Google Calendar scopes

## Testing

### Test Files Created:
1. `test_trainer_slots.php` - Tests the enhanced getTrainerAvailableSlots method
2. `test_complete_integration.php` - Comprehensive integration testing

### Test Coverage:
- ✅ Trainer connection management
- ✅ Available slots retrieval (connected and non-connected)
- ✅ Fallback system functionality
- ✅ API endpoint availability
- ✅ Method implementation verification

## Configuration

### Environment Variables:
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=your_app_url/google/callback
```

### Google Calendar Scopes:
- `https://www.googleapis.com/auth/calendar.readonly`
- `https://www.googleapis.com/auth/calendar.events`

## Future Enhancements

1. **Bulk Operations**: Connect/disconnect multiple trainers
2. **Calendar Sync**: Two-way synchronization with Google Calendar
3. **Event Creation**: Automatic event creation in trainer's calendar
4. **Notification System**: Email notifications for connection status changes
5. **Analytics**: Connection usage and availability statistics

## Troubleshooting

### Common Issues:

1. **"Trainer not connected" errors**
   - Solution: System automatically falls back to local availability
   - No action required from user

2. **Token expiration**
   - Solution: Automatic token refresh implemented
   - Manual reconnection if refresh fails

3. **Missing availability data**
   - Check trainer's weekly availability settings
   - Verify blocked times configuration

### Debug Mode:
Enable Laravel debug mode to see detailed error messages during development.

## Conclusion

The trainer Google Calendar integration provides a robust, scalable solution for managing trainer availability with intelligent fallback mechanisms. The system ensures uninterrupted service regardless of Google Calendar connection status while providing enhanced functionality for connected trainers.