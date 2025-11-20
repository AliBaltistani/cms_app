# Program Builder API Routes

Complete dynamic RESTful API for the Workout Program Builder with nested resource routing.

## Base URL
```
/api/trainer/programs/{program}/builder
```

## Route Models & Binding

All routes use Laravel route model binding for type-safe resource access:
- `{program}` - Program ID
- `{week}` - Week ID  
- `{day}` - Day ID
- `{circuit}` - Circuit ID
- `{exercise}` - ProgramExercise ID

---

## 1. Column Configuration

### Get Column Configuration
```http
GET /api/trainer/programs/{program}/builder/columns
```

**Response:**
```json
{
  "success": true,
  "data": {
    "columns": [
      {
        "id": "exercise",
        "name": "Exercise",
        "width": "25%",
        "type": "text",
        "required": true
      },
      {
        "id": "set1",
        "name": "Set 1 - rep / w",
        "width": "12%",
        "type": "text",
        "required": false
      }
    ]
  },
  "message": "Column configuration loaded"
}
```

### Update Column Configuration
```http
PUT /api/trainer/programs/{program}/builder/columns
Content-Type: application/json
```

**Request Body:**
```json
{
  "columns": [
    {
      "id": "exercise",
      "name": "Exercise",
      "width": "25%",
      "type": "text",
      "required": true
    },
    {
      "id": "set1",
      "name": "Set 1 - rep / w",
      "width": "12%",
      "type": "text",
      "required": false
    },
    {
      "id": "notes",
      "name": "Notes",
      "width": "15%",
      "type": "text",
      "required": false
    }
  ]
}
```

---

## 2. Week Management

### Create Week
```http
POST /api/trainer/programs/{program}/builder/weeks
Content-Type: application/json
```

**Request Body:**
```json
{
  "week_number": 1,
  "title": "Week 1: Foundation",
  "description": "Building the foundation with basic exercises"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "week": {
      "id": 1,
      "program_id": 3,
      "week_number": 1,
      "title": "Week 1: Foundation",
      "description": "Building the foundation with basic exercises",
      "created_at": "2025-01-20T10:30:00Z"
    }
  },
  "message": "Week created successfully"
}
```

### Get Week Details
```http
GET /api/trainer/programs/{program}/builder/weeks/{week}
```

**Response includes full nested structure** (days → circuits → exercises → sets)

### Update Week
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}
Content-Type: application/json
```

**Request Body (partial update):**
```json
{
  "week_number": 2,
  "title": "Updated Title",
  "description": "Updated description"
}
```

### Delete Week
```http
DELETE /api/trainer/programs/{program}/builder/weeks/{week}
```

---

## 3. Day Management

### Create Day
```http
POST /api/trainer/programs/{program}/builder/weeks/{week}/days
Content-Type: application/json
```

**Request Body:**
```json
{
  "day_number": 1,
  "title": "Monday - Chest & Triceps",
  "description": "Upper body push workout",
  "cool_down": "Light stretching (5 mins)",
  "custom_rows": ["Additional notes row 1", "Additional notes row 2"]
}
```

### Get Day Details
```http
GET /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}
```

### Update Day
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}
Content-Type: application/json
```

**Request Body (partial update):**
```json
{
  "day_number": 1,
  "title": "Updated Day Title",
  "description": "Updated description",
  "cool_down": "Updated cool down routine",
  "custom_rows": ["Updated note 1", "Updated note 2"]
}
```

### Update Day Cool Down
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/cool-down
Content-Type: application/json
```

**Request Body:**
```json
{
  "cool_down": "5 minutes light stretching and foam rolling"
}
```

### Update Day Custom Rows
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/custom-rows
Content-Type: application/json
```

**Request Body:**
```json
{
  "custom_rows": [
    "Warm up with 5 mins cardio",
    "Focus on form over weight",
    "Rest 90 seconds between sets"
  ]
}
```

### Delete Day
```http
DELETE /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}
```

---

## 4. Circuit Management

### Create Circuit
```http
POST /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits
Content-Type: application/json
```

**Request Body:**
```json
{
  "circuit_number": 1,
  "title": "Primary Circuit",
  "description": "Main compound movements"
}
```

### Get Circuit Details
```http
GET /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}
```

