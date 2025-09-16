# Go Globe CMS API Documentation

## Overview

This document provides comprehensive documentation for the Go Globe CMS API. The API provides complete CRUD operations for authentication, user management, goals, workouts, and workout videos.

**Base URL:** `http://your-domain.com/api`
**API Version:** 1.0.0
**Authentication:** Laravel Sanctum (Bearer Token)

## Table of Contents

1. [Authentication](#authentication)
2. [User Management](#user-management)
3. [Goals Management](#goals-management)
4. [Workouts Management](#workouts-management)
5. [Workout Videos Management](#workout-videos-management)
6. [System Information](#system-information)
7. [Error Handling](#error-handling)
8. [Rate Limiting](#rate-limiting)
9. [Response Format](#response-format)

---

## Authentication

### Register User
**POST** `/api/auth/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+1234567890",
    "role": "client",
    "device_name": "Mobile App"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "token_type": "Bearer",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client",
            "profile_image": null
        }
    },
    "message": "User registered successfully"
}
```

### Login User
**POST** `/api/auth/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123",
    "device_name": "Mobile App"
}
```

### Logout User
**POST** `/api/auth/logout`

**Headers:** `Authorization: Bearer {token}`

### Get Current User
**GET** `/api/auth/me`

**Headers:** `Authorization: Bearer {token}`

### Refresh Token
**POST** `/api/auth/refresh`

**Headers:** `Authorization: Bearer {token}`

### Password Reset Flow

#### 1. Request Password Reset
**POST** `/api/auth/forgot-password`

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

#### 2. Verify OTP
**POST** `/api/auth/verify-otp`

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

#### 3. Reset Password
**POST** `/api/auth/reset-password`

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "abc123...",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

## User Management

### Get User Profile
**GET** `/api/user/profile`

**Headers:** `Authorization: Bearer {token}`

### Update User Profile
**PUT** `/api/user/profile`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "+1234567891"
}
```

### Change Password
**POST** `/api/user/change-password`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "current_password": "oldpassword",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

### Upload Avatar
**POST** `/api/user/upload-avatar`

**Headers:** `Authorization: Bearer {token}`

**Request Body:** `multipart/form-data`
- `avatar`: Image file (jpeg, png, jpg, gif, max 2MB)

### Delete Avatar
**DELETE** `/api/user/delete-avatar`

**Headers:** `Authorization: Bearer {token}`

### Delete Account
**DELETE** `/api/user/account`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "password": "currentpassword"
}
```

---

## Goals Management

### Get All Goals
**GET** `/api/goals`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status`: Filter by status (0 or 1)
- `user_id`: Filter by user ID
- `search`: Search in name and description
- `sort_by`: Sort field (default: created_at)
- `sort_direction`: Sort direction (asc/desc, default: desc)
- `per_page`: Items per page (default: 15)

### Create Goal
**POST** `/api/goals`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "name": "Lose 10kg",
    "description": "Weight loss goal for summer",
    "status": 1,
    "target_date": "2024-06-01",
    "priority": "high",
    "category": "fitness"
}
```

### Get Specific Goal
**GET** `/api/goals/{id}`

**Headers:** `Authorization: Bearer {token}`

### Update Goal
**PUT** `/api/goals/{id}`

**Headers:** `Authorization: Bearer {token}`

### Delete Goal
**DELETE** `/api/goals/{id}`

**Headers:** `Authorization: Bearer {token}`

### Toggle Goal Status
**PATCH** `/api/goals/{id}/toggle-status`

**Headers:** `Authorization: Bearer {token}`

### Search Goals
**GET** `/api/goals/search`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `query`: Search term (required, min 2 chars)
- `status`: Filter by status
- `priority`: Filter by priority
- `category`: Filter by category
- `per_page`: Items per page

### Bulk Operations

#### Bulk Update Goals
**PATCH** `/api/goals/bulk`

**Request Body:**
```json
{
    "goal_ids": [1, 2, 3],
    "status": 1,
    "priority": "high",
    "category": "fitness"
}
```

#### Bulk Delete Goals
**DELETE** `/api/goals/bulk`

**Request Body:**
```json
{
    "goal_ids": [1, 2, 3]
}
```

---

## Workouts Management

### Get All Workouts
**GET** `/api/workouts`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `is_active`: Filter by active status
- `category`: Filter by category
- `difficulty`: Filter by difficulty
- `duration_min`: Minimum duration filter
- `duration_max`: Maximum duration filter
- `search`: Search in name, description, category
- `include_videos`: Include workout videos (true/false)
- `sort_by`: Sort field
- `sort_direction`: Sort direction
- `per_page`: Items per page

### Create Workout
**POST** `/api/workouts`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "name": "Morning Cardio",
    "description": "High intensity cardio workout",
    "category": "cardio",
    "difficulty": "intermediate",
    "duration": 30,
    "is_active": true
}
```

### Get Specific Workout
**GET** `/api/workouts/{id}`

**Headers:** `Authorization: Bearer {token}`

### Update Workout
**PUT** `/api/workouts/{id}`

**Headers:** `Authorization: Bearer {token}`

### Delete Workout
**DELETE** `/api/workouts/{id}`

**Headers:** `Authorization: Bearer {token}`

### Toggle Workout Status
**PATCH** `/api/workouts/{id}/toggle-status`

**Headers:** `Authorization: Bearer {token}`

### Duplicate Workout
**POST** `/api/workouts/{id}/duplicate`

**Headers:** `Authorization: Bearer {token}`

### Add to Favorites
**POST** `/api/workouts/{id}/favorite`

**Headers:** `Authorization: Bearer {token}`

### Remove from Favorites
**DELETE** `/api/workouts/{id}/favorite`

**Headers:** `Authorization: Bearer {token}`

### Search Workouts
**GET** `/api/workouts/search`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `query`: Search term (required)
- `category`: Filter by category
- `difficulty`: Filter by difficulty
- `duration_min`: Minimum duration
- `duration_max`: Maximum duration
- `is_active`: Filter by active status
- `per_page`: Items per page

### Get Workout Statistics
**GET** `/api/workouts/statistics`

**Headers:** `Authorization: Bearer {token}`

### Get Workout Categories
**GET** `/api/workouts/categories`

**Headers:** `Authorization: Bearer {token}`

### Bulk Operations

#### Bulk Update Workouts
**PATCH** `/api/workouts/bulk`

**Request Body:**
```json
{
    "workout_ids": [1, 2, 3],
    "is_active": true,
    "category": "strength",
    "difficulty": "advanced"
}
```

#### Bulk Delete Workouts
**DELETE** `/api/workouts/bulk`

**Request Body:**
```json
{
    "workout_ids": [1, 2, 3]
}
```

---

## Workout Videos Management

### Get Videos for Specific Workout
**GET** `/api/workouts/{workout_id}/videos`

**Headers:** `Authorization: Bearer {token}`

### Add Video to Workout
**POST** `/api/workouts/{workout_id}/videos`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "title": "Warm-up Exercise",
    "description": "5-minute warm-up routine",
    "video_url": "https://example.com/video.mp4",
    "thumbnail_url": "https://example.com/thumb.jpg",
    "duration": 300,
    "order": 1
}
```

### Get Specific Video
**GET** `/api/workouts/{workout_id}/videos/{video_id}`

**Headers:** `Authorization: Bearer {token}`

### Update Video
**PUT** `/api/workouts/{workout_id}/videos/{video_id}`

**Headers:** `Authorization: Bearer {token}`

### Delete Video
**DELETE** `/api/workouts/{workout_id}/videos/{video_id}`

**Headers:** `Authorization: Bearer {token}`

### Reorder Videos
**PATCH** `/api/workouts/{workout_id}/videos/reorder`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "video_ids": [3, 1, 2, 4]
}
```

### Toggle Video Status
**PATCH** `/api/workouts/{workout_id}/videos/{video_id}/toggle-status`

**Headers:** `Authorization: Bearer {token}`

### Standalone Video Operations

#### Get All Videos
**GET** `/api/videos`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `workout_id`: Filter by workout
- `duration_min`: Minimum duration
- `duration_max`: Maximum duration
- `search`: Search term
- `sort_by`: Sort field
- `sort_direction`: Sort direction
- `per_page`: Items per page

#### Get Specific Video
**GET** `/api/videos/{id}`

**Headers:** `Authorization: Bearer {token}`

#### Search Videos
**GET** `/api/videos/search`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `query`: Search term (required)
- `workout_id`: Filter by workout
- `duration_min`: Minimum duration
- `duration_max`: Maximum duration
- `per_page`: Items per page

#### Get Video Categories
**GET** `/api/videos/categories`

**Headers:** `Authorization: Bearer {token}`

#### Add Video to Favorites
**POST** `/api/videos/{id}/favorite`

**Headers:** `Authorization: Bearer {token}`

#### Remove Video from Favorites
**DELETE** `/api/videos/{id}/favorite`

**Headers:** `Authorization: Bearer {token}`

---

## System Information

### Get System Status
**GET** `/api/system/status`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "success": true,
    "data": {
        "status": "online",
        "version": "1.0.0",
        "timestamp": "2024-01-15T10:30:00.000Z",
        "laravel_version": "11.x"
    },
    "message": "System is operational"
}
```

### Get System Configuration
**GET** `/api/system/config`

**Headers:** `Authorization: Bearer {token}`

---

## Error Handling

### Standard Error Response Format

```json
{
    "success": false,
    "message": "Error description",
    "data": {
        "error": "Detailed error message",
        "field_errors": {
            "email": ["The email field is required."]
        }
    }
}
```

### HTTP Status Codes

- **200 OK**: Successful request
- **201 Created**: Resource created successfully
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Authentication endpoints**: 5 requests per minute
- **General API endpoints**: 60 requests per minute
- **Search endpoints**: 30 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Time when limit resets

---

## Response Format

### Success Response

```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation completed successfully"
}
```

### Paginated Response

```json
{
    "success": true,
    "data": {
        "data": [
            // Array of items
        ],
        "pagination": {
            "total": 100,
            "per_page": 15,
            "current_page": 1,
            "last_page": 7,
            "from": 1,
            "to": 15,
            "has_more_pages": true
        }
    },
    "message": "Data retrieved successfully"
}
```

---

## Authentication Headers

For all protected endpoints, include the authentication header:

```
Authorization: Bearer {your_token_here}
Content-Type: application/json
Accept: application/json
```

---

## Example Usage

### JavaScript/Fetch Example

```javascript
// Login
const loginResponse = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123',
        device_name: 'Web App'
    })
});

const loginData = await loginResponse.json();
const token = loginData.data.token;

// Get workouts
const workoutsResponse = await fetch('/api/workouts', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

const workoutsData = await workoutsResponse.json();
console.log(workoutsData.data);
```

### cURL Example

```bash
# Login
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "cURL Client"
  }'

# Get workouts (replace TOKEN with actual token)
curl -X GET http://your-domain.com/api/workouts \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

---

## Support

For API support and questions:
- Email: api-support@goglobe.com
- Documentation: http://your-domain.com/api/docs
- Status Page: http://your-domain.com/api/system/status

---

**Last Updated:** January 2024  
**API Version:** 1.0.0  
**Laravel Version:** 11.x