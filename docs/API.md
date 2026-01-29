# SVAIP API Documentation

> **Base URL:** `/api`  
> **Authentication:** Session-based (web middleware)  
> **Content-Type:** `application/json`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Flows](#flows)
3. [Result Templates](#result-templates)
4. [Flow Runs](#flow-runs)
5. [Response Format](#response-format)
6. [Error Handling](#error-handling)

---

## Authentication

All API endpoints require the `web` middleware which provides:
- Session management
- CSRF protection
- Cookie handling

### Register

**POST** `/api/auth/register`

Create a new user account and automatically log in.

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, valid email, unique in users table
- `password`: required, string, minimum 8 characters

---

### Login

**POST** `/api/auth/login`

Authenticate user and create session.

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Error Response:** `401 Unauthorized`
```json
{
  "success": false,
  "message": "The provided credentials do not match our records.",
  "errors": {
    "email": ["The provided credentials do not match our records."]
  }
}
```

---

### Logout

**POST** `/api/auth/logout`

**Requires:** Authentication

Log out current user and invalidate session.

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## Flows

### List Flows

**GET** `/api/flows`

**Requires:** Authentication

Get all flows for authenticated user with pagination.

**Query Parameters:**
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number (default: 1)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "flows": [
      {
        "id": 1,
        "title": "Skills Assessment",
        "description": "Evaluate technical skills",
        "is_public": false,
        "slug": "skills-assessment-abc123",
        "cards_count": 5,
        "runs_count": 12,
        "created_at": "2026-01-27T10:00:00Z",
        "updated_at": "2026-01-27T10:00:00Z"
      }
    ],
    "pagination": {
      "total": 50,
      "per_page": 15,
      "current_page": 1,
      "last_page": 4
    }
  }
}
```

---

### Get Flow

**GET** `/api/flows/{id}`

**Requires:** Authentication, Owner of flow

Get single flow details.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Skills Assessment",
    "description": "Evaluate technical skills",
    "is_public": true,
    "slug": "skills-assessment-abc123",
    "cards": [
      {
        "id": 1,
        "question": "What is your experience level?",
        "description": "Select your primary skill level",
        "type": "question",
        "options": ["Beginner", "Advanced"],
        "branches": [2, 3],
        "scoring": [0, 10],
        "skipable": false
      }
    ],
    "layout": {
      "1": {"x": 100, "y": 100},
      "2": {"x": 200, "y": 200}
    },
    "created_at": "2026-01-27T10:00:00Z",
    "updated_at": "2026-01-27T10:00:00Z"
  }
}
```

---

### Create Flow

**POST** `/api/flows`

**Requires:** Authentication

Create a new flow with cards.

**Request:**
```json
{
  "title": "Skills Assessment",
  "description": "Evaluate technical skills",
  "cards": [
    {
      "type": "question",
      "question": "What is your experience?",
      "description": "Select your level",
      "options": ["Beginner", "Advanced"],
      "branches": [2, null],
      "scoring": [0, 10],
      "skipable": false
    },
    {
      "type": "end",
      "question": "Thank you!",
      "description": "Assessment complete"
    }
  ],
  "layout": {
    "1": {"x": 100, "y": 100},
    "2": {"x": 200, "y": 200}
  }
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Flow saved successfully",
  "data": {
    "id": 1,
    "title": "Skills Assessment",
    "slug": "skills-assessment-abc123",
    ...
  }
}
```

**Validation Rules:**
- `title`: required, string, max 255 characters
- `description`: optional, string
- `cards`: required, array, minimum 1 card
- `cards.*.type`: optional, must be "question" or "end"
- `cards.*.question`: required for question cards, string, min 1 character
- `cards.*.options`: required for question cards, array, exactly 2 options
- `cards.*.branches`: optional, array of card indices or null
- `cards.*.scoring`: optional, array of integers
- `layout`: optional, object with card positions

**Special Validation:**
- Cycle detection: Returns 422 if branching creates infinite loops
- End cards: Automatically separated from main flow

---

### Update Flow

**PUT** `/api/flows/{id}`

**Requires:** Authentication, Owner of flow

Update existing flow.

**Request:** Same as Create Flow

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Flow updated successfully",
  "data": { ... }
}
```

---

### Delete Flow

**DELETE** `/api/flows/{id}`

**Requires:** Authentication, Owner of flow

Delete flow and all associated data (cards, runs).

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Flow deleted successfully"
}
```

---

### Toggle Public Status

**POST** `/api/flows/{id}/toggle-public`

**Requires:** Authentication, Owner of flow

Toggle flow public/private status.

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Flow visibility updated",
  "data": {
    "is_public": true,
    "slug": "skills-assessment-abc123"
  }
}
```

---

## Result Templates

### List Templates

**GET** `/api/flows/{flowId}/result-templates`

**Requires:** Authentication, Owner of flow

Get all result templates for a flow.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Expert Level",
      "content": "Congratulations! You scored high.",
      "min_score": 80,
      "max_score": 100,
      "created_at": "2026-01-27T10:00:00Z"
    }
  ]
}
```

---

### Create Template

**POST** `/api/flows/{flowId}/result-templates`

**Request:**
```json
{
  "title": "Expert Level",
  "content": "Congratulations! You scored high.",
  "min_score": 80,
  "max_score": 100
}
```

**Response:** `201 Created`

---

### Update Template

**PUT** `/api/flows/{flowId}/result-templates/{templateId}`

**Request:** Same as Create Template

**Response:** `200 OK`

---

### Delete Template

**DELETE** `/api/flows/{flowId}/result-templates/{templateId}`

**Response:** `200 OK`

---

## Flow Runs

### Create Run

**POST** `/api/flows/{id}/run`

**Requires:** Authentication

Start a new flow run.

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "id": "uuid-here",
    "flow_id": 1,
    "status": "in_progress",
    "created_at": "2026-01-27T10:00:00Z"
  }
}
```

---

### Submit Answer

**POST** `/api/flows/{id}/run/{flowRunId}/answer`

**Request:**
```json
{
  "card_id": 1,
  "answer": 0
}
```

**Response:** `200 OK`

---

### Stop Run

**POST** `/api/flows/{id}/run/{flowRunId}/stop`

**Requires:** Authentication

Complete the flow run and calculate final score.

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": "uuid-here",
    "status": "completed",
    "total_score": 85,
    "result_template": {
      "title": "Expert Level",
      "content": "Congratulations!"
    },
    "share_token": "abc123"
  }
}
```

