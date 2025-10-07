# Authentication API Reference

## Base URL
```
http://localhost:8201/api
```

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Error details"]
  },
  "error_code": "AUTH_XXX"
}
```

### Headers
- `X-Request-ID`: UUID for request tracing (included in all responses)
- `Authorization`: `Bearer {token}` (for protected endpoints)

## Error Codes

| Code | Description |
|------|-------------|
| AUTH_001 | Invalid credentials |
| AUTH_002 | Account locked |
| AUTH_003 | Email not verified |
| AUTH_004 | Rate limit exceeded |
| AUTH_005 | Invalid or expired token |
| AUTH_006 | Password reset token invalid/expired |
| AUTH_007 | Email verification link invalid/expired |
| AUTH_008 | Account deleted (contact support) |

## Endpoints

### Health Check

#### `GET /health`

Check API and service health status.

**Authentication**: None  
**Rate Limiting**: None

**Response 200 (Healthy)**:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "services": {
      "database": "connected",
      "redis": "connected"
    }
  }
}
```

**Response 503 (Degraded)**:
```json
{
  "success": false,
  "data": {
    "status": "degraded",
    "services": {
      "database": "connected",
      "redis": "disconnected"
    }
  }
}
```

---

### Registration

#### `POST /v1/auth/register`

Register a new user account.

**Authentication**: None  
**Rate Limiting**: 10 requests per 15 minutes (by IP)

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Password Requirements**:
- Minimum 8 characters
- Mixed case (upper and lower)
- At least one number
- At least one symbol
- Not in common breach databases (uncompromised)

**Response 201 (Created)**:
```json
{
  "success": true,
  "message": "Registration successful. Please check your email for verification.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2025-10-06T10:00:00.000000Z",
      "updated_at": "2025-10-06T10:00:00.000000Z"
    }
  }
}
```

**Response 422 (Validation Failed)**:
```json
{
  "success": false,
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**Response 422 (Deleted Account)**:
```json
{
  "success": false,
  "message": "This account has been deleted. Contact support to restore.",
  "error_code": "AUTH_008"
}
```

---

### Login

#### `POST /v1/auth/login`

Authenticate user and receive access token.

**Authentication**: None  
**Rate Limiting**: 
- 5 attempts per 15 minutes (by email)
- 20 attempts per 15 minutes (by IP)

**Request Body**:
```json
{
  "email": "john@example.com",
  "password": "Password123!",
  "remember": false
}
```

**Response 200 (Success)**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": "2025-10-06T10:05:00.000000Z"
    },
    "token": "1|abc123...",
    "expires_at": "2025-10-06T11:00:00Z"
  }
}
```

**Token TTL**:
- Standard: 1 hour
- Remember Me (`remember: true`): 30 days

**Response 401 (Invalid Credentials)**:
```json
{
  "success": false,
  "message": "Invalid credentials",
  "error_code": "AUTH_001"
}
```

**Response 403 (Account Locked)**:
```json
{
  "success": false,
  "message": "Your account has been locked due to multiple failed login attempts. Please try again in 14 minutes.",
  "error_code": "AUTH_002"
}
```

**Response 403 (Email Not Verified)**:
```json
{
  "success": false,
  "message": "Please verify your email address before continuing.",
  "error_code": "AUTH_003"
}
```

**Response 429 (Rate Limited)**:
```json
{
  "success": false,
  "message": "Too many login attempts. Please try again later.",
  "error_code": "AUTH_004"
}
```

---

### Logout

#### `POST /v1/auth/logout`

Revoke current access token (idempotent).

**Authentication**: Optional  
**Rate Limiting**: None

**Request Body**: Empty

**Response 200 (Always)**:
```json
{
  "success": true,
  "message": "Logout successful"
}
```

**Note**: Always returns 200, even if token is invalid/expired/missing.

---

### Forgot Password

#### `POST /v1/auth/forgot-password`

Request password reset link via email.

**Authentication**: None  
**Rate Limiting**: 10 requests per 15 minutes (by IP)

**Request Body**:
```json
{
  "email": "john@example.com"
}
```

**Response 200 (Success)**:
```json
{
  "success": true,
  "message": "Password reset link sent to your email."
}
```

**Response 422 (User Not Found)**:
```json
{
  "success": false,
  "message": "We could not find a user with that email address.",
  "error_code": "AUTH_001"
}
```

**Response 422 (Email Not Verified)**:
```json
{
  "success": false,
  "message": "Please verify your email first.",
  "error_code": "AUTH_003"
}
```

---

### Reset Password

#### `POST /v1/auth/reset-password`

Reset password using token from email.

**Authentication**: None  
**Rate Limiting**: None

