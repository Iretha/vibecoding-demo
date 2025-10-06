# Task: Backend Implementation of Authentication Feature with Laravel Fortify
## 1. Context
### Backend Tech Stack
- Framework: Laravel 10.x + PHP 8.2
- Web Server: Nginx (Port 8201)
- Database: MySQL 8.0 (Port 8203)
- Cache: Redis 7 (Port 8204)
- API consumers: Web SPA (localhost:8200) and mobile apps

### Backend Project Details
- Type: REST API backend (JSON responses only, except special cases)
- Current State: New authentication feature in existing codebase
- Base Directory: /backend
- Deployment: Development
- Migration Strategy: Fresh install (no existing users table to migrate)

### Core Features (Use Laravel Fortify)
- Registration
- Login with Remember Me
- Password Reset
- Email Verification (required before access)
- Password Confirmation

### API Routes
- All auth endpoints under: /api/v1/auth/
- POST /api/v1/auth/register
  - User needs to login after registration, do not include token in response
- POST /api/v1/auth/login
- POST /api/v1/auth/logout
  - 200: Success (idempotent - always returns 200, even if token is invalid/expired/missing)
  - Implementation: Attempt revocation, return success regardless of outcome
- POST /api/v1/auth/forgot-password
- POST /api/v1/auth/reset-password
  - Request:
    {
      "token": "string",
      "email": "string",
      "password": "string (min 8 chars, mixed case, numbers, symbols)",
      "password_confirmation": "string (must match password)"
    }
- GET  /api/v1/auth/email/verify/{id}/{hash}
- POST /api/v1/auth/email/resend

- POST /api/v1/auth/refresh
  - Authentication: Requires valid Bearer token in Authorization header
  - Request body: Empty (token extracted from header)
  - Response: New token with fresh TTL (same duration as original)
  - Behavior: Issues new token for this device, revokes old token for the same device immediately

- GET /api/v1/user
  - Authentication: Requires valid Bearer token in Authorization header
  - Email Verification: required
  - Response: Current user object
  - Status Codes:
    - 200: Success
    - 401: Unauthenticated

- GET /api/health
  - Authentication: None (public endpoint)
  - Use case: Load balancer health checks, monitoring
  - Rate limiting: Exempt (high-frequency polling expected)
  - Response: Success (200):
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

  - Response: Failure (503):
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

### Environment Configuration
Use existing /backend/.env
- use existing Database config
- use existing APP_URL

Add missing:
```
FRONTEND_URL=http://localhost:8200

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8200
SESSION_DOMAIN=localhost

# Queue
QUEUE_CONNECTION=redis

```
## 2. API Specifications
Standard JSON Response Format
All API responses must follow this structure:

### Success Response:
```
json{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "user": {...},
    "token": "...",
    "expires_at": "2025-10-06T15:30:00Z"
  }
}
```
### Error Response:
```
json{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The email field is required."]
  },
  "error_code": "AUTH_001"
}
```

### HTTP Status Codes Mapping:
- 200 OK - Successful operation
- 201 Created - Registration successful
- 400 Bad Request - Validation errors
- 401 Unauthorized - Invalid credentials, missing token
- 403 Forbidden - Email not verified, account locked
- 422 Unprocessable Entity - Validation failed
- 429 Too Many Requests - Rate limit exceeded
- 500 Internal Server Error - Server error

### Error Codes Mapping:
- AUTH_001: Invalid credentials
- AUTH_002: Account locked
- AUTH_003: Email not verified
- AUTH_004: Rate limit exceeded
- AUTH_005: Invalid or expired token
- AUTH_006: Password reset token invalid/expired
- AUTH_007: Email verification link invalid/expired
- AUTH_008: This account has been deleted. Contact support to restore.