---

## Response Format

All API endpoints follow this consistent structure:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 401 | Unauthorized | Authentication required or invalid credentials |
| 403 | Forbidden | User doesn't have permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Unexpected server error |

### Common Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Authorization Error (403):**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Flow not found"
}
```

**Cycle Detection (422):**
```json
{
  "success": false,
  "message": "Cycle detected in flow branches. Please check your branching logic."
}
```

---

## CSRF Protection

All POST/PUT/DELETE requests require a CSRF token in the request header:

```javascript
headers: {
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

The token is automatically included in the page via the `<meta>` tag in the layout.

---

## Rate Limiting

Currently no rate limiting is enforced. This may be added in future versions.

---

## Versioning

**No API versioning** - All endpoints use `/api/*` without version prefix.

Previous versions used `/api/v1/*` but this has been removed for simplicity.

---

## Authentication Flow

```
1. User submits login form (Alpine.js)
   ↓
2. POST /api/auth/login with credentials
   ↓
3. Server validates and creates session
   ↓
4. Response with user data (JSON)
   ↓
5. Client stores session cookie automatically
   ↓
6. Subsequent requests include session cookie
   ↓
7. Middleware validates session for protected routes
```

Session cookies are httpOnly, secure, and sameSite for security.

---

## Example: Creating a Flow with JavaScript

```javascript
async function createFlow() {
  const response = await fetch('/api/flows', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      title: 'My Assessment',
      description: 'A simple assessment',
      cards: [
        {
          type: 'question',
          question: 'How are you?',
          options: ['Good', 'Bad'],
          branches: [null, null],
          scoring: [10, 0]
        },
        {
          type: 'end',
          question: 'Thank you!'
        }
      ]
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    console.log('Flow created:', data.data);
  } else {
    console.error('Error:', data.message);
  }
}
```

---

## Frontend Integration

The project includes `api-service.js` which provides a helper class:

```javascript
// Global instance available
window.apiService = new ApiService();

// Usage
const flow = await window.apiService.createFlow(flowData);
const flows = await window.apiService.getFlows();
await window.apiService.updateFlow(id, flowData);
await window.apiService.deleteFlow(id);
```

See `public/js/api-service.js` for full implementation.