**Request Body**:
```json
{
  "token": "abc123...",
  "email": "john@example.com",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Response 200 (Success)**:
```json
{
  "success": true,
  "message": "Password has been reset successfully"
}
```

**Response 422 (Invalid/Expired Token)**:
```json
{
  "success": false,
  "message": "Invalid or expired token.",
  "error_code": "AUTH_006"
}
```

**Token Expiration**: 60 minutes (hard cutoff)

**Post-Reset Actions**:
- All user tokens are revoked
- User must login again

---

### Email Verification

#### `GET /v1/auth/email/verify/{id}/{hash}`

Verify email address (redirects to frontend).

**Authentication**: None  
**Rate Limiting**: 6 requests per minute

**Parameters**:
- `id`: User ID
- `hash`: Verification hash
- URL must be signed

**Redirects**:
- Success: `{FRONTEND_URL}/email-verified?status=success`
- Already Verified: `{FRONTEND_URL}/email-verified?status=already_verified`
- Invalid/Expired: `{FRONTEND_URL}/email-verified?status=error&code=AUTH_007`

**Link Expiration**: 60 minutes

---

### Resend Verification Email

#### `POST /v1/auth/email/resend`

Resend email verification link.

**Authentication**: Required (Bearer token)  
**Rate Limiting**: 10 requests per 15 minutes (by IP)

**Request Body**: Empty

**Response 200 (Success)**:
```json
{
  "success": true,
  "message": "Verification email sent."
}
```

**Response 400 (Already Verified)**:
```json
{
  "success": false,
  "message": "Email already verified."
}
```

---

### Token Refresh

#### `POST /v1/auth/refresh`

Refresh access token (get new token, revoke old).

**Authentication**: Required (Bearer token)  
**Rate Limiting**: 10 requests per hour (by user ID)

**Request Body**: Empty (token from header)

**Response 200 (Success)**:
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "2|xyz789...",
    "expires_at": "2025-10-06T12:00:00Z"
  }
}
```

**Behavior**:
- Issues new token with same TTL as original
- Revokes old token immediately
- Supports multi-session (doesn't affect other tokens)

---

### Get Current User

#### `GET /v1/user`

Get authenticated user information.

**Authentication**: Required (Bearer token)  
**Email Verification**: Required  
**Rate Limiting**: Standard API rate limit

**Request Body**: Empty

**Response 200 (Success)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-10-06T10:05:00.000000Z",
    "last_login_at": "2025-10-06T10:30:00.000000Z",
    "created_at": "2025-10-06T10:00:00.000000Z",
    "updated_at": "2025-10-06T10:30:00.000000Z"
  }
}
```

**Response 401 (Unauthenticated)**:
```json
{
  "message": "Unauthenticated."
}
```

**Response 403 (Email Not Verified)**:
```json
{
  "success": false,
  "message": "Please verify your email address before continuing.",
  "error_code": "AUTH_003"
}
```

---

## Testing with cURL

### Register
```bash
curl -X POST http://localhost:8201/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### Login
```bash
curl -X POST http://localhost:8201/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123!"
  }'
```

### Get User (with token)
```bash
curl -X GET http://localhost:8201/api/v1/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Logout
```bash
curl -X POST http://localhost:8201/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Refresh Token
```bash
curl -X POST http://localhost:8201/api/v1/auth/refresh \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Rate Limiting

| Endpoint | Limit | Window | Key |
|----------|-------|--------|-----|
| Login | 5 attempts | 15 min | Email |
| Login | 20 attempts | 15 min | IP |
| Register | 10 requests | 15 min | IP |
| Forgot Password | 10 requests | 15 min | IP |
| Resend Email | 10 requests | 15 min | IP |
| Email Verify | 6 requests | 1 min | Signed URL |
| Token Refresh | 10 requests | 1 hour | User ID |
| Health Check | Unlimited | - | - |

---

## Security Notes

### Account Lockout
- Triggered after 5 failed login attempts
- Duration: 15 minutes
- Auto-unlocks after duration
- Successful login resets counter

### Token Management
- Tokens stored in `personal_access_tokens` table
- No automatic cleanup (manual pruning required)
- Revoked on password reset
- Supports multiple active sessions

### Email Verification
- Required for most endpoints
- Unverified users have limited access
- Verification links expire in 60 minutes
- Can resend verification email

### Password Security
- Minimum 8 characters
- Mixed case required
- Numbers and symbols required
- Checked against breach databases
- Hashed with bcrypt

---

## Audit Logging

All authentication events are logged to `audit_logs` table:

- `login_success`
- `login_failed`
- `account_locked`
- `password_reset_requested`
- `password_reset_completed`
- `email_verified`

Access logs via MySQL client (no API endpoint in MVP).




