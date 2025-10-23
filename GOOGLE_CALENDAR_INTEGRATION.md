# Google Calendar Integration Documentation

## Overview

This document provides comprehensive information about the Google Calendar integration implemented in the Laravel CMS application. The integration allows trainers to automatically create Google Calendar events with Google Meet links for confirmed bookings.

## Features

- **Automatic Event Creation**: When a booking is confirmed, a Google Calendar event is automatically created
- **Google Meet Integration**: Each event includes a Google Meet link for virtual sessions
- **OAuth Authentication**: Secure Google OAuth 2.0 flow for trainer authentication
- **Event Management**: Automatic event updates and deletions when booking status changes
- **API Endpoints**: RESTful API for managing Google Calendar connections

## Setup and Configuration

### 1. Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Calendar API:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Google Calendar API"
   - Click "Enable"
4. Create OAuth 2.0 credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client IDs"
   - Choose "Web application"
   - Add authorized redirect URIs:
     - `http://localhost:8000/google/callback` (for local development)
     - `https://yourdomain.com/google/callback` (for production)

### 2. Environment Configuration

Add the following variables to your `.env` file:

```env
# Google Calendar Integration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/google/callback
```

### 3. Database Migration

The integration uses the existing `google_token` column in the `users` table. If this column doesn't exist, create a migration:

```php
Schema::table('users', function (Blueprint $table) {
    $table->json('google_token')->nullable();
});
```

## API Endpoints

### Trainer Google Calendar Management

#### Connect to Google Calendar
```
GET /api/trainer/google/connect
```
**Response:**
```json
{
    "success": true,
    "auth_url": "https://accounts.google.com/oauth2/auth?...",
    "message": "Google OAuth URL generated successfully"
}
```

#### Check Connection Status
```
GET /api/trainer/google/status
```
**Response:**
```json
{
    "success": true,
    "data": {
        "is_connected": true,
        "connected_email": "trainer@example.com",
        "last_checked": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### Disconnect Google Calendar
```
DELETE /api/trainer/google/disconnect
```
**Response:**
```json
{
    "success": true,
    "message": "Google Calendar disconnected successfully"
}
```

### OAuth Callback
```
GET /google/callback?code=...&state=...
```
This endpoint handles the OAuth callback and redirects to the trainer dashboard.

## Integration Points

### 1. Booking Creation

When a new booking is created and confirmed, the system automatically:
- Creates a Google Calendar event
- Generates a Google Meet link
- Stores the event ID in the schedule record

**Controllers Updated:**
- `ClientBookingController::store()` - Client booking creation
- `Admin\BookingController::store()` - Admin booking creation

### 2. Booking Status Updates

When a booking status changes:
- **PENDING → CONFIRMED**: Creates Google Calendar event
- **CONFIRMED → CANCELLED**: Deletes Google Calendar event
- **Status updates**: Updates existing Google Calendar event

**Controllers Updated:**
- `TrainerSchedulingController::updateBookingStatus()` - Trainer status updates
- `Admin\BookingController::update()` - Admin booking updates

### 3. Schedule Model Methods

New methods added to the `Schedule` model:

```php
// Create Google Calendar event
public function createGoogleCalendarEvent()

// Update existing Google Calendar event
public function updateGoogleCalendarEvent()

// Delete Google Calendar event
public function deleteGoogleCalendarEvent()

// Check if schedule has Google Calendar event
public function hasGoogleCalendarEvent()

// Check if schedule has Google Meet link
public function hasGoogleMeetLink()
```

## Testing the Integration

### 1. Prerequisites

- Google Cloud project with Calendar API enabled
- OAuth 2.0 credentials configured
- Environment variables set
- Trainer account in the system

### 2. Testing Flow

1. **Connect Google Calendar:**
   ```bash
   # Get auth URL
   curl -X GET "http://localhost:8000/api/trainer/google/connect" \
        -H "Authorization: Bearer {trainer_token}"
   
   # Visit the returned auth_url in browser
   # Complete OAuth flow
   ```

2. **Check Connection Status:**
   ```bash
   curl -X GET "http://localhost:8000/api/trainer/google/status" \
        -H "Authorization: Bearer {trainer_token}"
   ```

3. **Create a Booking:**
   ```bash
   curl -X POST "http://localhost:8000/api/client/bookings" \
        -H "Authorization: Bearer {client_token}" \
        -H "Content-Type: application/json" \
        -d '{
          "trainer_id": 1,
          "date": "2024-01-20",
          "start_time": "10:00",
          "end_time": "11:00",
          "notes": "Test session"
        }'
   ```

4. **Verify Event Creation:**
   - Check Google Calendar for the created event
   - Verify the response includes `meet_link` and `google_event_id`

### 3. Error Testing

Test error scenarios:
- Invalid Google credentials
- Expired tokens
- Network connectivity issues
- Calendar API rate limits

## Troubleshooting

### Common Issues

1. **"Invalid credentials" error:**
   - Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`
   - Check redirect URI matches Google Console settings

2. **"Token expired" error:**
   - The system automatically refreshes tokens
   - If refresh fails, user needs to reconnect

3. **"Calendar API not enabled" error:**
   - Enable Google Calendar API in Google Cloud Console

4. **"Insufficient permissions" error:**
   - Ensure OAuth scope includes Calendar access
   - User may need to re-authorize

### Logging

The integration logs important events:
- OAuth flow completion
- Event creation/update/deletion
- Error conditions

Check Laravel logs for debugging:
```bash
tail -f storage/logs/laravel.log
```

## Security Considerations

1. **Token Storage**: Google tokens are stored encrypted in the database
2. **Scope Limitation**: Only Calendar scope is requested
3. **User Validation**: Only trainers can connect Google Calendar
4. **Error Handling**: Sensitive information is not exposed in error messages

## Performance Considerations

1. **Async Processing**: Consider queuing Google API calls for better performance
2. **Rate Limiting**: Google Calendar API has rate limits
3. **Token Refresh**: Automatic token refresh prevents authentication failures
4. **Error Recovery**: Graceful handling of API failures

## Future Enhancements

1. **Bulk Operations**: Support for bulk event creation/updates
2. **Calendar Selection**: Allow trainers to choose specific calendars
3. **Notification Settings**: Configurable event notifications
4. **Recurring Events**: Support for recurring booking patterns
5. **Time Zone Handling**: Better time zone management for global users

## Support

For issues or questions regarding the Google Calendar integration:
1. Check the Laravel logs for error details
2. Verify Google Cloud Console configuration
3. Test API endpoints using the provided examples
4. Review this documentation for troubleshooting steps