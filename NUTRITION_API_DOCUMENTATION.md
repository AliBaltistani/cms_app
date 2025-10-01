# Nutrition Plans API Documentation

## Overview

This document provides comprehensive API documentation for the Nutrition Plans management system in the Go Globe CMS Application. The API supports complete CRUD operations for trainers to manage nutrition plans and read-only access for clients to view their assigned plans.

## Base URL
```
http://127.0.0.1:8000/api
http://127.0.0.1:8000/api
http://127.0.0.1:8000/api
```

## Authentication

All nutrition API endpoints require authentication using Laravel Sanctum tokens. Include the token in the Authorization header:

```
Authorization: Bearer {your-token-here}
```

## Response Format

All API responses follow a consistent JSON structure:

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

## Trainer Nutrition Management

### Authentication Required
- **Role**: Trainer
- **Middleware**: `auth:sanctum`, `trainer`

### Base Route
```
/api/trainer/nutrition
```

---

### 1. Get All Nutrition Plans

**Endpoint**: `GET /api/trainer/nutrition/plans`

**Description**: Retrieve all nutrition plans created by the authenticated trainer.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number for pagination (default: 1) |
| per_page | integer | No | Items per page (default: 15, max: 100) |
| search | string | No | Search term for plan name or description |
| status | string | No | Filter by status (active, inactive) |
| client_id | integer | No | Filter by specific client |

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plans retrieved successfully",
    "data": {
        "plans": [
            {
                "id": 1,
                "name": "Weight Loss Plan",
                "description": "Comprehensive weight loss nutrition plan",
                "client_id": 5,
                "client_name": "John Doe",
                "trainer_id": 2,
                "status": "active",
                "start_date": "2024-01-15",
                "end_date": "2024-04-15",
                "total_calories": 1800,
                "meals_count": 6,
                "created_at": "2024-01-10T10:30:00Z",
                "updated_at": "2024-01-10T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "per_page": 15,
            "total_items": 42
        }
    }
}
```

---

### 2. Create Nutrition Plan

**Endpoint**: `POST /api/trainer/nutrition/plans`

**Description**: Create a new nutrition plan for a client.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body**:
```json
{
    "name": "Weight Loss Plan",
    "description": "Comprehensive weight loss nutrition plan for 3 months",
    "client_id": 5,
    "start_date": "2024-01-15",
    "end_date": "2024-04-15",
    "target_calories": 1800,
    "target_protein": 120,
    "target_carbs": 180,
    "target_fat": 60,
    "notes": "Focus on lean proteins and complex carbohydrates",
    "status": "active"
}
```

**Validation Rules**:
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | max:255, unique per trainer |
| description | string | No | max:1000 |
| client_id | integer | Yes | exists:users,id,role,client |
| start_date | date | Yes | date, after_or_equal:today |
| end_date | date | Yes | date, after:start_date |
| target_calories | integer | Yes | min:800, max:5000 |
| target_protein | integer | No | min:0, max:500 |
| target_carbs | integer | No | min:0, max:1000 |
| target_fat | integer | No | min:0, max:300 |
| notes | string | No | max:2000 |
| status | string | No | in:active,inactive |

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plan created successfully",
    "data": {
        "plan": {
            "id": 15,
            "name": "Weight Loss Plan",
            "description": "Comprehensive weight loss nutrition plan for 3 months",
            "client_id": 5,
            "client_name": "John Doe",
            "trainer_id": 2,
            "start_date": "2024-01-15",
            "end_date": "2024-04-15",
            "target_calories": 1800,
            "target_protein": 120,
            "target_carbs": 180,
            "target_fat": 60,
            "notes": "Focus on lean proteins and complex carbohydrates",
            "status": "active",
            "created_at": "2024-01-10T10:30:00Z",
            "updated_at": "2024-01-10T10:30:00Z"
        }
    }
}
```

---

### 3. Get Specific Nutrition Plan

**Endpoint**: `GET /api/trainer/nutrition/plans/{id}`

