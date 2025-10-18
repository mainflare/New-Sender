# WhatsML API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication

All protected endpoints require Bearer token authentication.

### Headers
```
Authorization: Bearer {your_access_token}
Content-Type: application/json
Accept: application/json
```

---

## Authentication Endpoints

### Register User
```http
POST /register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "company": "My Company",
  "timezone": "UTC"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "is_active": true
    },
    "access_token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Login
```http
POST /login
```

**Request Body:**
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
  "data": {
    "user": {...},
    "access_token": "2|xyz789...",
    "token_type": "Bearer"
  }
}
```

### Logout
```http
POST /logout
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /me
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "workspaces": [...],
    "subscription": {...}
  }
}
```

---

## Workspace Endpoints

### List Workspaces
```http
GET /workspaces
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Marketing Workspace",
      "description": "Main workspace",
      "is_active": true,
      "members": [...]
    }
  ]
}
```

### Create Workspace
```http
POST /workspaces
```
*Requires authentication*

**Request Body:**
```json
{
  "name": "New Workspace",
  "description": "Description here",
  "logo": "https://example.com/logo.png"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Workspace created successfully",
  "data": {
    "id": 2,
    "name": "New Workspace",
    "user_id": 1,
    "is_active": true
  }
}
```

### Invite Member
```http
POST /workspaces/{id}/invite
```
*Requires authentication*

**Request Body:**
```json
{
  "user_id": 2,
  "role": "agent"
}
```

**Roles:** `owner`, `admin`, `manager`, `agent`

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Member invited successfully",
  "data": {
    "id": 1,
    "workspace_id": 1,
    "user_id": 2,
    "role": "agent",
    "user": {...}
  }
}
```

---

## WhatsApp Session Endpoints

### Create Session
```http
POST /whatsapp-sessions
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "name": "Main WhatsApp",
  "type": "web"
}
```

**Types:** `web` (WhatsApp Web), `cloud` (Official API)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "WhatsApp session created successfully",
  "data": {
    "id": 1,
    "session_id": "session_abc123_1234567890",
    "name": "Main WhatsApp",
    "type": "web",
    "status": "initializing"
  }
}
```

### Get QR Code
```http
GET /whatsapp-sessions/{id}/qr
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "qrCode": "data:image/png;base64,..."
  }
}
```

### Disconnect Session
```http
POST /whatsapp-sessions/{id}/disconnect
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Session disconnected successfully"
}
```

---

## Contact Endpoints

### List Contacts
```http
GET /contacts?workspace_id=1&per_page=15
```
*Requires authentication*