### Update Circuit
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}
Content-Type: application/json
```

**Request Body:**
```json
{
  "circuit_number": 1,
  "title": "Updated Circuit Name",
  "description": "Updated description"
}
```

### Delete Circuit
```http
DELETE /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}
```

---

## 5. Exercise Management

### Create Exercise
```http
POST /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}/exercises
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Barbell Bench Press",
  "workout_id": 5,
  "order": 0,
  "tempo": "3-1-2",
  "rest_interval": "90 seconds",
  "notes": "Maintain steady form. No bouncing.",
  "sets": [
    {
      "set_number": 1,
      "reps": 12,
      "weight": 185.5
    },
    {
      "set_number": 2,
      "reps": 10,
      "weight": 195.5
    },
    {
      "set_number": 3,
      "reps": 8,
      "weight": 205.5
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "exercise": {
      "id": 42,
      "circuit_id": 15,
      "workout_id": 5,
      "name": "Barbell Bench Press",
      "order": 0,
      "tempo": "3-1-2",
      "rest_interval": "90 seconds",
      "notes": "Maintain steady form. No bouncing.",
      "created_at": "2025-01-20T10:35:00Z",
      "exerciseSets": [
        {
          "id": 101,
          "program_exercise_id": 42,
          "set_number": 1,
          "reps": 12,
          "weight": 84.37
        },
        {
          "id": 102,
          "program_exercise_id": 42,
          "set_number": 2,
          "reps": 10,
          "weight": 88.68
        },
        {
          "id": 103,
          "program_exercise_id": 42,
          "set_number": 3,
          "reps": 8,
          "weight": 93.04
        }
      ],
      "workout": {
        "id": 5,
        "name": "Barbell Bench Press"
      }
    }
  },
  "message": "Exercise created successfully"
}
```

### Get Exercise Details
```http
GET /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}/exercises/{exercise}
```

### Update Exercise
```http
PUT /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}/exercises/{exercise}
Content-Type: application/json
```

**Request Body (partial update):**
```json
{
  "name": "Updated Exercise Name",
  "tempo": "2-1-2",
  "rest_interval": "120 seconds",
  "notes": "Updated notes",
  "sets": [
    {
      "set_number": 1,
      "reps": 10,
      "weight": 200
    },
    {
      "set_number": 2,
      "reps": 8,
      "weight": 215
    }
  ]
}
```

### Delete Exercise
```http
DELETE /api/trainer/programs/{program}/builder/weeks/{week}/days/{day}/circuits/{circuit}/exercises/{exercise}
```

---

## Error Responses

All endpoints return consistent error responses:

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {
    "week_number": ["Week number already exists for this program"],
    "title": ["Title is required"]
  }
}
```

### Unauthorized (403)
```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": {
    "error": ["Access denied"]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Not Found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Update Failed",
  "errors": {
    "error": ["Unable to update week"]
  }
}
```

---

## Example Usage Patterns

### Create a Complete Week Structure
```javascript
// 1. Create Week
const weekRes = await fetch('/api/trainer/programs/3/builder/weeks', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    week_number: 1,
    title: 'Week 1',
    description: 'Foundation week'
  })
});
const week = await weekRes.json();
const weekId = week.data.week.id;

// 2. Create Day
const dayRes = await fetch(
  `/api/trainer/programs/3/builder/weeks/${weekId}/days`,
  {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      day_number: 1,
      title: 'Monday',
      description: 'Upper Body'
    })
  }
);
const day = await dayRes.json();
const dayId = day.data.day.id;

// 3. Create Circuit
const circuitRes = await fetch(
  `/api/trainer/programs/3/builder/weeks/${weekId}/days/${dayId}/circuits`,
  {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      circuit_number: 1,
      title: 'Main Circuit'
    })
  }
);
const circuit = await circuitRes.json();
const circuitId = circuit.data.circuit.id;

// 4. Create Exercise
const exerciseRes = await fetch(
  `/api/trainer/programs/3/builder/weeks/${weekId}/days/${dayId}/circuits/${circuitId}/exercises`,
  {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      name: 'Bench Press',
      order: 0,
      sets: [
        { set_number: 1, reps: 12, weight: 185 },
        { set_number: 2, reps: 10, weight: 195 },
        { set_number: 3, reps: 8, weight: 205 }
      ]
    })
  }
);
```

### Update Specific Exercise
```javascript
const exerciseId = 42;
await fetch(
  `/api/trainer/programs/3/builder/weeks/1/days/1/circuits/1/exercises/${exerciseId}`,
  {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      tempo: '3-1-2',
      rest_interval: '90 seconds'
    })
  }
);
```

### Update Cool Down for a Day
```javascript
await fetch(
  `/api/trainer/programs/3/builder/weeks/1/days/1/cool-down`,
  {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      cool_down: '5 minutes stretching + foam rolling'
    })
  }
);
```

---

## Authorization

All endpoints require:
- `Authorization: Bearer {sanctum_token}`
- Trainer role verification
- Ownership verification (trainer must own the program)

## HTTP Methods

- `GET` - Retrieve resources
- `POST` - Create new resources
- `PUT` - Update entire resource or specific fields
- `DELETE` - Delete resources

## Notes

- Weight values in requests are in lbs and converted to kg internally
- All nested resources require the full path (program → week → day → circuit → exercise)
- Model binding automatically returns 404 if resource not found or doesn't belong to the hierarchy
