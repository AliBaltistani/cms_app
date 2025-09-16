# Client API Documentation

## Overview

This document provides comprehensive documentation for the Client API endpoints that allow client users to find trainers and view detailed trainer profiles including certifications and testimonials.

## Authentication

All client API endpoints require:
1. **Sanctum Authentication Token** in the Authorization header
2. **Client Role** - Only users with `role = 'client'` can access these endpoints

### Headers Required
```
Authorization: Bearer {your-sanctum-token}
Content-Type: application/json
Accept: application/json
```

---

## API Endpoints

### 1. Find Trainers

**Endpoint:** `GET /api/client/trainers/find`

**Description:** Search and filter trainers with comprehensive filtering options.

#### Query Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `search` | string | No | Search in name, designation, about, training_philosophy | `yoga instructor` |
| `specialization` | string | No | Filter by trainer designation/specialization | `Personal Trainer` |
| `location` | string | No | Filter by location (future implementation) | `New York` |
| `experience_min` | integer | No | Minimum years of experience (0-50) | `2` |
| `experience_max` | integer | No | Maximum years of experience (0-50) | `10` |
| `rating_min` | decimal | No | Minimum average rating (1-5) | `4.0` |
| `sort_by` | string | No | Sort field: `name`, `experience`, `rating`, `created_at` | `rating` |
| `sort_order` | string | No | Sort order: `asc`, `desc` | `desc` |
| `per_page` | integer | No | Results per page (5-50) | `10` |

#### Example Request
```bash
GET /api/client/trainers/find?search=yoga&experience_min=2&rating_min=4&sort_by=rating&sort_order=desc&per_page=10
```

#### Example Response
```json
{
    "success": true,
    "message": "Trainers retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 15,
                "name": "Sarah Johnson",
                "email": "sarah@example.com",
                "phone": "+1234567890",
                "designation": "Certified Personal Trainer",
                "experience": 5,
                "about": "Passionate fitness trainer with 5+ years experience...",
                "training_philosophy": "Holistic approach to fitness and wellness...",
                "profile_image": "http://localhost/storage/profiles/sarah.jpg",
                "total_testimonials": 12,
                "average_rating": 4.8,
                "certifications_count": 3,
                "recent_certifications": [
                    {
                        "id": 1,
                        "name": "NASM Certified",
                        "date_added": "Jan 2024"
                    },
                    {
                        "id": 2,
                        "name": "ACE Certified",
                        "date_added": "Dec 2023"
                    }
                ],
                "member_since": "Jan 2023",
                "created_at": "2023-01-15T10:30:00.000000Z"
            }
        ],
        "first_page_url": "http://localhost/api/client/trainers/find?page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://localhost/api/client/trainers/find?page=2",
        "links": [...],
        "next_page_url": "http://localhost/api/client/trainers/find?page=2",
        "path": "http://localhost/api/client/trainers/find",
        "per_page": 10,
        "prev_page_url": null,
        "to": 10,
        "total": 15
    }
}
```

---

### 2. Get Trainer Profile

**Endpoint:** `GET /api/client/trainers/{trainerId}/profile`

**Description:** Get comprehensive trainer profile with all details, certifications, and testimonials.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `trainerId` | integer | Yes | The trainer's user ID |

#### Example Request
```bash
GET /api/client/trainers/15/profile
```