**Description**: Retrieve detailed information about a specific nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Nutrition plan ID |

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plan retrieved successfully",
    "data": {
        "plan": {
            "id": 1,
            "name": "Weight Loss Plan",
            "description": "Comprehensive weight loss nutrition plan",
            "client": {
                "id": 5,
                "name": "John Doe",
                "email": "john@example.com",
                "profile_image": "profile_images/john_doe.jpg"
            },
            "trainer_id": 2,
            "start_date": "2024-01-15",
            "end_date": "2024-04-15",
            "target_calories": 1800,
            "target_protein": 120,
            "target_carbs": 180,
            "target_fat": 60,
            "notes": "Focus on lean proteins and complex carbohydrates",
            "status": "active",
            "meals": [
                {
                    "id": 1,
                    "meal_type": "breakfast",
                    "name": "Protein Oatmeal",
                    "description": "Oats with protein powder and berries",
                    "calories": 350,
                    "protein": 25,
                    "carbs": 45,
                    "fat": 8,
                    "ingredients": "1 cup oats, 1 scoop protein powder, 1/2 cup berries",
                    "instructions": "Mix oats with water, add protein powder and top with berries"
                }
            ],
            "macros": {
                "total_calories": 1800,
                "total_protein": 120,
                "total_carbs": 180,
                "total_fat": 60
            },
            "restrictions": [
                {
                    "id": 1,
                    "type": "allergy",
                    "name": "Nuts",
                    "description": "Avoid all tree nuts and peanuts"
                }
            ],
            "created_at": "2024-01-10T10:30:00Z",
            "updated_at": "2024-01-10T10:30:00Z"
        }
    }
}
```

---

### 4. Get Trainer's Clients

**Endpoint**: `GET /api/trainer/nutrition/clients`

**Description**: Retrieve list of clients assigned to the authenticated trainer for nutrition plan creation.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search by client name or email |
| status | string | No | Filter by client status (active, inactive) |

**Response Example**:
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
                "profile_image": "profile_images/john_doe.jpg",
                "status": "active",
                "nutrition_plans_count": 2,
                "active_plans_count": 1,
                "joined_date": "2023-12-01T00:00:00Z"
            }
        ]
    }
}
```

---

### 5. Add Meal to Nutrition Plan

**Endpoint**: `POST /api/trainer/nutrition/plans/{planId}/meals`

**Description**: Add a new meal to an existing nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| planId | integer | Yes | Nutrition plan ID |

**Request Body**:
```json
{
    "meal_type": "breakfast",
    "name": "Protein Smoothie",
    "description": "High protein breakfast smoothie",
    "calories": 300,
    "protein": 30,
    "carbs": 25,
    "fat": 10,
    "ingredients": "1 banana, 1 scoop protein powder, 1 cup almond milk, 1 tbsp peanut butter",
    "instructions": "Blend all ingredients until smooth",
    "meal_time": "08:00",
    "order": 1
}
```

**Validation Rules**:
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| meal_type | string | Yes | in:breakfast,lunch,dinner,snack |
| name | string | Yes | max:255 |
| description | string | No | max:500 |
| calories | integer | Yes | min:0, max:2000 |
| protein | integer | No | min:0, max:200 |
| carbs | integer | No | min:0, max:300 |
| fat | integer | No | min:0, max:150 |
| ingredients | string | Yes | max:1000 |
| instructions | string | Yes | max:1000 |
| meal_time | time | No | format:H:i |
| order | integer | No | min:1, max:20 |

**Response Example**:
```json
{
    "success": true,
    "message": "Meal added to nutrition plan successfully",
    "data": {
        "meal": {
            "id": 25,
            "nutrition_plan_id": 1,
            "meal_type": "breakfast",
            "name": "Protein Smoothie",
            "description": "High protein breakfast smoothie",
            "calories": 300,
            "protein": 30,
            "carbs": 25,
            "fat": 10,
            "ingredients": "1 banana, 1 scoop protein powder, 1 cup almond milk, 1 tbsp peanut butter",
            "instructions": "Blend all ingredients until smooth",
            "meal_time": "08:00:00",
            "order": 1,
            "created_at": "2024-01-10T10:30:00Z",
            "updated_at": "2024-01-10T10:30:00Z"
        }
    }
}
```

---

### 6. Update Nutrition Plan Macros

**Endpoint**: `PUT /api/trainer/nutrition/plans/{planId}/macros`

**Description**: Update the macro targets for a nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| planId | integer | Yes | Nutrition plan ID |

**Request Body**:
```json
{
    "target_calories": 2000,
    "target_protein": 140,
    "target_carbs": 200,
    "target_fat": 70,
    "notes": "Updated macros based on client progress"
}
```

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plan macros updated successfully",
    "data": {
        "macros": {
            "target_calories": 2000,
            "target_protein": 140,
            "target_carbs": 200,
            "target_fat": 70,
            "updated_at": "2024-01-10T10:30:00Z"
        }
    }
}
```

---

### 7. Update Nutrition Plan Restrictions

**Endpoint**: `PUT /api/trainer/nutrition/plans/{planId}/restrictions`

**Description**: Update dietary restrictions for a nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| planId | integer | Yes | Nutrition plan ID |

**Request Body**:
```json
{
    "restrictions": [
        {
            "type": "allergy",
            "name": "Dairy",
            "description": "Lactose intolerant - avoid all dairy products"
        },
        {
            "type": "preference",
            "name": "Vegetarian",
            "description": "No meat or fish"
        }
    ]
}
```

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plan restrictions updated successfully",
    "data": {
        "restrictions": [
            {
                "id": 1,
                "type": "allergy",
                "name": "Dairy",
                "description": "Lactose intolerant - avoid all dairy products"
            },
            {
                "id": 2,
                "type": "preference",
                "name": "Vegetarian",
                "description": "No meat or fish"
            }
        ]
    }
}
```

