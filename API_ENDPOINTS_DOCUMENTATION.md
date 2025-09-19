# Go Globe CMS API Documentation

## Overview

This document provides comprehensive documentation for all API endpoints in the Go Globe CMS Application. The API is organized by user roles and functionality, with all protected routes requiring Sanctum authentication tokens.

**Base URL:** `{APP_URL}/api`  
**Version:** 2.0.0  
**Authentication:** Laravel Sanctum  
**Content-Type:** `application/json`  
**Accept:** `application/json`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Public Routes](#public-routes)
3. [Common Authenticated Routes](#common-authenticated-routes)
4. [Admin Routes](#admin-routes)
5. [Trainer Routes](#trainer-routes)
6. [Client Routes](#client-routes)
7. [Error Handling](#error-handling)
8. [Rate Limiting](#rate-limiting)

---

## Authentication

### Headers Required for Protected Routes

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Standard Response Format

All API responses follow this standard format:

```json
{
    "success": true|false,
    "message": "Response message",
    "data": {
        // Response data object
    },
    "errors": {
        // Validation errors (if any)
    }
}
```

---

## Public Routes

### Authentication Endpoints

#### 1. User Registration
**POST** `/api/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "client|trainer"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client",
            "created_at": "2025-01-19T10:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

#### 2. User Login
**POST** `/api/auth/login`

Authenticate user and receive access token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client"
        },
        "token": "1|abc123def456..."
    }
}
```

#### 3. Forgot Password
**POST** `/api/auth/forgot-password`

Request password reset OTP.

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "OTP sent to your email",
    "data": {
        "email": "john@example.com",
        "expires_at": "2025-01-19T10:15:00.000000Z"
    }
}
```

#### 4. Verify OTP
**POST** `/api/auth/verify-otp`

Verify the OTP for password reset.

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "OTP verified successfully",
    "data": {
        "reset_token": "abc123def456...",
        "expires_at": "2025-01-19T10:30:00.000000Z"
    }
}
```

#### 5. Reset Password
**POST** `/api/auth/reset-password`

Reset password using verified token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "reset_token": "abc123def456...",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Password reset successfully",
    "data": {
        "message": "You can now login with your new password"
    }
}
```

#### 6. Resend OTP
**POST** `/api/auth/resend-otp`

Resend OTP for password reset.

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "OTP resent successfully",
    "data": {
        "email": "john@example.com",
        "expires_at": "2025-01-19T10:15:00.000000Z"
    }
}
```

### System Information Endpoints

#### 7. System Status
**GET** `/api/system/status`

Get system operational status.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "status": "online",
        "version": "2.0.0",
        "timestamp": "2025-01-19T10:00:00.000000Z",
        "laravel_version": "11.x"
    },
    "message": "System is operational"
}
```

#### 8. System Configuration
**GET** `/api/system/config`

Get public system configuration.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "app_name": "Go Globe CMS",
        "app_env": "production",
        "timezone": "UTC",
        "locale": "en"
    },
    "message": "System configuration retrieved"
}
```

### API Documentation Endpoints

#### 9. API Documentation Index
**GET** `/api/docs/`

Get complete API documentation.

#### 10. Specific Endpoint Documentation
**GET** `/api/docs/{endpoint}`

Get documentation for a specific endpoint.

#### 11. OpenAPI Schema
**GET** `/api/docs/schema/openapi`

Get OpenAPI/Swagger schema.

---

## Common Authenticated Routes

*Requires Authentication: Bearer Token*

### Authentication Management

#### 12. Logout
**POST** `/api/auth/logout`

Logout and revoke current token.

**Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "data": {}
}
```

#### 13. Refresh Token
**POST** `/api/auth/refresh`

Refresh the current authentication token.

**Response (200):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "2|new_token_here..."
    }
}
```

#### 14. Get Current User
**GET** `/api/auth/me`

Get current authenticated user information.

**Response (200):**
```json
{
    "success": true,
    "message": "User information retrieved",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client",
            "profile_image": "path/to/image.jpg",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 15. Verify Token
**POST** `/api/auth/verify-token`

Verify if current token is valid.

**Response (200):**
```json
{
    "success": true,
    "message": "Token is valid",
    "data": {
        "valid": true,
        "expires_at": "2025-01-20T10:00:00.000000Z"
    }
}
```

### User Profile Management

#### 16. Get User Profile
**GET** `/api/user/profile`

Get current user's profile information.

**Response (200):**
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client",
            "profile_image": "path/to/image.jpg",
            "phone": "+1234567890",
            "date_of_birth": "1990-01-01",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 17. Update User Profile
**PUT** `/api/user/profile`

Update current user's profile information.

**Request Body:**
```json
{
    "name": "John Updated",
    "phone": "+1234567890",
    "date_of_birth": "1990-01-01"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Updated",
            "email": "john@example.com",
            "phone": "+1234567890",
            "date_of_birth": "1990-01-01"
        }
    }
}
```

#### 18. Change Password
**POST** `/api/user/change-password`

Change current user's password.

**Request Body:**
```json
{
    "current_password": "oldpassword123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Password changed successfully",
    "data": {}
}
```

#### 19. Upload Avatar
**POST** `/api/user/upload-avatar`

Upload user profile image.

**Request Body (multipart/form-data):**
```
avatar: [image file]
```

**Response (200):**
```json
{
    "success": true,
    "message": "Avatar uploaded successfully",
    "data": {
        "profile_image": "path/to/new/image.jpg"
    }
}
```

#### 20. Delete Avatar
**DELETE** `/api/user/delete-avatar`

Remove user profile image.

**Response (200):**
```json
{
    "success": true,
    "message": "Avatar deleted successfully",
    "data": {}
}
```

#### 21. Get Activity Log
**GET** `/api/user/activity-log`

Get user's activity log.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
    "success": true,
    "message": "Activity log retrieved",
    "data": {
        "activities": [
            {
                "id": 1,
                "action": "login",
                "description": "User logged in",
                "ip_address": "192.168.1.1",
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 75
        }
    }
}
```

#### 22. Delete Account
**DELETE** `/api/user/account`

Delete current user account (soft delete).

**Response (200):**
```json
{
    "success": true,
    "message": "Account deleted successfully",
    "data": {}
}
```

### Goals Management

#### 23. Get Goals
**GET** `/api/goals`

Get user's goals with pagination.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 15)
- `status` (optional): Filter by status (active, completed, paused)

**Response (200):**
```json
{
    "success": true,
    "message": "Goals retrieved successfully",
    "data": {
        "goals": [
            {
                "id": 1,
                "title": "Lose 10kg",
                "description": "Weight loss goal",
                "target_value": 10,
                "current_value": 3,
                "unit": "kg",
                "status": "active",
                "target_date": "2025-06-01",
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 2,
            "total_items": 25
        }
    }
}
```

#### 24. Create Goal
**POST** `/api/goals`

Create a new goal.