**Query Parameters:**
- `workspace_id` (required): Workspace ID
- `per_page` (optional): Items per page (default: 15)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "phone_number": "+1234567890",
        "name": "Jane Smith",
        "email": "jane@example.com",
        "tags": ["customer", "vip"],
        "is_valid": true
      }
    ],
    "total": 100
  }
}
```

### Create Contact
```http
POST /contacts
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "phone_number": "+1234567890",
  "name": "Jane Smith",
  "email": "jane@example.com",
  "company": "ABC Corp",
  "tags": ["customer"],
  "custom_fields": {
    "age": "30",
    "interest": "Technology"
  }
}
```

**Response:** `201 Created`

### Import Contacts
```http
POST /contacts/import
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "contacts": [
    {
      "phone_number": "+1234567890",
      "name": "John Doe"
    },
    {
      "phone_number": "+0987654321",
      "name": "Jane Smith"
    }
  ]
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Imported 2 contacts, skipped 0 duplicates",
  "data": {
    "imported": 2,
    "skipped": 0
  }
}
```

### Validate Number
```http
POST /contacts/validate
```
*Requires authentication*

**Request Body:**
```json
{
  "session_id": "session_abc123",
  "phone_number": "+1234567890"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "phone": "+1234567890",
    "isValid": true,
    "isRegistered": true
  }
}
```

---

## Campaign Endpoints

### Create Campaign
```http
POST /campaigns
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "whatsapp_session_id": 1,
  "name": "Summer Sale Campaign",
  "description": "Promotion campaign",
  "type": "bulk",
  "message_content": "Hi {name}, check our summer sale!",
  "target_audiences": [1, 2, 3],
  "delay_between_messages": 5,
  "scheduled_at": "2025-10-20 10:00:00"
}
```

**Types:** `bulk`, `scheduled`, `drip`, `triggered`

**Response:** `201 Created`

### Start Campaign
```http
POST /campaigns/{id}/start
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Campaign started successfully",
  "data": {
    "id": 1,
    "status": "running",
    "started_at": "2025-10-18 12:00:00"
  }
}
```

### Campaign Analytics
```http
GET /campaigns/{id}/analytics
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "total_recipients": 100,
    "sent_count": 95,
    "delivered_count": 90,
    "read_count": 75,
    "failed_count": 5,
    "delivery_rate": 94.74,
    "read_rate": 83.33,
    "status": "completed"
  }
}
```

---

## Conversation Endpoints

### List Conversations
```http
GET /conversations?workspace_id=1&status=open
```
*Requires authentication*

**Query Parameters:**
- `workspace_id` (required)
- `status` (optional): `open`, `pending`, `resolved`, `closed`
- `assigned_to` (optional): User ID
- `is_archived` (optional): `true`/`false`

**Response:** `200 OK`

### Get Conversation Messages
```http
GET /conversations/{id}/messages?per_page=50
```
*Requires authentication*

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "body": "Hello!",
        "type": "text",
        "from_me": false,
        "created_at": "2025-10-18 10:00:00"
      }
    ]
  }
}
```

### Send Message
```http
POST /conversations/{id}/send-message
```
*Requires authentication*

**Request Body:**
```json
{
  "message": "Thank you for contacting us!"
}
```

**Response:** `200 OK`

---

## Chatbot Endpoints

### Create Chatbot
```http
POST /chatbots
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "name": "Support Bot",
  "description": "Customer support chatbot",
  "ai_provider": "openai",
  "model": "gpt-4",
  "system_prompt": "You are a helpful customer support assistant.",
  "use_nlp": true,
  "temperature": 0.7,
  "max_tokens": 500
}
```

**AI Providers:** `openai`, `gemini`

**Response:** `201 Created`

### Train Chatbot
```http
POST /chatbots/{id}/train
```
*Requires authentication*

**Request Body:**
```json
{
  "training_data": [
    {
      "question": "What are your business hours?",
      "answer": "We are open Monday-Friday, 9am-5pm."
    },
    {
      "question": "How can I contact support?",
      "answer": "You can reach us at support@example.com"
    }
  ]
}
```

**Response:** `200 OK`

### Test Chatbot
```http
POST /chatbots/{id}/test
```
*Requires authentication*

**Request Body:**
```json
{
  "message": "What are your hours?"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "response": "We are open Monday-Friday, 9am-5pm.",
    "type": "ai",
    "provider": "openai"
  }
}
```

---

## Template Endpoints

### Create Template
```http
POST /templates
```
*Requires authentication*

**Request Body:**
```json
{
  "workspace_id": 1,
  "name": "Welcome Message",
  "content": "Welcome {name} to our service!",
  "type": "custom",
  "variables": ["name"],
  "category": "marketing"
}
```

**Types:** `official`, `custom`, `quick_reply`

**Response:** `201 Created`

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Access denied"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Internal server error",
  "error": "Error details"
}
```

---

## Rate Limiting

- **Default**: 60 requests per minute per user
- **Bulk operations**: Lower limits apply
- **Headers included in response**:
  - `X-RateLimit-Limit`
  - `X-RateLimit-Remaining`

---

**Version**: 1.0.0  
**Last Updated**: October 2025