---

## Client Nutrition Management (Read-Only)

### Authentication Required
- **Role**: Client
- **Middleware**: `auth:sanctum`, `client`

### Base Route
```
/api/client/nutrition
```

---

### 1. Get Client's Nutrition Plans

**Endpoint**: `GET /api/client/nutrition/plans`

**Description**: Retrieve all nutrition plans assigned to the authenticated client.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status (active, inactive, completed) |
| page | integer | No | Page number for pagination |

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plans retrieved successfully",
    "data": {
        "plans": [
            {
                "id": 1,
                "name": "Weight Loss Plan",
                "description": "Comprehensive weight loss nutrition plan",
                "trainer": {
                    "id": 2,
                    "name": "Jane Smith",
                    "profile_image": "trainers/jane_smith.jpg"
                },
                "start_date": "2024-01-15",
                "end_date": "2024-04-15",
                "target_calories": 1800,
                "status": "active",
                "progress": {
                    "days_completed": 15,
                    "total_days": 90,
                    "completion_percentage": 16.67
                },
                "created_at": "2024-01-10T10:30:00Z"
            }
        ]
    }
}
```

---

### 2. Get Specific Nutrition Plan

**Endpoint**: `GET /api/client/nutrition/plans/{id}`

**Description**: Retrieve detailed information about a specific nutrition plan assigned to the client.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition plan retrieved successfully",
    "data": {
        "plan": {
            "id": 1,
            "name": "Weight Loss Plan",
            "description": "Comprehensive weight loss nutrition plan",
            "trainer": {
                "id": 2,
                "name": "Jane Smith",
                "profile_image": "trainers/jane_smith.jpg",
                "specialization": "Weight Management"
            },
            "start_date": "2024-01-15",
            "end_date": "2024-04-15",
            "target_calories": 1800,
            "target_protein": 120,
            "target_carbs": 180,
            "target_fat": 60,
            "notes": "Focus on lean proteins and complex carbohydrates",
            "status": "active",
            "meals_by_type": {
                "breakfast": 2,
                "lunch": 1,
                "dinner": 1,
                "snack": 2
            },
            "total_meals": 6,
            "restrictions": [
                {
                    "type": "allergy",
                    "name": "Nuts",
                    "description": "Avoid all tree nuts and peanuts"
                }
            ]
        }
    }
}
```

---

### 3. Get Specific Meal

**Endpoint**: `GET /api/client/nutrition/plans/{planId}/meals/{mealId}`

**Description**: Retrieve detailed information about a specific meal in a nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| planId | integer | Yes | Nutrition plan ID |
| mealId | integer | Yes | Meal ID |

**Response Example**:
```json
{
    "success": true,
    "message": "Meal retrieved successfully",
    "data": {
        "meal": {
            "id": 1,
            "meal_type": "breakfast",
            "name": "Protein Oatmeal",
            "description": "Oats with protein powder and berries",
            "calories": 350,
            "protein": 25,
            "carbs": 45,
            "fat": 8,
            "ingredients": "1 cup oats, 1 scoop protein powder, 1/2 cup berries",
            "instructions": "Mix oats with water, add protein powder and top with berries",
            "meal_time": "08:00:00",
            "order": 1,
            "nutrition_breakdown": {
                "calories_percentage": 19.4,
                "protein_percentage": 20.8,
                "carbs_percentage": 25.0,
                "fat_percentage": 13.3
            }
        }
    }
}
```

---

### 4. Get Meals by Type

**Endpoint**: `GET /api/client/nutrition/plans/{planId}/meals/type/{mealType}`

**Description**: Retrieve all meals of a specific type from a nutrition plan.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| planId | integer | Yes | Nutrition plan ID |
| mealType | string | Yes | Meal type (breakfast, lunch, dinner, snack) |

**Response Example**:
```json
{
    "success": true,
    "message": "Breakfast meals retrieved successfully",
    "data": {
        "meal_type": "breakfast",
        "meals": [
            {
                "id": 1,
                "name": "Protein Oatmeal",
                "description": "Oats with protein powder and berries",
                "calories": 350,
                "protein": 25,
                "carbs": 45,
                "fat": 8,
                "meal_time": "08:00:00",
                "order": 1
            },
            {
                "id": 2,
                "name": "Greek Yogurt Parfait",
                "description": "Greek yogurt with granola and fruit",
                "calories": 280,
                "protein": 20,
                "carbs": 35,
                "fat": 6,
                "meal_time": "08:30:00",
                "order": 2
            }
        ],
        "totals": {
            "total_calories": 630,
            "total_protein": 45,
            "total_carbs": 80,
            "total_fat": 14
        }
    }
}
```

