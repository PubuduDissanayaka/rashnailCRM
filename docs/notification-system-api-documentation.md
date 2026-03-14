# Notification System - API Documentation

## Overview

The Notification System API provides programmatic access to send, manage, and retrieve notifications. All API endpoints require authentication.

## Base URL

```
https://your-domain.com/api
```

## Authentication

All API requests require a Bearer token in the Authorization header:

```http
Authorization: Bearer {your_api_token}
```

### Getting an API Token

1. Log into the application
2. Go to Profile → API Tokens
3. Generate a new token with appropriate permissions

## Rate Limiting

- **Default**: 100 requests per minute per token
- **Notification Sending**: 50 notifications per minute
- **Bulk Operations**: 10 requests per minute

Headers included in responses:
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1633046400
```

## Response Format

All responses are in JSON format:

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input data",
    "details": { ... }
  }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Endpoints

### 1. Send Notification

Send a notification to a user.

**Endpoint:** `POST /api/notifications`

**Permissions Required:** `send notifications`

**Request Body:**
```json
{
  "user_id": 123,
  "notification_type": "appointment_reminder",
  "variables": {
    "appointment_date": "2024-12-25",
    "appointment_time": "14:00",
    "service_name": "Haircut"
  },
  "channels": ["email", "in_app"],
  "options": {
    "priority": "high",
    "scheduled_at": "2024-12-24T10:00:00Z",
    "attachments": [
      {
        "name": "appointment_details.pdf",
        "url": "https://example.com/files/123.pdf"
      }
    ]
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "user_id": 123,
    "notification_type": "appointment_reminder",
    "subject": "Appointment Reminder for Haircut",
    "status": "pending",
    "channels": ["email", "in_app"],
    "created_at": "2024-12-24T09:30:00Z",
    "scheduled_at": "2024-12-24T10:00:00Z"
  }
}
```

### 2. Send Bulk Notifications

Send notifications to multiple users.

**Endpoint:** `POST /api/notifications/bulk`

**Permissions Required:** `send notifications`, `bulk operations`

**Request Body:**
```json
{
  "user_ids": [123, 456, 789],
  "notification_type": "system_announcement",
  "variables": {
    "announcement_title": "System Maintenance",
    "announcement_content": "System will be down for maintenance..."
  },
  "channels": ["email"],
  "options": {
    "batch_size": 100,
    "delay_processing": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "batch_id": "batch_abc123",
    "total_users": 3,
    "successful": 3,
    "failed": 0,
    "notifications": [
      {
        "id": 456,
        "user_id": 123,
        "channels": ["email"]
      },
      {
        "id": 457,
        "user_id": 456,
        "channels": ["email"]
      },
      {
        "id": 458,
        "user_id": 789,
        "channels": ["email"]
      }
    ]
  }
}
```

### 3. Get User Notifications

Retrieve notifications for a user.

**Endpoint:** `GET /api/notifications`

**Query Parameters:**
- `user_id` (optional): User ID (default: current user)
- `status` (optional): Filter by status (pending, sent, failed, delivered)
- `type` (optional): Filter by notification type
- `channel` (optional): Filter by channel
- `start_date` (optional): Start date (YYYY-MM-DD)
- `end_date` (optional): End date (YYYY-MM-DD)
- `limit` (optional): Number of results (default: 50, max: 100)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 456,
        "user_id": 123,
        "notification_type": "appointment_reminder",
        "subject": "Appointment Reminder",
        "content": "You have an appointment tomorrow...",
        "channels": ["email", "in_app"],
        "status": "sent",
        "metadata": {
          "variables": { ... },
          "attachments": [ ... ]
        },
        "sent_at": "2024-12-24T10:00:00Z",
        "read_at": null,
        "created_at": "2024-12-24T09:30:00Z"
      }
    ],
    "pagination": {
      "total": 150,
      "per_page": 50,
      "current_page": 1,
      "last_page": 3
    }
  }
}
```

### 4. Get Notification Details

Get details of a specific notification.