#### Example Response
```json
{
    "success": true,
    "message": "Trainer profile retrieved successfully",
    "data": {
        "id": 15,
        "name": "Sarah Johnson",
        "email": "sarah@example.com",
        "phone": "+1234567890",
        "designation": "Certified Personal Trainer",
        "experience": 5,
        "about": "Passionate fitness trainer with over 5 years of experience...",
        "training_philosophy": "I believe in a holistic approach to fitness...",
        "profile_image": "http://localhost/storage/profiles/sarah.jpg",
        "member_since": "January 2023",
        "created_at": "2023-01-15T10:30:00.000000Z",
        "statistics": {
            "total_testimonials": 12,
            "average_rating": 4.8,
            "total_likes": 45,
            "total_dislikes": 2,
            "total_certifications": 3,
            "years_experience": 5
        },
        "certifications": [
            {
                "id": 1,
                "certificate_name": "NASM Certified Personal Trainer",
                "document_url": "http://localhost/storage/certifications/nasm-cert.pdf",
                "has_document": true,
                "date_added": "15 Jan, 2024",
                "created_at": "2024-01-15T09:00:00.000000Z"
            },
            {
                "id": 2,
                "certificate_name": "ACE Certified",
                "document_url": null,
                "has_document": false,
                "date_added": "10 Dec, 2023",
                "created_at": "2023-12-10T14:30:00.000000Z"
            }
        ],
        "testimonials": [
            {
                "id": 25,
                "client_name": "Emily Carter",
                "client_profile_image": "http://localhost/storage/profiles/emily.jpg",
                "rating": 5,
                "comments": "Sarah is an amazing trainer! She helped me lose 20 pounds and gain so much strength...",
                "likes": 8,
                "dislikes": 0,
                "date_posted": "10 Feb, 2024",
                "created_at": "2024-02-10T16:45:00.000000Z"
            },
            {
                "id": 24,
                "client_name": "Mike Rodriguez",
                "client_profile_image": null,
                "rating": 4,
                "comments": "Great workouts and very motivating. Highly recommend!",
                "likes": 5,
                "dislikes": 1,
                "date_posted": "05 Feb, 2024",
                "created_at": "2024-02-05T11:20:00.000000Z"
            }
        ]
    }
}
```

---

### 3. Get Trainer Certifications

**Endpoint:** `GET /api/client/trainers/{trainerId}/certifications`

**Description:** Get only the certifications for a specific trainer.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `trainerId` | integer | Yes | The trainer's user ID |

#### Example Request
```bash
GET /api/client/trainers/15/certifications
```

#### Example Response
```json
{
    "success": true,
    "message": "Trainer certifications retrieved successfully",
    "data": {
        "trainer": {
            "id": 15,
            "name": "Sarah Johnson",
            "designation": "Certified Personal Trainer"
        },
        "certifications": [
            {
                "id": 1,
                "certificate_name": "NASM Certified Personal Trainer",
                "document_url": "http://localhost/storage/certifications/nasm-cert.pdf",
                "has_document": true,
                "date_added": "15 Jan, 2024",
                "created_at": "2024-01-15T09:00:00.000000Z"
            },
            {
                "id": 2,
                "certificate_name": "ACE Certified",
                "document_url": null,
                "has_document": false,
                "date_added": "10 Dec, 2023",
                "created_at": "2023-12-10T14:30:00.000000Z"
            },
            {
                "id": 3,
                "certificate_name": "ISSA Certified",
                "document_url": "http://localhost/storage/certifications/issa-cert.jpg",
                "has_document": true,
                "date_added": "05 Nov, 2023",
                "created_at": "2023-11-05T13:15:00.000000Z"
            }
        ],
        "total_certifications": 3
    }
}
```

---

### 4. Get Trainer Testimonials

**Endpoint:** `GET /api/client/trainers/{trainerId}/testimonials`

**Description:** Get paginated testimonials for a specific trainer with sorting options.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|--------------|
| `trainerId` | integer | Yes | The trainer's user ID |

#### Query Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `per_page` | integer | No | Results per page (5-50) | `10` |
| `sort_by` | string | No | Sort field: `rating`, `date`, `likes` | `rating` |
| `sort_order` | string | No | Sort order: `asc`, `desc` | `desc` |

#### Example Request
```bash
GET /api/client/trainers/15/testimonials?sort_by=rating&sort_order=desc&per_page=5
```