### API Versioning Strategy
Current: /api/v1/auth/*
Future: /api/v2/auth/* (parallel deployment)
Deprecation: v1 does not expire

### ID Tracing
Include X-Request-ID header (UUID v4) in all responses for tracing

## 3. Security Requirements

### Rate Limiting Strategy
**Login endpoint:**
- By email: 5 attempts per 15 minutes (prevents credential stuffing)
- By IP: 20 attempts per 15 minutes (prevents distributed attacks)
- Implementation: Check both, block if either limit exceeded
- Ublock automatically after 15 minutes
- Successful login before lockout resets failed_login_attempts

**Other endpoints (forgot-password, resend-verification):**
- By IP: 10 requests per 15 minutes
- Ublock automatically after 15 minutes

### Token Refresh Limits
- Add rate limit for token refresh - by user_id: 10 refreshes per hour

### Token Refresh Behavior
Token Refresh Behavior:
  - Each token represents one session (not strictly one device)
  - On refresh: Issue new token, revoke ONLY the token used in the request
  - Multi-session support: User can have multiple active tokens
  - Clarification: "Device" means "session" (one login = one token/session)

### Token Persistence
- **Storage**: personal_access_tokens table (Sanctum default)
- **Cleanup Strategy**: 
  - MVP: No automated cleanup (expired tokens remain in DB)
  - Future: Laravel command for pruning (not in scope)

### Rate Limiting Implementation:
  - Storage: Redis (CACHE_DRIVER=redis)
  - Keys: Format "rate_limit:{type}:{identifier}"
  - TTL: 15 minutes (auto-cleanup)
  - Use Laravel's RateLimiter facade

### Guard: Use sanctum guard
Token Name: auth-token
Standard Token TTL: 1 hour
Remember Me Token TTL: 30 days (triggered by "remember": true in login request)
Storage: Frontend Responsibility
Usage: Authorization: Bearer {token} header
Response: Always include expires_at timestamp in ISO 8601 format

### Multi-Device Handling:
- Allow multiple active tokens, one per device
- Logout Behavior: Revoke only current token for the current device
- Token Revocation Rules:
  - Revoke all tokens when password is changed
  - Revoke all tokens when account is deleted
  - Expired tokens are automatically invalidated (no manual cleanup needed initially)

### CORS Configuration
php'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:8200'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],
'exposed_headers' => [],
'max_age' => 0,

### Validation Rules
Email: required|email|unique:users,email (on registration)
Password: Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
Name: required|string|max:255

### Email Verification
Interface: Use MustVerifyEmail interface on User model
Link Expiration: 60 minutes (configurable in config/auth.php)
Signed URLs: Use Laravel's signed URL feature
Unverified User Behavior: Return 403 with message "Please verify your email address"
Post-Registration Flow: Ask user to login

### Password Reset
Token Expiration: 60 minutes (hard cutoff)
  - After 60 minutes: Return 422 with error_code AUTH_006
  - No grace period or soft warnings
  - Config: config/auth.php → 'passwords.users.expire' => 60
Token Storage: password_reset_tokens table (Laravel default)
Old Password Requirement: Not required (it's a reset, not change)
Post-Reset Actions: Invalidate all existing tokens for this device, force re-login on this device

### Password Reset Verification Rules
- Scenario 1: User registered but never verified
  → Block password reset, show: "Please verify your email first"

### Data Protection
Email: Unique at database level (unique index)
Soft Deletes: Enabled for user accounts
Deleted User Re-registration: Block re-registration with soft-deleted email (restore account instead)
Future: Admin panel restoration feature

### Soft Delete Behavior:
- Registration with soft-deleted email: 
  → Return 422 with message: "This account has been deleted. Contact support to restore."
  → Error code: AUTH_008
- Manual restoration: Admin runs `User::withTrashed()->where('email', '...')->restore()`

### Audit Logging Implementation
Audit Fields: last_login_at, last_login_ip, failed_login_attempts, locked_until
Storage: audit_logs table
Columns: id, event, user_id (nullable), ip_address, user_agent, metadata (JSON), created_at
Implementation: Laravel Event Listeners
Queue Processing: Queue audit logs (QUEUE_CONNECTION=redis)
Audit Log Access: Database-only
- No API endpoint in MVP
- Admin queries directly via MySQL client
Cleanup: Future Enhancement - delete entries older than 30 days

#### Events to Log:
login_success - {"remember": true/false}
login_failed - {"email": "user@example.com", "reason": "invalid_credentials"}
account_locked - {"email": "user@example.com", "locked_until": "timestamp"}
account_unlocked - {"email": "user@example.com", "method": "automatic|manual"}
password_reset_requested - {"email": "user@example.com"}
password_reset_completed - {"user_id": 123}
email_verification_sent - {"user_id": 123}
email_verified - {"user_id": 123}
Email Notifications (Create config/audit.php):
phpreturn [
    'notify_on' => [
        'account_locked' => true,
        'password_changed' => true,
        'login_from_new_location' => false, // Disabled for simplicity
    ],
    'queue_notifications' => false,
];
New Location Detection: Disable feature (set to false in config)

### Error Messages
Generic Auth Failures: "Invalid credentials" (never reveal email vs password)
Account Locked: "Your account has been locked due to multiple failed login attempts. Please try again in X minutes."
Email Not Verified: "Please verify your email address before continuing."
Rate Limited: "Too many requests. Please try again later."

## 4. Database Requirements
Migrations
1. Users Table (enhance default Laravel users migration):
phpSchema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    
    // Audit fields
    $table->timestamp('last_login_at')->nullable();
    $table->string('last_login_ip', 45)->nullable();
    $table->unsignedTinyInteger('failed_login_attempts')->default(0);
    $table->timestamp('locked_until')->nullable();
    
    $table->softDeletes();
    $table->timestamps();
    
    // Indexes
    $table->index('email');
    $table->index('locked_until');
});
2. Audit Logs Table:
phpSchema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event');
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('ip_address', 45);
    $table->text('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at');
    
    // Indexes
    $table->index('event');
    $table->index('user_id');
    $table->index('created_at');
});
Database Seeder
Create DatabaseSeeder with test users:
php// Create 1 verified user for testing
User::factory()->create([
    'email' => 'test@example.com',
    'password' => bcrypt('Password123!'),
    'email_verified_at' => now(),
]);

// Create 1 unverified user for testing verification flow
User::factory()->create([
    'email' => 'unverified@example.com',
    'password' => bcrypt('Password123!'),
    'email_verified_at' => null,
]);
5. Implementation Requirements
Required Components
1. Fortify Configuration (config/fortify.php):

Enable: registration, reset passwords, email verification
Disable: update profile information, update passwords, two-factor authentication
Set views to false (API only)

2. Custom Action Classes:

App\Actions\Fortify\CreateNewUser - Handle registration with audit logging
App\Actions\Fortify\ResetUserPassword - Handle password reset with token revocation
App\Actions\Fortify\UpdateUserPassword - Handle password update (for future use)

3. Middleware:

App\Http\Middleware\CheckAccountLocked - Verify account is not locked
App\Http\Middleware\EnsureEmailIsVerified - Custom email verification check with proper JSON response
Apply middleware to protected routes

4. Event Listeners:

App\Listeners\LoginAttemptListener - Log login attempts (success/failure)
App\Listeners\PasswordResetListener - Log password reset events
App\Listeners\EmailVerificationListener - Log email verification events
App\Listeners\AccountLockListener - Send email notification when account is locked

5. Fortify Response Customization:

Customize all Fortify responses to match the standard JSON format
Use Fortify's response hooks in FortifyServiceProvider

6. Custom Routes (in routes/api.php):
Override Fortify's default routes to use /api/v1/auth/ prefix
Middleware Priority
Apply in this order on protected routes:

auth:sanctum (authentication)
check.account.locked (account status)
verified (email verification)

6. Expected Deliverables
1. Code Artifacts
All code must include:
Document complex logic and public APIs with PHPDoc
Type hints for parameters and return types

Required Files:

 config/fortify.php - Fortify configuration
 config/audit.php - Audit configuration
 config/cors.php - CORS configuration updates
 app/Actions/Fortify/CreateNewUser.php
 app/Actions/Fortify/ResetUserPassword.php
 app/Actions/Fortify/UpdateUserPassword.php
 app/Http/Middleware/CheckAccountLocked.php
 app/Http/Middleware/EnsureEmailIsVerified.php
 app/Listeners/LoginAttemptListener.php
 app/Listeners/PasswordResetListener.php
 app/Listeners/EmailVerificationListener.php
 app/Listeners/AccountLockListener.php
 app/Providers/FortifyServiceProvider.php - With response customizations
 app/Providers/EventServiceProvider.php - With listener mappings
 database/migrations/xxxx_create_users_table.php
 database/migrations/xxxx_create_audit_logs_table.php
 database/seeders/DatabaseSeeder.php
 routes/api.php - Auth route definitions
 .env.example - With all required variables

2. API Documentation
OpenAPI 3.0 Specification (docs/auth-api.yaml):

Complete endpoint documentation
Request/response examples for each endpoint
Error response examples
Authentication scheme definition
All HTTP status codes documented

Postman Collection (docs/auth-api.postman_collection.json):

All endpoints with example requests
Environment variables for base URL and tokens
Pre-request scripts for token management
Test scenarios documented in request descriptions
Example collections for:

Happy path testing
Error scenarios
Rate limiting testing
Email verification flow

3. Documentation Files
Setup Instructions (docs/SETUP.md):
markdown# Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0 (running on port 8203)
- Redis 7 (running on port 8204)
- MailHog (for local email testing, optional)

# Installation Steps
## Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0 (already configured on port 8203)
- Redis 7 (running on port 8204)
- MailHog (for local email testing, optional)

### Initial Setup
- Run: php artisan migrate
- Run: php artisan db:seed (optional, creates test users)
- Run: php artisan config:clear
- Run: php artisan queue:work redis
- Run: php artisan serve --host=localhost --port=8201

## 5. Email Testing
- Use MailHog (docker run -p 1025:1025 -p 8025:8025 mailhog/mailhog)

### Test Credentials
- Verified user: test@example.com / Password123!
- Unverified user: unverified@example.com / Password123!
API Endpoint Reference (docs/API.md):

All endpoints with descriptions
Request/response examples
Authentication requirements
Rate limiting rules

## 6. Troubleshooting Guide (docs/TROUBLESHOOTING.md):
Common errors and solutions
CORS issues
Email verification not working
Token expiration handling
Account lockout recovery

## 7. Testing Guidance
### Test Scenarios
1. Registration Flow:
✓ Successful registration with valid data
✓ Duplicate email rejection
✓ Unverified user can access /api/v1/user (profile endpoint)
✓ Weak password rejection
✓ Compromised password rejection (use 'password123')
✓ Email verification sent

2. Login Flow:
✓ Successful login with verified email
✓ Login with unverified email (should fail with 403)
✓ Invalid credentials (generic error message)
✓ Remember me functionality (check token TTL)
✓ Rate limiting after 5 failed attempts
✓ Account lockout after 5 failures
✓ Automatic unlock after 15 minutes
✓ Audit log created for login attempts

3. Email Verification:
✓ Resend verification email
✓ Click verification link
✓ Expired verification link (test after 60+ minutes)
✓ Invalid verification link
✓ Already verified email

4. Password Reset:
✓ Request password reset
✓ Receive reset email
✓ Reset password with valid token
✓ Expired token rejection (after 60+ minutes)
✓ All tokens revoked after successful reset
✓ Audit log created

5. Token Management:
✓ Protected route access with valid token
✓ Protected route rejection without token
✓ Expired token rejection
✓ Logout revokes token
✓ Multiple device logins

6. Account Security:
✓ Account locked email notification
✓ Password changed email notification
✓ Soft delete maintains email uniqueness

7. Post-Registration Flow:
User registers → receives 201 response (no token)
User must login → receives token
Unverified users CAN use their token but are LIMITED:
   - ✓ Can access: GET /api/v1/user (to see verification status)
   - ✓ Can access: POST /api/v1/auth/email/resend
   - ✓ Can access: POST /api/v1/auth/logout
   - ✗ Cannot access: Any other protected endpoints (403 + AUTH_003)

Expected HTTP Status Codes by Endpoint
POST /api/v1/auth/register
- 201: Success
- 422: Validation failed

POST /api/v1/auth/login
- 200: Success
- 401: Invalid credentials
- 403: Email not verified / Account locked
- 429: Rate limited

POST /api/v1/auth/logout
- Implementation: Attempt revocation if token present, return 200 regardless
- Rationale: Allows clients to clean up state even after token expires
- No 401 response (idempotent behavior)

POST /api/v1/auth/forgot-password
- 200: Reset link sent
- 422: Invalid email
- 429: Rate limited

POST /api/v1/auth/reset-password
- 200: Password reset successful
- 422: Invalid/expired token

POST /api/v1/auth/email/resend
- 200: Verification email sent
- 429: Rate limited

GET /api/v1/auth/email/verify/{id}/{hash}
Scenarios:
- Special case
  - Success (first time): 
    Redirect to {FRONTEND_URL}/email-verified?status=success
  
  - Already verified: 
    Redirect to {FRONTEND_URL}/email-verified?status=already_verified
  
  - Invalid/expired: 
    Redirect to {FRONTEND_URL}/email-verified?status=error&code=AUTH_007
  
  - User not found: 
    Redirect to {FRONTEND_URL}/email-verified?status=error&code=AUTH_007

Postman: Use provided collection
curl: Example commands in API.md
MailHog: View emails at http://localhost:8025
MySQL Client: Verify database changes
Redis CLI: Check rate limiting keys

## 8. Constraints & Preferences
### Development Principles
✓ Prefer Laravel's built-in features over custom implementations or third-party libs
✓ Use Fortify for all authentication features
✓ Use Sanctum for API token management
✓ Follow Laravel best practices and conventions
✓ Keep code DRY (Don't Repeat Yourself)
✓ Use dependency injection where appropriate

### Code Quality Requirements
✓ PSR-12 coding standards
✓ Meaningful variable and method names
✓ Inline comments for complex logic only
✓ Document public APIs with PHPDoc
✓ Type hints for parameters and return values
✓ Exception handling with proper error messages

### Performance Considerations
✓ Use database indexes for frequently queried fields
✓ Eager load relationships where appropriate
✓ Cache rate limiting data in Redis