**Request Body:**
```json
{
    "title": "Lose 10kg",
    "description": "Weight loss goal for summer",
    "target_value": 10,
    "unit": "kg",
    "target_date": "2025-06-01"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Goal created successfully",
    "data": {
        "goal": {
            "id": 1,
            "title": "Lose 10kg",
            "description": "Weight loss goal for summer",
            "target_value": 10,
            "current_value": 0,
            "unit": "kg",
            "status": "active",
            "target_date": "2025-06-01",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 25. Search Goals
**GET** `/api/goals/search`

Search goals by title or description.

**Query Parameters:**
- `q`: Search query
- `status` (optional): Filter by status

**Response (200):**
```json
{
    "success": true,
    "message": "Search results retrieved",
    "data": {
        "goals": [
            {
                "id": 1,
                "title": "Lose 10kg",
                "description": "Weight loss goal",
                "status": "active"
            }
        ],
        "total": 1
    }
}
```

#### 26. Get Single Goal
**GET** `/api/goals/{goal}`

Get specific goal details.

**Response (200):**
```json
{
    "success": true,
    "message": "Goal retrieved successfully",
    "data": {
        "goal": {
            "id": 1,
            "title": "Lose 10kg",
            "description": "Weight loss goal",
            "target_value": 10,
            "current_value": 3,
            "unit": "kg",
            "status": "active",
            "target_date": "2025-06-01",
            "progress_percentage": 30,
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 27. Update Goal
**PUT** `/api/goals/{goal}`

Update specific goal.

**Request Body:**
```json
{
    "title": "Lose 12kg",
    "current_value": 5,
    "target_date": "2025-07-01"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Goal updated successfully",
    "data": {
        "goal": {
            "id": 1,
            "title": "Lose 12kg",
            "current_value": 5,
            "target_date": "2025-07-01"
        }
    }
}
```

#### 28. Delete Goal
**DELETE** `/api/goals/{goal}`

Delete specific goal.

**Response (200):**
```json
{
    "success": true,
    "message": "Goal deleted successfully",
    "data": {}
}
```

#### 29. Toggle Goal Status
**PATCH** `/api/goals/{goal}/toggle-status`

Toggle goal status between active/paused/completed.

**Request Body:**
```json
{
    "status": "completed"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Goal status updated successfully",
    "data": {
        "goal": {
            "id": 1,
            "status": "completed"
        }
    }
}
```

#### 30. Bulk Update Goals
**PATCH** `/api/goals/bulk`

Update multiple goals at once.

**Request Body:**
```json
{
    "goal_ids": [1, 2, 3],
    "updates": {
        "status": "paused"
    }
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Goals updated successfully",
    "data": {
        "updated_count": 3
    }
}
```

#### 31. Bulk Delete Goals
**DELETE** `/api/goals/bulk`

Delete multiple goals at once.

**Request Body:**
```json
{
    "goal_ids": [1, 2, 3]
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Goals deleted successfully",
    "data": {
        "deleted_count": 3
    }
}
```

### Public Trainer Information

#### 32. Get All Trainers
**GET** `/api/trainers`

Get list of all trainers (public information).

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `specialization` (optional): Filter by specialization

**Response (200):**
```json
{
    "success": true,
    "message": "Trainers retrieved successfully",
    "data": {
        "trainers": [
            {
                "id": 1,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "specialization": "Weight Loss",
                "experience_years": 5,
                "rating": 4.8,
                "profile_image": "path/to/image.jpg",
                "bio": "Certified fitness trainer..."
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 45
        }
    }
}
```

#### 33. Get Trainer Details
**GET** `/api/trainers/{id}`

Get specific trainer's detailed information.

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer details retrieved",
    "data": {
        "trainer": {
            "id": 1,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "specialization": "Weight Loss",
            "experience_years": 5,
            "rating": 4.8,
            "profile_image": "path/to/image.jpg",
            "bio": "Certified fitness trainer with 5 years experience...",
            "hourly_rate": 50,
            "availability": "Mon-Fri 9AM-6PM"
        }
    }
}
```

#### 34. Get Trainer Certifications
**GET** `/api/trainers/{id}/certifications`

Get trainer's certifications.

**Response (200):**
```json
{
    "success": true,
    "message": "Certifications retrieved",
    "data": {
        "certifications": [
            {
                "id": 1,
                "name": "Certified Personal Trainer",
                "issuing_organization": "ACSM",
                "issue_date": "2020-01-15",
                "expiry_date": "2025-01-15",
                "certificate_image": "path/to/cert.jpg",
                "status": "approved"
            }
        ]
    }
}
```

#### 35. Get Trainer Testimonials
**GET** `/api/trainers/{id}/testimonials`

Get trainer's testimonials from clients.

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonials retrieved",
    "data": {
        "testimonials": [
            {
                "id": 1,
                "client_name": "John Doe",
                "rating": 5,
                "comment": "Excellent trainer, very professional!",
                "created_at": "2025-01-15T10:00:00.000000Z",
                "likes": 12,
                "dislikes": 1
            }
        ],
        "average_rating": 4.8,
        "total_testimonials": 25
    }
}
```

### Testimonial Reactions

#### 36. Like Testimonial
**POST** `/api/testimonials/{id}/like`

Like a testimonial.

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonial liked successfully",
    "data": {
        "testimonial_id": 1,
        "likes": 13,
        "user_reaction": "like"
    }
}
```

#### 37. Dislike Testimonial
**POST** `/api/testimonials/{id}/dislike`

Dislike a testimonial.

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonial disliked successfully",
    "data": {
        "testimonial_id": 1,
        "dislikes": 2,
        "user_reaction": "dislike"
    }
}
```

---

## Admin Routes

*Requires Authentication: Bearer Token + Admin Role*

### Admin User Management

#### 38. Get All Users (Admin)
**GET** `/api/admin/users`

Get all users with admin privileges.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `role` (optional): Filter by role
- `status` (optional): Filter by status

**Response (200):**
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": {
        "users": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "role": "client",
                "status": "active",
                "created_at": "2025-01-19T10:00:00.000000Z",
                "last_login": "2025-01-19T09:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 10,
            "total_items": 150
        }
    }
}
```

#### 39. Create User (Admin)
**POST** `/api/admin/users`

Create new user as admin.

**Request Body:**
```json
{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "role": "client|trainer|admin",
    "status": "active"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "user": {
            "id": 2,
            "name": "New User",
            "email": "newuser@example.com",
            "role": "client",
            "status": "active",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 40. Get User Statistics (Admin)
**GET** `/api/admin/users/statistics`

Get user statistics for admin dashboard.

**Response (200):**
```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "total_users": 150,
        "active_users": 140,
        "inactive_users": 10,
        "users_by_role": {
            "admin": 5,
            "trainer": 25,
            "client": 120
        },
        "new_users_this_month": 15,
        "growth_percentage": 12.5
    }
}
```

#### 41. Get Single User (Admin)
**GET** `/api/admin/users/{id}`

Get specific user details as admin.

**Response (200):**
```json
{
    "success": true,
    "message": "User details retrieved",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "client",
            "status": "active",
            "profile_image": "path/to/image.jpg",
            "phone": "+1234567890",
            "created_at": "2025-01-19T10:00:00.000000Z",
            "last_login": "2025-01-19T09:00:00.000000Z",
            "login_count": 45
        }
    }
}
```

#### 42. Update User (Admin)
**PUT** `/api/admin/users/{id}`

Update user information as admin.

**Request Body:**
```json
{
    "name": "Updated Name",
    "email": "updated@example.com",
    "role": "trainer",
    "status": "active"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Updated Name",
            "email": "updated@example.com",
            "role": "trainer",
            "status": "active"
        }
    }
}
```

#### 43. Delete User (Admin)
**DELETE** `/api/admin/users/{id}`

Delete user as admin.

**Response (200):**
```json
{
    "success": true,
    "message": "User deleted successfully",
    "data": {}
}
```

#### 44. Get Users by Role (Admin)
**GET** `/api/admin/users/role/{role}`

Get users filtered by specific role.

**Response (200):**
```json
{
    "success": true,
    "message": "Users by role retrieved",
    "data": {
        "users": [
            {
                "id": 1,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "role": "trainer",
                "status": "active"
            }
        ],
        "total": 25
    }
}
```

#### 45. Toggle User Status (Admin)
**PATCH** `/api/admin/users/{id}/toggle-status`

Toggle user active/inactive status.

**Request Body:**
```json
{
    "status": "inactive"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "User status updated successfully",
    "data": {
        "user": {
            "id": 1,
            "status": "inactive"
        }
    }
}
```

#### 46. Delete User Image (Admin)
**DELETE** `/api/admin/users/{id}/delete-image`

Delete user's profile image as admin.

**Response (200):**
```json
{
    "success": true,
    "message": "User image deleted successfully",
    "data": {}
}
```

### Admin Trainer Management

#### 47. Get All Trainers (Admin)
**GET** `/api/admin/trainers`

Get all trainers with admin oversight.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `status` (optional): Filter by status
- `specialization` (optional): Filter by specialization

**Response (200):**
```json
{
    "success": true,
    "message": "Trainers retrieved successfully",
    "data": {
        "trainers": [
            {
                "id": 1,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "specialization": "Weight Loss",
                "experience_years": 5,
                "status": "active",
                "rating": 4.8,
                "total_clients": 25,
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 75
        }
    }
}
```

#### 48. Get Trainer Details (Admin)
**GET** `/api/admin/trainers/{id}`

Get specific trainer details with admin access.

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer details retrieved",
    "data": {
        "trainer": {
            "id": 1,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "specialization": "Weight Loss",
            "experience_years": 5,
            "status": "active",
            "rating": 4.8,
            "hourly_rate": 50,
            "bio": "Certified fitness trainer...",
            "total_clients": 25,
            "total_workouts": 150,
            "total_sessions": 500,
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 49. Update Trainer (Admin)
**PUT** `/api/admin/trainers/{id}`

Update trainer information as admin.

**Request Body:**
```json
{
    "specialization": "Strength Training",
    "hourly_rate": 60,
    "status": "active"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer updated successfully",
    "data": {
        "trainer": {
            "id": 1,
            "specialization": "Strength Training",
            "hourly_rate": 60,
            "status": "active"
        }
    }
}
```

#### 50. Delete Trainer (Admin)
**DELETE** `/api/admin/trainers/{id}`

Delete trainer as admin.

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer deleted successfully",
    "data": {}
}
```

#### 51. Toggle Trainer Status (Admin)
**PATCH** `/api/admin/trainers/{id}/toggle-status`

Toggle trainer active/inactive status.

**Request Body:**
```json
{
    "status": "inactive"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer status updated successfully",
    "data": {
        "trainer": {
            "id": 1,
            "status": "inactive"
        }
    }
}
```

#### 52. Get Trainer Analytics (Admin)
**GET** `/api/admin/trainers/{id}/analytics`

Get detailed analytics for specific trainer.

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer analytics retrieved",
    "data": {
        "analytics": {
            "total_clients": 25,
            "active_clients": 20,
            "total_sessions": 500,
            "sessions_this_month": 45,
            "average_rating": 4.8,
            "total_revenue": 25000,
            "revenue_this_month": 2250,
            "client_retention_rate": 85.5
        }
    }
}
```

### Admin Trainer Certifications Management

#### 53. Get Trainer Certifications (Admin)
**GET** `/api/admin/trainers/{id}/certifications`

Get trainer's certifications with admin access.

**Response (200):**
```json
{
    "success": true,
    "message": "Certifications retrieved",
    "data": {
        "certifications": [
            {
                "id": 1,
                "name": "Certified Personal Trainer",
                "issuing_organization": "ACSM",
                "issue_date": "2020-01-15",
                "expiry_date": "2025-01-15",
                "certificate_image": "path/to/cert.jpg",
                "status": "pending",
                "submitted_at": "2025-01-19T10:00:00.000000Z"
            }
        ]
    }
}
```

#### 54. Add Trainer Certification (Admin)
**POST** `/api/admin/trainers/{id}/certifications`

Add certification for trainer as admin.

**Request Body (multipart/form-data):**
```
name: Certified Personal Trainer
issuing_organization: ACSM
issue_date: 2020-01-15
expiry_date: 2025-01-15
certificate_image: [file]
```

**Response (201):**
```json
{
    "success": true,
    "message": "Certification added successfully",
    "data": {
        "certification": {
            "id": 2,
            "name": "Certified Personal Trainer",
            "issuing_organization": "ACSM",
            "status": "approved"
        }
    }
}
```

#### 55. Update Trainer Certification (Admin)
**PUT** `/api/admin/trainers/{trainerId}/certifications/{certificationId}`

Update trainer's certification as admin.

**Request Body:**
```json
{
    "name": "Updated Certification Name",
    "expiry_date": "2026-01-15",
    "status": "approved"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Certification updated successfully",
    "data": {
        "certification": {
            "id": 1,
            "name": "Updated Certification Name",
            "expiry_date": "2026-01-15",
            "status": "approved"
        }
    }
}
```

#### 56. Approve Trainer Certification (Admin)
**POST** `/api/admin/trainers/{id}/certifications/{certificationId}/approve`

Approve pending trainer certification.

**Request Body:**
```json
{
    "status": "approved",
    "admin_notes": "Certification verified and approved"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Certification approved successfully",
    "data": {
        "certification": {
            "id": 1,
            "status": "approved",
            "approved_at": "2025-01-19T10:00:00.000000Z",
            "admin_notes": "Certification verified and approved"
        }
    }
}
```

#### 57. Delete Trainer Certification (Admin)
**DELETE** `/api/admin/trainers/{trainerId}/certifications/{certificationId}`

Delete trainer's certification as admin.

**Response (200):**
```json
{
    "success": true,
    "message": "Certification deleted successfully",
    "data": {}
}
```

### Admin Trainer Testimonials Management

#### 58. Get Trainer Testimonials (Admin)
**GET** `/api/admin/trainers/{id}/testimonials`

Get trainer's testimonials with admin oversight.

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonials retrieved",
    "data": {
        "testimonials": [
            {
                "id": 1,
                "client_id": 5,
                "client_name": "John Doe",
                "rating": 5,
                "comment": "Excellent trainer!",
                "status": "approved",
                "created_at": "2025-01-15T10:00:00.000000Z",
                "likes": 12,
                "dislikes": 1
            }
        ],
        "statistics": {
            "total": 25,
            "approved": 23,
            "pending": 2,
            "average_rating": 4.8
        }
    }
}
```

---

## Trainer Routes

*Requires Authentication: Bearer Token + Trainer Role*

### Trainer Profile Management

#### 59. Get Trainer Profile
**GET** `/api/trainer/profile`

Get current trainer's profile information.

**Response (200):**
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "trainer": {
            "id": 1,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "specialization": "Weight Loss",
            "experience_years": 5,
            "hourly_rate": 50,
            "bio": "Certified fitness trainer...",
            "profile_image": "path/to/image.jpg",
            "phone": "+1234567890",
            "availability": "Mon-Fri 9AM-6PM",
            "rating": 4.8,
            "total_clients": 25
        }
    }
}
```

#### 60. Update Trainer Profile
**PUT** `/api/trainer/profile`

Update current trainer's profile.

**Request Body:**
```json
{
    "specialization": "Strength Training",
    "experience_years": 6,
    "hourly_rate": 60,
    "bio": "Updated bio...",
    "availability": "Mon-Sat 8AM-7PM"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "trainer": {
            "id": 1,
            "specialization": "Strength Training",
            "experience_years": 6,
            "hourly_rate": 60,
            "bio": "Updated bio...",
            "availability": "Mon-Sat 8AM-7PM"
        }
    }
}
```

### Trainer Certification Management

#### 61. Get Trainer Certifications
**GET** `/api/trainer/certifications`

Get current trainer's certifications.

**Response (200):**
```json
{
    "success": true,
    "message": "Certifications retrieved successfully",
    "data": {
        "certifications": [
            {
                "id": 1,
                "name": "Certified Personal Trainer",
                "issuing_organization": "ACSM",
                "issue_date": "2020-01-15",
                "expiry_date": "2025-01-15",
                "certificate_image": "path/to/cert.jpg",
                "status": "approved"
            }
        ]
    }
}
```

#### 62. Add Certification
**POST** `/api/trainer/certifications`

Add new certification for current trainer.

**Request Body (multipart/form-data):**
```
name: New Certification
issuing_organization: Organization Name
issue_date: 2023-01-15
expiry_date: 2028-01-15
certificate_image: [file]
```

**Response (201):**
```json
{
    "success": true,
    "message": "Certification added successfully",
    "data": {
        "certification": {
            "id": 2,
            "name": "New Certification",
            "issuing_organization": "Organization Name",
            "status": "pending"
        }
    }
}
```

#### 63. Get Single Certification
**GET** `/api/trainer/certifications/{id}`

Get specific certification details.

**Response (200):**
```json
{
    "success": true,
    "message": "Certification retrieved successfully",
    "data": {
        "certification": {
            "id": 1,
            "name": "Certified Personal Trainer",
            "issuing_organization": "ACSM",
            "issue_date": "2020-01-15",
            "expiry_date": "2025-01-15",
            "certificate_image": "path/to/cert.jpg",
            "status": "approved",
            "admin_notes": "Verified and approved"
        }
    }
}
```

#### 64. Update Certification
**PUT** `/api/trainer/certifications/{id}`

Update specific certification.

**Request Body:**
```json
{
    "name": "Updated Certification Name",
    "expiry_date": "2026-01-15"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Certification updated successfully",
    "data": {
        "certification": {
            "id": 1,
            "name": "Updated Certification Name",
            "expiry_date": "2026-01-15"
        }
    }
}
```

#### 65. Delete Certification
**DELETE** `/api/trainer/certifications/{id}`

Delete specific certification.

**Response (200):**
```json
{
    "success": true,
    "message": "Certification deleted successfully",
    "data": {}
}
```

### Trainer Testimonials

#### 66. Get My Testimonials
**GET** `/api/trainer/testimonials`

Get testimonials received by current trainer.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `rating` (optional): Filter by rating

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonials retrieved successfully",
    "data": {
        "testimonials": [
            {
                "id": 1,
                "client_name": "John Doe",
                "rating": 5,
                "comment": "Excellent trainer, very professional!",
                "created_at": "2025-01-15T10:00:00.000000Z",
                "likes": 12,
                "dislikes": 1
            }
        ],
        "statistics": {
            "total": 25,
            "average_rating": 4.8,
            "rating_distribution": {
                "5": 18,
                "4": 5,
                "3": 2,
                "2": 0,
                "1": 0
            }
        },
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 25
        }
    }
}
```

### Trainer Scheduling & Availability Management

#### 67. Set Availability
**POST** `/api/trainer/scheduling/availability`

Set trainer's availability schedule.

**Request Body:**
```json
{
    "schedule": [
        {
            "day": "monday",
            "start_time": "09:00",
            "end_time": "17:00",
            "is_available": true
        },
        {
            "day": "tuesday",
            "start_time": "09:00",
            "end_time": "17:00",
            "is_available": true
        }
    ]
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Availability set successfully",
    "data": {
        "schedule": [
            {
                "day": "monday",
                "start_time": "09:00",
                "end_time": "17:00",
                "is_available": true
            }
        ]
    }
}
```

#### 68. Get Availability
**GET** `/api/trainer/scheduling/availability`

Get current trainer's availability schedule.

**Response (200):**
```json
{
    "success": true,
    "message": "Availability retrieved successfully",
    "data": {
        "schedule": [
            {
                "day": "monday",
                "start_time": "09:00",
                "end_time": "17:00",
                "is_available": true
            }
        ]
    }
}
```

#### 69. Get Blocked Times
**GET** `/api/trainer/scheduling/blocked-times`

Get trainer's blocked time slots.

**Response (200):**
```json
{
    "success": true,
    "message": "Blocked times retrieved successfully",
    "data": {
        "blocked_times": [
            {
                "id": 1,
                "start_datetime": "2025-01-20T14:00:00.000000Z",
                "end_datetime": "2025-01-20T15:00:00.000000Z",
                "reason": "Personal appointment",
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ]
    }
}
```

#### 70. Add Blocked Time
**POST** `/api/trainer/scheduling/blocked-times`

Add new blocked time slot.

**Request Body:**
```json
{
    "start_datetime": "2025-01-20T14:00:00.000000Z",
    "end_datetime": "2025-01-20T15:00:00.000000Z",
    "reason": "Personal appointment"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Blocked time added successfully",
    "data": {
        "blocked_time": {
            "id": 2,
            "start_datetime": "2025-01-20T14:00:00.000000Z",
            "end_datetime": "2025-01-20T15:00:00.000000Z",
            "reason": "Personal appointment"
        }
    }
}
```

#### 71. Delete Blocked Time
**DELETE** `/api/trainer/scheduling/blocked-times/{id}`

Remove blocked time slot.

**Response (200):**
```json
{
    "success": true,
    "message": "Blocked time deleted successfully",
    "data": {}
}
```

#### 72. Set Session Capacity
**POST** `/api/trainer/scheduling/session-capacity`

Set maximum session capacity for different session types.

**Request Body:**
```json
{
    "individual_capacity": 1,
    "group_capacity": 8,
    "online_capacity": 20
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Session capacity set successfully",
    "data": {
        "capacity": {
            "individual_capacity": 1,
            "group_capacity": 8,
            "online_capacity": 20
        }
    }
}
```

#### 73. Get Session Capacity
**GET** `/api/trainer/scheduling/session-capacity`

Get current session capacity settings.

**Response (200):**
```json
{
    "success": true,
    "message": "Session capacity retrieved successfully",
    "data": {
        "capacity": {
            "individual_capacity": 1,
            "group_capacity": 8,
            "online_capacity": 20
        }
    }
}
```

#### 74. Set Booking Settings
**POST** `/api/trainer/scheduling/booking-settings`

Set booking preferences and rules.

**Request Body:**
```json
{
    "advance_booking_days": 30,
    "cancellation_hours": 24,
    "auto_accept_bookings": false,
    "buffer_time_minutes": 15
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Booking settings updated successfully",
    "data": {
        "settings": {
            "advance_booking_days": 30,
            "cancellation_hours": 24,
            "auto_accept_bookings": false,
            "buffer_time_minutes": 15
        }
    }
}
```

#### 75. Get Booking Settings
**GET** `/api/trainer/scheduling/booking-settings`

Get current booking settings.

**Response (200):**
```json
{
    "success": true,
    "message": "Booking settings retrieved successfully",
    "data": {
        "settings": {
            "advance_booking_days": 30,
            "cancellation_hours": 24,
            "auto_accept_bookings": false,
            "buffer_time_minutes": 15
        }
    }
}
```

### Trainer Booking Management

#### 76. Get Trainer Bookings
**GET** `/api/trainer/bookings`

Get bookings for current trainer.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `status` (optional): Filter by status
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date

**Response (200):**
```json
{
    "success": true,
    "message": "Bookings retrieved successfully",
    "data": {
        "bookings": [
            {
                "id": 1,
                "client_id": 5,
                "client_name": "John Doe",
                "session_type": "individual",
                "scheduled_datetime": "2025-01-20T10:00:00.000000Z",
                "duration_minutes": 60,
                "status": "confirmed",
                "notes": "Weight loss session",
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 75
        }
    }
}
```

#### 77. Update Booking Status
**PATCH** `/api/trainer/bookings/{id}/status`

Update booking status (accept, reject, complete, etc.).

**Request Body:**
```json
{
    "status": "confirmed",
    "trainer_notes": "Looking forward to the session"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Booking status updated successfully",
    "data": {
        "booking": {
            "id": 1,
            "status": "confirmed",
            "trainer_notes": "Looking forward to the session",
            "updated_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

### Trainer Workout Management

#### 78. Get Trainer Workouts
**GET** `/api/trainer/workouts`

Get workouts created by current trainer.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `status` (optional): Filter by status
- `difficulty` (optional): Filter by difficulty

**Response (200):**
```json
{
    "success": true,
    "message": "Workouts retrieved successfully",
    "data": {
        "workouts": [
            {
                "id": 1,
                "title": "Full Body Strength",
                "description": "Complete full body workout",
                "difficulty": "intermediate",
                "duration_minutes": 45,
                "status": "active",
                "video_count": 8,
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 45
        }
    }
}
```

#### 79. Create Workout
**POST** `/api/trainer/workouts`

Create new workout.

**Request Body:**
```json
{
    "title": "New Workout",
    "description": "Workout description",
    "difficulty": "beginner|intermediate|advanced",
    "duration_minutes": 30,
    "equipment_needed": "Dumbbells, Mat",
    "target_muscle_groups": ["chest", "arms", "core"]
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Workout created successfully",
    "data": {
        "workout": {
            "id": 2,
            "title": "New Workout",
            "description": "Workout description",
            "difficulty": "beginner",
            "duration_minutes": 30,
            "status": "draft",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 80. Get Single Workout
**GET** `/api/trainer/workouts/{id}`

Get specific workout details.

**Response (200):**
```json
{
    "success": true,
    "message": "Workout retrieved successfully",
    "data": {
        "workout": {
            "id": 1,
            "title": "Full Body Strength",
            "description": "Complete full body workout",
            "difficulty": "intermediate",
            "duration_minutes": 45,
            "equipment_needed": "Dumbbells, Mat",
            "target_muscle_groups": ["chest", "arms", "core"],
            "status": "active",
            "video_count": 8,
            "videos": [
                {
                    "id": 1,
                    "title": "Warm-up",
                    "duration_seconds": 300,
                    "order": 1
                }
            ]
        }
    }
}
```

#### 81. Update Workout
**PUT** `/api/trainer/workouts/{id}`

Update specific workout.

**Request Body:**
```json
{
    "title": "Updated Workout Title",
    "description": "Updated description",
    "difficulty": "advanced",
    "duration_minutes": 60
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Workout updated successfully",
    "data": {
        "workout": {
            "id": 1,
            "title": "Updated Workout Title",
            "description": "Updated description",
            "difficulty": "advanced",
            "duration_minutes": 60
        }
    }
}
```

#### 82. Delete Workout
**DELETE** `/api/trainer/workouts/{id}`

Delete specific workout.

**Response (200):**
```json
{
    "success": true,
    "message": "Workout deleted successfully",
    "data": {}
}
```

#### 83. Toggle Workout Status
**PATCH** `/api/trainer/workouts/{id}/toggle-status`

Toggle workout status between active/inactive.

**Request Body:**
```json
{
    "status": "active"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Workout status updated successfully",
    "data": {
        "workout": {
            "id": 1,
            "status": "active"
        }
    }
}
```

### Trainer Workout Videos Management

#### 84. Get Workout Videos
**GET** `/api/trainer/workouts/{id}/videos`

Get videos for specific workout.

**Response (200):**
```json
{
    "success": true,
    "message": "Videos retrieved successfully",
    "data": {
        "videos": [
            {
                "id": 1,
                "title": "Warm-up",
                "description": "5-minute warm-up routine",
                "video_url": "path/to/video.mp4",
                "thumbnail_url": "path/to/thumbnail.jpg",
                "duration_seconds": 300,
                "order": 1
            }
        ]
    }
}
```

#### 85. Add Workout Video
**POST** `/api/trainer/workouts/{id}/videos`

Add new video to workout.

**Request Body (multipart/form-data):**
```
title: Exercise Video
description: Video description
video_file: [video file]
thumbnail: [image file]
duration_seconds: 180
order: 2
```

**Response (201):**
```json
{
    "success": true,
    "message": "Video added successfully",
    "data": {
        "video": {
            "id": 2,
            "title": "Exercise Video",
            "description": "Video description",
            "duration_seconds": 180,
            "order": 2
        }
    }
}
```

#### 86. Reorder Videos
**PATCH** `/api/trainer/workouts/{id}/videos/reorder`

Reorder videos within workout.

**Request Body:**
```json
{
    "video_orders": [
        {"video_id": 1, "order": 2},
        {"video_id": 2, "order": 1}
    ]
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Videos reordered successfully",
    "data": {
        "reordered_count": 2
    }
}
```

#### 87. Update Workout Video
**PUT** `/api/trainer/workouts/{id}/videos/{videoId}`

Update specific workout video.

**Request Body:**
```json
{
    "title": "Updated Video Title",
    "description": "Updated description",
    "order": 3
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Video updated successfully",
    "data": {
        "video": {
            "id": 1,
            "title": "Updated Video Title",
            "description": "Updated description",
            "order": 3
        }
    }
}
```

#### 88. Delete Workout Video
**DELETE** `/api/trainer/workouts/{id}/videos/{videoId}`

Delete specific workout video.

**Response (200):**
```json
{
    "success": true,
    "message": "Video deleted successfully",
    "data": {}
}
```

### Trainer Nutrition Management

#### 89. Get Nutrition Plans
**GET** `/api/trainer/nutrition/plans`

Get nutrition plans created by current trainer.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `client_id` (optional): Filter by client

**Response (200):**
```json
{
    "success": true,
    "message": "Nutrition plans retrieved successfully",
    "data": {
        "plans": [
            {
                "id": 1,
                "title": "Weight Loss Plan",
                "description": "Balanced nutrition for weight loss",
                "client_id": 5,
                "client_name": "John Doe",
                "duration_days": 30,
                "status": "active",
                "created_at": "2025-01-19T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 2,
            "total_items": 25
        }
    }
}
```

#### 90. Create Nutrition Plan
**POST** `/api/trainer/nutrition/plans`

Create new nutrition plan.

**Request Body:**
```json
{
    "title": "New Nutrition Plan",
    "description": "Plan description",
    "client_id": 5,
    "duration_days": 30,
    "daily_calories": 2000,
    "protein_grams": 150,
    "carbs_grams": 200,
    "fat_grams": 70
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Nutrition plan created successfully",
    "data": {
        "plan": {
            "id": 2,
            "title": "New Nutrition Plan",
            "description": "Plan description",
            "client_id": 5,
            "duration_days": 30,
            "status": "active",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 91. Get Single Nutrition Plan
**GET** `/api/trainer/nutrition/plans/{id}`

Get specific nutrition plan details.

**Response (200):**
```json
{
    "success": true,
    "message": "Nutrition plan retrieved successfully",
    "data": {
        "plan": {
            "id": 1,
            "title": "Weight Loss Plan",
            "description": "Balanced nutrition for weight loss",
            "client_id": 5,
            "client_name": "John Doe",
            "duration_days": 30,
            "daily_calories": 2000,
            "macros": {
                "protein_grams": 150,
                "carbs_grams": 200,
                "fat_grams": 70
            },
            "meals": [
                {
                    "id": 1,
                    "meal_type": "breakfast",
                    "name": "Oatmeal with Berries",
                    "calories": 350
                }
            ],
            "restrictions": ["gluten-free", "dairy-free"]
        }
    }
}
```

#### 92. Get Trainer Clients
**GET** `/api/trainer/nutrition/clients`

Get list of clients for nutrition plan assignment.

**Response (200):**
```json
{
    "success": true,
    "message": "Clients retrieved successfully",
    "data": {
        "clients": [
            {
                "id": 5,
                "name": "John Doe",
                "email": "john@example.com",
                "active_plans": 1,
                "last_session": "2025-01-18T10:00:00.000000Z"
            }
        ]
    }
}
```

#### 93. Add Meal to Plan
**POST** `/api/trainer/nutrition/plans/{planId}/meals`

Add meal to nutrition plan.

**Request Body:**
```json
{
    "meal_type": "breakfast",
    "name": "Scrambled Eggs",
    "description": "2 eggs with vegetables",
    "calories": 250,
    "protein_grams": 20,
    "carbs_grams": 5,
    "fat_grams": 15,
    "ingredients": ["eggs", "spinach", "tomatoes"],
    "instructions": "Scramble eggs with vegetables"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Meal added successfully",
    "data": {
        "meal": {
            "id": 2,
            "meal_type": "breakfast",
            "name": "Scrambled Eggs",
            "calories": 250,
            "protein_grams": 20
        }
    }
}
```

#### 94. Update Plan Macros
**PUT** `/api/trainer/nutrition/plans/{planId}/macros`

Update macronutrient targets for nutrition plan.

**Request Body:**
```json
{
    "daily_calories": 2200,
    "protein_grams": 160,
    "carbs_grams": 220,
    "fat_grams": 75
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Macros updated successfully",
    "data": {
        "macros": {
            "daily_calories": 2200,
            "protein_grams": 160,
            "carbs_grams": 220,
            "fat_grams": 75
        }
    }
}
```

#### 95. Add Dietary Restrictions
**POST** `/api/trainer/nutrition/plans/{planId}/restrictions`

Add dietary restrictions to nutrition plan.

**Request Body:**
```json
{
    "restrictions": ["gluten-free", "dairy-free", "vegetarian"]
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Restrictions added successfully",
    "data": {
        "restrictions": ["gluten-free", "dairy-free", "vegetarian"]
    }
}
```

---

## Client Routes

*Requires Authentication: Bearer Token + Client Role*

### Client Dashboard

#### 96. Get Client Dashboard
**GET** `/api/client/dashboard`

Get client dashboard overview with statistics.

**Response (200):**
```json
{
    "success": true,
    "message": "Dashboard data retrieved successfully",
    "data": {
        "overview": {
            "total_workouts": 45,
            "completed_sessions": 32,
            "active_goals": 3,
            "current_trainer": "Jane Smith",
            "next_session": "2025-01-20T10:00:00.000000Z"
        },
        "recent_activities": [
            {
                "type": "workout_completed",
                "description": "Completed Full Body Strength workout",
                "date": "2025-01-19T09:00:00.000000Z"
            }
        ],
        "progress_summary": {
            "weight_change": -2.5,
            "goals_completed": 2,
            "streak_days": 15
        }
    }
}
```

### Client Workouts

#### 97. Get Available Workouts
**GET** `/api/client/workouts`

Get workouts available to client.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `difficulty` (optional): Filter by difficulty
- `trainer_id` (optional): Filter by trainer

**Response (200):**
```json
{
    "success": true,
    "message": "Workouts retrieved successfully",
    "data": {
        "workouts": [
            {
                "id": 1,
                "title": "Full Body Strength",
                "description": "Complete full body workout",
                "trainer_name": "Jane Smith",
                "difficulty": "intermediate",
                "duration_minutes": 45,
                "video_count": 8,
                "completed": false
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 75
        }
    }
}
```

#### 98. Get Workout Details
**GET** `/api/client/workouts/{id}`

Get detailed workout information for client.

**Response (200):**
```json
{
    "success": true,
    "message": "Workout details retrieved successfully",
    "data": {
        "workout": {
            "id": 1,
            "title": "Full Body Strength",
            "description": "Complete full body workout",
            "trainer_name": "Jane Smith",
            "difficulty": "intermediate",
            "duration_minutes": 45,
            "equipment_needed": "Dumbbells, Mat",
            "target_muscle_groups": ["chest", "arms", "core"],
            "videos": [
                {
                    "id": 1,
                    "title": "Warm-up",
                    "duration_seconds": 300,
                    "order": 1,
                    "completed": false
                }
            ],
            "completion_stats": {
                "total_completions": 156,
                "average_rating": 4.7
            }
        }
    }
}
```

#### 99. Get Workout Statistics
**GET** `/api/client/workouts/statistics`

Get client's workout statistics and progress.

**Response (200):**
```json
{
    "success": true,
    "message": "Workout statistics retrieved successfully",
    "data": {
        "statistics": {
            "total_workouts_available": 75,
            "completed_workouts": 32,
            "completion_percentage": 42.7,
            "total_workout_time_minutes": 1440,
            "favorite_difficulty": "intermediate",
            "current_streak": 7,
            "longest_streak": 15,
            "workouts_this_week": 4,
            "workouts_this_month": 18
        },
        "progress_chart": [
            {
                "date": "2025-01-13",
                "workouts_completed": 1
            },
            {
                "date": "2025-01-14",
                "workouts_completed": 2
            }
        ]
    }
}
```

#### 100. Get Workout Videos
**GET** `/api/client/workouts/{id}/videos`

Get videos for specific workout (client view).

**Response (200):**
```json
{
    "success": true,
    "message": "Workout videos retrieved successfully",
    "data": {
        "videos": [
            {
                "id": 1,
                "title": "Warm-up",
                "description": "5-minute warm-up routine",
                "video_url": "path/to/video.mp4",
                "thumbnail_url": "path/to/thumbnail.jpg",
                "duration_seconds": 300,
                "order": 1,
                "completed": false,
                "completion_date": null
            }
        ],
        "workout_progress": {
            "completed_videos": 3,
            "total_videos": 8,
            "completion_percentage": 37.5
        }
    }
}
```

### Client Trainer Discovery

#### 101. Find Trainers
**GET** `/api/client/trainers/find`

Find and filter trainers for client.

**Query Parameters:**
- `specialization` (optional): Filter by specialization
- `max_rate` (optional): Maximum hourly rate
- `min_rating` (optional): Minimum rating
- `availability` (optional): Available on specific day

**Response (200):**
```json
{
    "success": true,
    "message": "Trainers found successfully",
    "data": {
        "trainers": [
            {
                "id": 1,
                "name": "Jane Smith",
                "specialization": "Weight Loss",
                "experience_years": 5,
                "hourly_rate": 50,
                "rating": 4.8,
                "total_clients": 25,
                "profile_image": "path/to/image.jpg",
                "availability_today": true,
                "next_available": "2025-01-20T10:00:00.000000Z"
            }
        ],
        "filters_applied": {
            "specialization": "Weight Loss",
            "max_rate": 60
        }
    }
}
```

#### 102. Get Trainer Profile
**GET** `/api/client/trainers/{id}/profile`

Get detailed trainer profile for client view.

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer profile retrieved successfully",
    "data": {
        "trainer": {
            "id": 1,
            "name": "Jane Smith",
            "specialization": "Weight Loss",
            "experience_years": 5,
            "hourly_rate": 50,
            "rating": 4.8,
            "bio": "Certified fitness trainer with 5 years experience...",
            "profile_image": "path/to/image.jpg",
            "total_clients": 25,
            "success_stories": 18,
            "certifications": [
                {
                    "name": "Certified Personal Trainer",
                    "organization": "ACSM"
                }
            ],
            "specialties": ["Weight Loss", "Strength Training"],
            "languages": ["English", "Spanish"]
        }
    }
}
```

#### 103. Get Trainer Testimonials (Client View)
**GET** `/api/client/trainers/{id}/testimonials`

Get trainer testimonials from client perspective.

**Response (200):**
```json
{
    "success": true,
    "message": "Testimonials retrieved successfully",
    "data": {
        "testimonials": [
            {
                "id": 1,
                "client_name": "Anonymous Client",
                "rating": 5,
                "comment": "Excellent trainer, very professional!",
                "created_at": "2025-01-15T10:00:00.000000Z",
                "verified": true
            }
        ],
        "summary": {
            "total_testimonials": 25,
            "average_rating": 4.8,
            "rating_distribution": {
                "5": 18,
                "4": 5,
                "3": 2,
                "2": 0,
                "1": 0
            }
        }
    }
}
```

#### 104. Get Trainer Availability
**GET** `/api/client/trainers/{id}/availability`

Get trainer's availability for booking.

**Query Parameters:**
- `date_from` (optional): Start date for availability check
- `date_to` (optional): End date for availability check

**Response (200):**
```json
{
    "success": true,
    "message": "Trainer availability retrieved successfully",
    "data": {
        "availability": [
            {
                "date": "2025-01-20",
                "slots": [
                    {
                        "start_time": "09:00",
                        "end_time": "10:00",
                        "available": true,
                        "session_type": "individual"
                    },
                    {
                        "start_time": "10:00",
                        "end_time": "11:00",
                        "available": false,
                        "reason": "booked"
                    }
                ]
            }
        ],
        "booking_settings": {
            "advance_booking_days": 30,
            "cancellation_hours": 24,
            "session_types": ["individual", "group", "online"]
        }
    }
}
```

### Client Booking Management

#### 105. Create Booking
**POST** `/api/client/bookings`

Create new booking with trainer.

**Request Body:**
```json
{
    "trainer_id": 1,
    "session_type": "individual",
    "scheduled_datetime": "2025-01-20T10:00:00.000000Z",
    "duration_minutes": 60,
    "notes": "First session - weight loss focus",
    "session_goals": ["weight loss", "strength building"]
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Booking created successfully",
    "data": {
        "booking": {
            "id": 1,
            "trainer_id": 1,
            "trainer_name": "Jane Smith",
            "session_type": "individual",
            "scheduled_datetime": "2025-01-20T10:00:00.000000Z",
            "duration_minutes": 60,
            "status": "pending",
            "total_cost": 50,
            "notes": "First session - weight loss focus",
            "created_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 106. Get Client Bookings
**GET** `/api/client/bookings`

Get client's bookings.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `status` (optional): Filter by status
- `upcoming` (optional): Show only upcoming bookings

**Response (200):**
```json
{
    "success": true,
    "message": "Bookings retrieved successfully",
    "data": {
        "bookings": [
            {
                "id": 1,
                "trainer_id": 1,
                "trainer_name": "Jane Smith",
                "session_type": "individual",
                "scheduled_datetime": "2025-01-20T10:00:00.000000Z",
                "duration_minutes": 60,
                "status": "confirmed",
                "total_cost": 50,
                "can_cancel": true,
                "can_reschedule": true
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 45
        }
    }
}
```

#### 107. Get Single Booking
**GET** `/api/client/bookings/{id}`

Get specific booking details.

**Response (200):**
```json
{
    "success": true,
    "message": "Booking details retrieved successfully",
    "data": {
        "booking": {
            "id": 1,
            "trainer_id": 1,
            "trainer_name": "Jane Smith",
            "trainer_profile_image": "path/to/image.jpg",
            "session_type": "individual",
            "scheduled_datetime": "2025-01-20T10:00:00.000000Z",
            "duration_minutes": 60,
            "status": "confirmed",
            "total_cost": 50,
            "notes": "First session - weight loss focus",
            "session_goals": ["weight loss", "strength building"],
            "trainer_notes": "Looking forward to working with you!",
            "can_cancel": true,
            "can_reschedule": true,
            "cancellation_deadline": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 108. Update Booking
**PUT** `/api/client/bookings/{id}`

Update booking details (if allowed).

**Request Body:**
```json
{
    "notes": "Updated session notes",
    "session_goals": ["weight loss", "flexibility"]
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Booking updated successfully",
    "data": {
        "booking": {
            "id": 1,
            "notes": "Updated session notes",
            "session_goals": ["weight loss", "flexibility"],
            "updated_at": "2025-01-19T10:00:00.000000Z"
        }
    }
}
```

#### 109. Cancel Booking
**DELETE** `/api/client/bookings/{id}/cancel`

Cancel existing booking.

**Request Body:**
```json
{
    "cancellation_reason": "Schedule conflict"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Booking cancelled successfully",
    "data": {
        "booking": {
            "id": 1,
            "status": "cancelled",
            "cancellation_reason": "Schedule conflict",
            "cancelled_at": "2025-01-19T10:00:00.000000Z",
            "refund_amount": 50
        }
    }
}
```

### Client Nutrition

#### 110. Get Nutrition Plans
**GET** `/api/client/nutrition/plans`

Get nutrition plans assigned to client.

**Response (200):**
```json
{
    "success": true,
    "message": "Nutrition plans retrieved successfully",
    "data": {
        "plans": [
            {
                "id": 1,
                "title": "Weight Loss Plan",
                "description": "Balanced nutrition for weight loss",
                "trainer_name": "Jane Smith",
                "duration_days": 30,
                "daily_calories": 2000,
                "status": "active",
                "progress": {
                    "days_completed": 15,
                    "completion_percentage": 50
                },
                "created_at": "2025-01-05T10:00:00.000000Z"
            }
        ]
    }
}
```

#### 111. Get Nutrition Plan Details
**GET** `/api/client/nutrition/plans/{id}`

Get detailed nutrition plan information.

**Response (200):**
```json
{
    "success": true,
    "message": "Nutrition plan details retrieved successfully",
    "data": {
        "plan": {
            "id": 1,
            "title": "Weight Loss Plan",
            "description": "Balanced nutrition for weight loss",
            "trainer_name": "Jane Smith",
            "duration_days": 30,
            "daily_calories": 2000,
            "macros": {
                "protein_grams": 150,
                "carbs_grams": 200,
                "fat_grams": 70
            },
            "restrictions": ["gluten-free"],
            "meals": [
                {
                    "id": 1,
                    "meal_type": "breakfast",
                    "name": "Oatmeal with Berries",
                    "calories": 350,
                    "protein_grams": 12,
                    "carbs_grams": 65,
                    "fat_grams": 8
                }
            ]
        }
    }
}
```

#### 112. Get Daily Meals
**GET** `/api/client/nutrition/plans/{id}/meals`

Get meals for specific day in nutrition plan.

**Query Parameters:**
- `date` (optional): Specific date (YYYY-MM-DD)

**Response (200):**
```json
{
    "success": true,
    "message": "Daily meals retrieved successfully",
    "data": {
        "date": "2025-01-19",
        "meals": [
            {
                "meal_type": "breakfast",
                "name": "Oatmeal with Berries",
                "calories": 350,
                "ingredients": ["oats", "blueberries", "milk"],
                "instructions": "Cook oats with milk, top with berries",
                "completed": true
            }
        ],
        "daily_totals": {
            "calories": 1950,
            "protein_grams": 145,
            "carbs_grams": 195,
            "fat_grams": 68
        },
        "targets": {
            "calories": 2000,
            "protein_grams": 150,
            "carbs_grams": 200,
            "fat_grams": 70
        }
    }
}
```

#### 113. Get Nutrition Summary
**GET** `/api/client/nutrition/summary`

Get nutrition tracking summary and progress.

**Query Parameters:**
- `period` (optional): week, month, year

**Response (200):**
```json
{
    "success": true,
    "message": "Nutrition summary retrieved successfully",
    "data": {
        "summary": {
            "period": "week",
            "average_calories": 1975,
            "target_calories": 2000,
            "adherence_percentage": 87.5,
            "days_on_track": 6,
            "total_days": 7,
            "macro_averages": {
                "protein_grams": 148,
                "carbs_grams": 198,
                "fat_grams": 69
            },
            "macro_targets": {
                "protein_grams": 150,
                "carbs_grams": 200,
                "fat_grams": 70
            }
        },
        "progress_chart": [
            {
                "date": "2025-01-13",
                "calories": 1980,
                "target_met": true
            }
        ]
    }
}
```

---

## Error Handling

### Standard Error Response Format

All API errors follow this standard format:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Specific validation error message"]
    },
    "error_code": "ERROR_CODE",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

### Common HTTP Status Codes

| Status Code | Description | Usage |
|-------------|-------------|-------|
| 200 | OK | Successful GET, PUT, PATCH requests |
| 201 | Created | Successful POST requests |
| 400 | Bad Request | Invalid request data or parameters |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Insufficient permissions for the requested action |
| 404 | Not Found | Requested resource does not exist |
| 422 | Unprocessable Entity | Validation errors in request data |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

### Common Error Codes

| Error Code | Description |
|------------|-------------|
| `INVALID_CREDENTIALS` | Login credentials are incorrect |
| `TOKEN_EXPIRED` | Authentication token has expired |
| `INSUFFICIENT_PERMISSIONS` | User lacks required permissions |
| `RESOURCE_NOT_FOUND` | Requested resource does not exist |
| `VALIDATION_FAILED` | Request data validation failed |
| `RATE_LIMIT_EXCEEDED` | Too many requests in time window |
| `BOOKING_CONFLICT` | Requested time slot is not available |
| `PAYMENT_REQUIRED` | Payment is required for this action |

### Example Error Responses

#### Validation Error (422)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    },
    "error_code": "VALIDATION_FAILED",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

#### Authentication Error (401)
```json
{
    "success": false,
    "message": "Unauthenticated.",
    "error_code": "TOKEN_EXPIRED",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

#### Permission Error (403)
```json
{
    "success": false,
    "message": "This action is unauthorized.",
    "error_code": "INSUFFICIENT_PERMISSIONS",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

#### Resource Not Found (404)
```json
{
    "success": false,
    "message": "The requested resource was not found.",
    "error_code": "RESOURCE_NOT_FOUND",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

---

## Rate Limiting

### Rate Limit Headers

All API responses include rate limiting headers:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642608000
```

### Rate Limits by Endpoint Type

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| Authentication | 5 requests | 1 minute |
| Public Routes | 60 requests | 1 minute |
| Authenticated Routes | 100 requests | 1 minute |
| File Uploads | 10 requests | 1 minute |
| Admin Routes | 200 requests | 1 minute |

### Rate Limit Exceeded Response (429)

```json
{
    "success": false,
    "message": "Too many requests. Please try again later.",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60,
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

---

## API Fallback Route

### Undefined Endpoint Handler
**ANY** `/api/{any}`

Handles all undefined API endpoints with helpful information.

**Response (404):**
```json
{
    "success": false,
    "message": "API endpoint not found",
    "error_code": "ENDPOINT_NOT_FOUND",
    "available_sections": [
        "auth - Authentication endpoints",
        "user - User profile management",
        "goals - Goal management",
        "trainers - Trainer information",
        "testimonials - Testimonial interactions",
        "admin - Admin management (requires admin role)",
        "trainer - Trainer-specific endpoints (requires trainer role)",
        "client - Client-specific endpoints (requires client role)"
    ],
    "documentation_url": "/api/docs",
    "timestamp": "2025-01-19T10:00:00.000000Z"
}
```

---

## Additional Information

### File Upload Guidelines

- **Maximum file size**: 10MB for images, 100MB for videos
- **Supported image formats**: JPG, PNG, GIF, WebP
- **Supported video formats**: MP4, AVI, MOV, WebM
- **Image dimensions**: Recommended 1920x1080 for videos, 800x600 for images

### Pagination

All paginated endpoints support these query parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

### Date Formats

- All dates are in ISO 8601 format: `YYYY-MM-DDTHH:mm:ss.sssZ`
- Timezone: UTC
- Date-only fields: `YYYY-MM-DD`

### API Versioning

- Current version: 2.0.0
- Version header: `Accept: application/vnd.api+json;version=2.0`
- Backward compatibility maintained for 1 major version

---

**Last Updated:** January 19, 2025  
**API Version:** 2.0.0  
**Documentation Version:** 1.0.0

For technical support or API questions, please contact the development team.