**Endpoint:** `GET /api/notifications/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "user_id": 123,
    "notification_type": "appointment_reminder",
    "subject": "Appointment Reminder",
    "content": "You have an appointment tomorrow...",
    "channels": ["email", "in_app"],
    "status": "sent",
    "metadata": { ... },
    "sent_at": "2024-12-24T10:00:00Z",
    "read_at": null,
    "retry_count": 0,
    "last_attempt_at": null,
    "created_at": "2024-12-24T09:30:00Z",
    "updated_at": "2024-12-24T10:00:00Z",
    "delivery_logs": [
      {
        "channel": "email",
        "status": "sent",
        "provider": "smtp_provider",
        "message_id": "abc123@example.com",
        "timestamp": "2024-12-24T10:00:05Z"
      }
    ]
  }
}
```

### 5. Update Notification Status

Update notification status (mark as read, etc.).

**Endpoint:** `PUT /api/notifications/{id}/status`

**Request Body:**
```json
{
  "status": "read",
  "read_at": "2024-12-24T11:00:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "read",
    "read_at": "2024-12-24T11:00:00Z"
  }
}
```

### 6. Retry Failed Notification

Retry sending a failed notification.

**Endpoint:** `POST /api/notifications/{id}/retry`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "pending",
    "retry_count": 1,
    "last_attempt_at": "2024-12-24T11:30:00Z"
  }
}
```

### 7. Get User Notification Settings

Get notification settings for a user.

**Endpoint:** `GET /api/notification-settings`

**Query Parameters:**
- `user_id` (optional): User ID (default: current user)
- `notification_type` (optional): Filter by notification type

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "settings": {
      "attendance_check_in": {
        "email": {
          "is_enabled": true,
          "preferences": {
            "priority": "normal",
            "send_immediately": true
          }
        },
        "in_app": {
          "is_enabled": true,
          "preferences": {
            "sound": "default",
            "vibrate": true
          }
        }
      }
    },
    "system_defaults": {
      "attendance_check_in": {
        "email": {
          "is_enabled": true,
          "preferences": { ... }
        }
      }
    }
  }
}
```

### 8. Update User Notification Settings

Update notification settings for a user.

**Endpoint:** `PUT /api/notification-settings`

**Request Body:**
```json
{
  "settings": [
    {
      "notification_type": "attendance_check_in",
      "channel": "email",
      "is_enabled": true,
      "preferences": {
        "priority": "high",
        "send_immediately": false
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 1,
    "settings": [ ... ]
  }
}
```

### 9. Get Notification Templates

Get available notification templates.

**Endpoint:** `GET /api/notification-templates`

**Query Parameters:**
- `notification_type` (optional): Filter by notification type
- `language` (optional): Filter by language (default: en)
- `active_only` (optional): Only active templates (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "templates": [
      {
        "id": 1,
        "name": "Attendance Check-In Template",
        "notification_type": "attendance_check_in",
        "subject": "Check-In Notification for {{name}}",
        "content": "Hello {{name}}, you checked in at {{check_in_time}}...",
        "variables": ["name", "check_in_time", "location"],
        "language": "en",
        "version": 2,
        "is_active": true
      }
    ]
  }
}
```

### 10. Preview Template

Preview a template with sample variables.

**Endpoint:** `POST /api/notification-templates/preview`

**Request Body:**
```json
{
  "template_id": 1,
  "variables": {
    "name": "John Doe",
    "check_in_time": "09:00 AM",
    "location": "Main Office"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "subject": "Check-In Notification for John Doe",
    "content": "Hello John Doe, you checked in at 09:00 AM...",
    "html_content": "<p>Hello John Doe, you checked in at 09:00 AM...</p>"
  }
}
```

### 11. Get Notification Statistics

Get notification statistics.

**Endpoint:** `GET /api/notification-statistics`

**Query Parameters:**
- `period` (optional): Time period (today, week, month, year)
- `user_id` (optional): Filter by user
- `notification_type` (optional): Filter by type
- `channel` (optional): Filter by channel

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "week",
    "total": 1250,
    "sent": 1200,
    "failed": 50,
    "pending": 0,
    "success_rate": 96.0,
    "by_channel": {
      "email": {
        "total": 800,
        "sent": 780,
        "failed": 20,
        "success_rate": 97.5
      },
      "in_app": {
        "total": 450,
        "sent": 420,
        "failed": 30,
        "success_rate": 93.3
      }
    },
    "by_type": {
      "attendance_check_in": {
        "total": 300,
        "sent": 295,
        "failed": 5
      }
    }
  }
}
```

### 12. Get Queue Status

Get notification queue status.