---

### 5. Get Nutrition Summary

**Endpoint**: `GET /api/client/nutrition/summary`

**Description**: Get a comprehensive nutrition summary for the authenticated client.

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Response Example**:
```json
{
    "success": true,
    "message": "Nutrition summary retrieved successfully",
    "data": {
        "summary": {
            "active_plans": 1,
            "total_plans": 3,
            "completed_plans": 2,
            "current_plan": {
                "id": 1,
                "name": "Weight Loss Plan",
                "trainer_name": "Jane Smith",
                "days_remaining": 75,
                "progress_percentage": 16.67
            },
            "daily_targets": {
                "calories": 1800,
                "protein": 120,
                "carbs": 180,
                "fat": 60
            },
            "restrictions": [
                {
                    "type": "allergy",
                    "name": "Nuts"
                }
            ],
            "achievements": {
                "plans_completed": 2,
                "days_followed": 180,
                "favorite_meal_type": "breakfast"
            }
        }
    }
}
```

---

## Error Responses

### Common Error Codes

| HTTP Code | Error Type | Description |
|-----------|------------|-------------|
| 400 | Bad Request | Invalid request data or parameters |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Insufficient permissions for the requested action |
| 404 | Not Found | Requested resource does not exist |
| 422 | Validation Error | Request data failed validation rules |
| 500 | Server Error | Internal server error |

### Error Response Format

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": [
            "Specific validation error message"
        ]
    },
    "error_code": "VALIDATION_FAILED"
}
```

### Example Error Responses

**Validation Error (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "target_calories": [
            "The target calories must be at least 800."
        ],
        "client_id": [
            "The selected client id is invalid."
        ]
    },
    "error_code": "VALIDATION_FAILED"
}
```

**Unauthorized Error (401)**:
```json
{
    "success": false,
    "message": "Unauthenticated",
    "error_code": "UNAUTHENTICATED"
}
```

**Forbidden Error (403)**:
```json
{
    "success": false,
    "message": "This action is unauthorized",
    "error_code": "UNAUTHORIZED"
}
```

**Not Found Error (404)**:
```json
{
    "success": false,
    "message": "Nutrition plan not found",
    "error_code": "RESOURCE_NOT_FOUND"
}
```

---

## Rate Limiting

- **Rate Limit**: 60 requests per minute per authenticated user
- **Headers**: Rate limit information is included in response headers
  - `X-RateLimit-Limit`: Maximum requests per minute
  - `X-RateLimit-Remaining`: Remaining requests in current window
  - `X-RateLimit-Reset`: Unix timestamp when the rate limit resets

---

## Testing Examples

### Using cURL

**Get Nutrition Plans**:
```bash
curl -X GET "http://127.0.0.1:8000/api/trainer/nutrition/plans" \
  -H "Authorization: Bearer your-token-here" \
  -H "Accept: application/json"
```

**Create Nutrition Plan**:
```bash
curl -X POST "http://127.0.0.1:8000/api/trainer/nutrition/plans" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Weight Loss Plan",
    "description": "Comprehensive weight loss nutrition plan",
    "client_id": 5,
    "start_date": "2024-01-15",
    "end_date": "2024-04-15",
    "target_calories": 1800,
    "target_protein": 120,
    "target_carbs": 180,
    "target_fat": 60
  }'
```

### Using JavaScript (Fetch API)

```javascript
// Get nutrition plans
const getPlans = async () => {
  try {
    const response = await fetch('http://127.0.0.1:8000/api/trainer/nutrition/plans', {
      method: 'GET',
      headers: {
        'Authorization': 'Bearer your-token-here',
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    console.log(data);
  } catch (error) {
    console.error('Error:', error);
  }
};

// Create nutrition plan
const createPlan = async (planData) => {
  try {
    const response = await fetch('http://127.0.0.1:8000/api/trainer/nutrition/plans', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer your-token-here',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(planData)
    });
    
    const data = await response.json();
    console.log(data);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

---

## Changelog

### Version 1.0.0 (Current)
- Initial API documentation
- Complete CRUD operations for trainers
- Read-only access for clients
- Comprehensive meal management
- Macro and restriction management
- Detailed error handling documentation

---

## Support

For API support and questions:
- **Email**: api-support@goglobe.com
- **Documentation**: http://127.0.0.1:8000/api/documentation
- **Status Page**: http://127.0.0.1:8000/api/system/status

---

*Last Updated: January 10, 2024*
*API Version: 1.0.0*
*Laravel Version: 11.x*