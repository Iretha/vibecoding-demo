# Job Roles Management API

This document describes the Job Roles Management API endpoints that allow users to manage their job roles.

## Base URL
All endpoints are prefixed with `/api/v1/`

## Authentication
All endpoints require authentication using Laravel Sanctum tokens. Include the token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Endpoints

### 1. Add Multiple Job Roles to User

**POST** `/users/{userId}/job-roles`

Add multiple job roles to a user's profile.

#### Request Body
```json
{
  "roles": ["Software Engineer", "Product Manager", "Data Analyst"]
}
```

#### Response (201)
```json
{
  "success": true,
  "message": "Roles processed successfully",
  "data": {
    "added": [
      {
        "role_id": 1,
        "role_name": "Software Engineer"
      },
      {
        "role_id": 2,
        "role_name": "Product Manager"
      }
    ],
    "skipped": [
      {
        "role_id": 3,
        "role_name": "Data Analyst",
        "reason": "User already has this role"
      }
    ]
  }
}
```

### 2. Add Single Job Role to User

**POST** `/users/{userId}/job-roles/single`

Add a single job role to a user's profile.

#### Request Body
```json
{
  "role_name": "Software Engineer"
}
```

#### Response (201)
```json
{
  "success": true,
  "message": "Role processed successfully",
  "data": {
    "added": [
      {
        "role_id": 1,
        "role_name": "Software Engineer"
      }
    ],
    "skipped": []
  }
}
```

### 3. Get User's Job Roles

**GET** `/users/{userId}/job-roles`

Retrieve all job roles assigned to a user with pagination.

#### Query Parameters
- `page` (optional): Page number (default: 1)
- `limit` (optional): Number of results per page (default: 20, max: 100)
- `sort` (optional): Sort field - `role_name` or `assigned_at` (default: `role_name`)

#### Response (200)
```json
{
  "success": true,
  "data": {
    "roles": [
      {
        "role_id": 1,
        "role_name": "Software Engineer",
        "assigned_at": "2025-01-15T10:30:00Z"
      },
      {
        "role_id": 2,
        "role_name": "Product Manager",
        "assigned_at": "2025-01-16T14:15:00Z"
      }
    ],
    "total": 2,
    "page": 1,
    "limit": 20
  }
}
```

### 4. Remove Job Role from User

**DELETE** `/users/{userId}/job-roles/{roleId}`

Remove a specific job role from a user's profile.

#### Response (204)
No content - successful removal

#### Response (404)
```json
{
  "success": false,
  "message": "Role not found for this user",
  "error_code": "ROLES_005"
}
```

### 5. Get All Available Roles

**GET** `/roles`

Retrieve all available job roles in the system.

#### Query Parameters
- `search` (optional): Search term to filter roles by name
- `limit` (optional): Number of results (default: 50, max: 100)

#### Response (200)
```json
{
  "success": true,
  "data": {
    "roles": [
      {
        "role_id": 1,
        "role_name": "Software Engineer",
        "user_count": 150
      },
      {
        "role_id": 2,
        "role_name": "Product Manager",
        "user_count": 75
      }
    ],
    "total": 250
  }
}
```

### 6. Get Popular Roles

**GET** `/roles/popular`

Retrieve the most popular job roles (most used).

#### Query Parameters
- `limit` (optional): Number of results (default: 10, max: 50)

#### Response (200)
```json
{
  "success": true,
  "data": {
    "roles": [
      {
        "role_id": 1,
        "role_name": "Software Engineer",
        "user_count": 150
      },
      {
        "role_id": 2,
        "role_name": "Product Manager",
        "user_count": 75
      }
    ],
    "total": 10
  }
}
```

### 7. Get Specific Role Details

**GET** `/roles/{roleId}`

Retrieve details for a specific role.

#### Response (200)
```json
{
  "success": true,
  "data": {
    "role_id": 1,
    "role_name": "Software Engineer",
    "user_count": 150,
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T10:30:00Z"
  }
}
```

### 8. Get Users with Specific Role

**GET** `/roles/{roleId}/users`

Retrieve all users who have a specific role.

#### Query Parameters
- `page` (optional): Page number (default: 1)
- `limit` (optional): Number of results per page (default: 20, max: 100)

#### Response (200)
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "user_id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "assigned_at": "2025-01-15T10:30:00Z"
      },
      {
        "user_id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "assigned_at": "2025-01-16T14:15:00Z"
      }
    ],
    "total": 150,
    "page": 1,
    "limit": 20
  }
}
```

## Error Responses

All endpoints return consistent error responses:

### 400 Bad Request
```json
{
  "success": false,
  "message": "Role name cannot exceed 100 characters",
  "error_code": "ROLES_003"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized to manage roles for this user",
  "error_code": "ROLES_001"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Role not found",
  "error_code": "ROLES_005"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "roles": ["The roles field is required."]
  },
  "error_code": "ROLES_002"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "An error occurred while adding roles",
  "error_code": "ROLES_004"
}
```

## Business Rules

1. **User Authorization**: Users can only manage their own job roles
2. **Role Limit**: Maximum 20 roles per user
3. **Role Name Length**: Maximum 100 characters
4. **Duplicate Prevention**: Users cannot have duplicate roles
5. **Case Insensitive**: Role names are normalized (title case)
6. **Audit Logging**: All role modifications are logged for audit purposes

## Rate Limiting

All endpoints are subject to the standard API rate limiting policies.

## Examples

### Adding Multiple Roles
```bash
curl -X POST "https://api.example.com/api/v1/users/1/job-roles" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "roles": ["Software Engineer", "Product Manager"]
  }'
```

### Getting User Roles
```bash
curl -X GET "https://api.example.com/api/v1/users/1/job-roles?page=1&limit=10" \
  -H "Authorization: Bearer your-token"
```

### Removing a Role
```bash
curl -X DELETE "https://api.example.com/api/v1/users/1/job-roles/5" \
  -H "Authorization: Bearer your-token"
```