**Endpoint:** `GET /api/notification-queue/status`

**Response:**
```json
{
  "success": true,
  "data": {
    "queues": {
      "notifications": {
        "pending": 45,
        "failed": 3,
        "oldest_job_age": 15,
        "health_status": "healthy"
      }
    },
    "total_jobs": 45,
    "total_failed": 3,
    "worker_status": {
      "active_workers": 2,
      "last_heartbeat": "2024-12-24T11:45:00Z"
    },
    "processing_rate": 12.5
  }
}
```

## Webhooks

The notification system can send webhooks for certain events.

### Webhook Events

- `notification.sent` - When a notification is successfully sent
- `notification.failed` - When a notification fails to send
- `notification.delivered` - When a notification is delivered
- `notification.read` - When a notification is read by the user

### Webhook Payload

```json
{
  "event": "notification.sent",
  "timestamp": "2024-12-24T10:00:05Z",
  "data": {
    "notification_id": 456,
    "user_id": 123,
    "notification_type": "appointment_reminder",
    "channels": ["email"],
    "provider": "smtp_provider",
    "message_id": "abc123@example.com"
  }
}
```

### Configuring Webhooks

1. Go to System Settings → Notifications → Webhooks
2. Add webhook URL
3. Select events to subscribe to
4. Configure retry policy

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `INVALID_TOKEN` | Invalid or expired API token | 401 |
| `INSUFFICIENT_PERMISSIONS` | User lacks required permissions | 403 |
| `VALIDATION_ERROR` | Invalid request data | 422 |
| `USER_NOT_FOUND` | Specified user not found | 404 |
| `NOTIFICATION_NOT_FOUND` | Notification not found | 404 |
| `TEMPLATE_NOT_FOUND` | Template not found | 404 |
| `RATE_LIMIT_EXCEEDED` | Rate limit exceeded | 429 |
| `CHANNEL_DISABLED` | Notification channel disabled | 400 |
| `PROVIDER_UNAVAILABLE` | Notification provider unavailable | 503 |
| `BATCH_PROCESSING_ERROR` | Batch processing failed | 500 |

## SDK Examples

### PHP (Laravel)

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

$notification = $notificationService->sendNotification(
    $user,
    'appointment_reminder',
    ['appointment_date' => '2024-12-25'],
    ['email', 'in_app']
);
```

### JavaScript (Node.js)

```javascript
const axios = require('axios');

const api = axios.create({
    baseURL: 'https://your-domain.com/api',
    headers: {
        'Authorization': `Bearer ${API_TOKEN}`
    }
});

// Send notification
const response = await api.post('/notifications', {
    user_id: 123,
    notification_type: 'appointment_reminder',
    variables: {
        appointment_date: '2024-12-25'
    },
    channels: ['email', 'in_app']
});
```

### Python

```python
import requests

headers = {
    'Authorization': f'Bearer {API_TOKEN}'
}

response = requests.post(
    'https://your-domain.com/api/notifications',
    json={
        'user_id': 123,
        'notification_type': 'appointment_reminder',
        'variables': {
            'appointment_date': '2024-12-25'
        },
        'channels': ['email', 'in_app']
    },
    headers=headers
)
```

## Testing

### Sandbox Environment

Use the sandbox environment for testing:
```
https://sandbox.your-domain.com/api
```

### Test Credentials

- **API Token:** `test_token_123`
- **User ID:** `test_user_456`

### Test Notification Types

- `test_notification` - For testing purposes only
- `test_bulk` - For bulk notification testing

## Migration Guide

### Version 1.x to 2.0

#### Breaking Changes
1. API endpoint changed from `/v1/notifications` to `/api/notifications`
2. Response format standardized
3. Authentication changed from API key to Bearer token

#### Migration Steps
1. Update base URL
2. Update authentication headers
3. Update response handling
4. Test with sandbox environment

## Support

For API support:
- **Email:** api-support@example.com
- **Documentation:** https://docs.example.com
- **Status Page:** https://status.example.com

## Changelog

### Version 2.0 (December 2024)
- Added bulk notification endpoints
- Improved error handling
- Added webhook support
- Enhanced statistics endpoints

### Version 1.5 (October 2024)
- Added template preview
- Added queue monitoring
- Improved rate limiting

### Version 1.0 (August 2024)
- Initial release
- Basic notification sending