#### Example Response
```json
{
    "success": true,
    "message": "Trainer testimonials retrieved successfully",
    "data": {
        "trainer": {
            "id": 15,
            "name": "Sarah Johnson",
            "designation": "Certified Personal Trainer"
        },
        "testimonials": {
            "current_page": 1,
            "data": [
                {
                    "id": 25,
                    "client_name": "Emily Carter",
                    "client_profile_image": "http://localhost/storage/profiles/emily.jpg",
                    "rating": 5,
                    "comments": "Sarah is an amazing trainer! She helped me lose 20 pounds and gain so much strength. Her workouts are challenging but fun, and she always motivates me to push harder. I've been training with her for 6 months and the results speak for themselves!",
                    "likes": 8,
                    "dislikes": 0,
                    "date_posted": "10 Feb, 2024",
                    "created_at": "2024-02-10T16:45:00.000000Z"
                },
                {
                    "id": 26,
                    "client_name": "David Wilson",
                    "client_profile_image": null,
                    "rating": 5,
                    "comments": "Excellent trainer with great knowledge and patience.",
                    "likes": 6,
                    "dislikes": 0,
                    "date_posted": "08 Feb, 2024",
                    "created_at": "2024-02-08T10:30:00.000000Z"
                }
            ],
            "first_page_url": "http://localhost/api/client/trainers/15/testimonials?page=1",
            "from": 1,
            "last_page": 3,
            "last_page_url": "http://localhost/api/client/trainers/15/testimonials?page=3",
            "links": [...],
            "next_page_url": "http://localhost/api/client/trainers/15/testimonials?page=2",
            "path": "http://localhost/api/client/trainers/15/testimonials",
            "per_page": 5,
            "prev_page_url": null,
            "to": 5,
            "total": 12
        },
        "statistics": {
            "total_testimonials": 12,
            "average_rating": 4.8,
            "total_likes": 45,
            "total_dislikes": 2
        }
    }
}
```

---

## Error Responses

### Authentication Errors

**401 Unauthorized** - Missing or invalid token
```json
{
    "success": false,
    "message": "Unauthenticated",
    "error": "Please login to access this area."
}
```

**403 Forbidden** - User doesn't have client role
```json
{
    "success": false,
    "message": "Access Denied",
    "error": "Client access required. Your role: trainer"
}
```

### Validation Errors

**422 Unprocessable Entity** - Invalid request parameters
```json
{
    "success": false,
    "message": "Validation Error",
    "data": {
        "experience_min": ["The experience min must be between 0 and 50."]
    }
}
```

### Resource Not Found

**404 Not Found** - Trainer not found or inactive
```json
{
    "success": false,
    "message": "Trainer Not Found",
    "data": {
        "error": "Trainer not found or inactive"
    }
}
```

### Server Errors

**500 Internal Server Error** - Server-side error
```json
{
    "success": false,
    "message": "Search Failed",
    "data": {
        "error": "Unable to search trainers"
    }
}
```

---

## Usage Examples

### JavaScript/Fetch Example

```javascript
// Find trainers with search and filters
const findTrainers = async () => {
    const response = await fetch('/api/client/trainers/find?search=yoga&rating_min=4', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    
    if (data.success) {
        console.log('Trainers found:', data.data.data);
    } else {
        console.error('Error:', data.message);
    }
};

// Get trainer profile
const getTrainerProfile = async (trainerId) => {
    const response = await fetch(`/api/client/trainers/${trainerId}/profile`, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    
    if (data.success) {
        console.log('Trainer profile:', data.data);
    } else {
        console.error('Error:', data.message);
    }
};
```

### cURL Examples

```bash
# Find trainers
curl -X GET "http://localhost/api/client/trainers/find?search=yoga&rating_min=4" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"

# Get trainer profile
curl -X GET "http://localhost/api/client/trainers/15/profile" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"

# Get trainer certifications
curl -X GET "http://localhost/api/client/trainers/15/certifications" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"

# Get trainer testimonials
curl -X GET "http://localhost/api/client/trainers/15/testimonials?sort_by=rating&sort_order=desc" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## Rate Limiting

All API endpoints are subject to rate limiting:
- **60 requests per minute** per authenticated user
- Rate limit headers are included in responses:
  - `X-RateLimit-Limit`: Maximum requests per minute
  - `X-RateLimit-Remaining`: Remaining requests in current window
  - `X-RateLimit-Reset`: Time when rate limit resets

---

## Best Practices

1. **Always include proper headers** for authentication and content type
2. **Handle pagination** properly when dealing with large result sets
3. **Implement proper error handling** for all possible response codes
4. **Cache trainer profiles** on the client side to reduce API calls
5. **Use appropriate filters** to reduce response size and improve performance
6. **Respect rate limits** and implement retry logic with exponential backoff
7. **Validate user input** before making API calls
8. **Use HTTPS** in production environments

---

## Support

For API support and questions:
- **Email:** api-support@go-globe.com
- **Documentation:** [API Documentation](./API_DOCUMENTATION.md)
- **Status Page:** [API Status](http://localhost/api/system/status)