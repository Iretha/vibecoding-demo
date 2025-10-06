# 🔐 Laravel Authentication API

A complete authentication system built with **Laravel 12**, **Fortify**, and **Sanctum**.

## 📋 Table of Contents

- [Features](#-features)
- [Quick Start](#-quick-start)
- [API Endpoints](#-api-endpoints)
- [Documentation](#-documentation)
- [Test Accounts](#-test-accounts)
- [Tech Stack](#-tech-stack)

## ✨ Features

- ✅ **User Registration** with email verification
- ✅ **Login/Logout** with Remember Me (1h / 30d TTL)
- ✅ **Password Reset** via email
- ✅ **Email Verification** (required)
- ✅ **Token Refresh** for session management
- ✅ **Rate Limiting** (login, forgot-password, refresh)
- ✅ **Account Lockout** (5 attempts, 15min)
- ✅ **Audit Logging** (all auth events)
- ✅ **Multi-device Sessions**
- ✅ **Soft Deletes** (prevent email reuse)
- ✅ **Strong Password Validation**
- ✅ **CORS Support**

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose running
- MailHog for email testing (included in docker-compose.yml)

### Installation (8 Steps)

```bash
# 1. Install Fortify
docker compose exec php_fpm composer require laravel/fortify

# 2. Publish Sanctum
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. Setup .env (if needed)
cp env.template .env
# Edit .env: Replace ##USERNAME## with vibecode-full-stack-starter-kit

# 4. Generate key
docker compose exec php_fpm php artisan key:generate

# 5. Migrate
docker compose exec php_fpm php artisan migrate

# 6. Seed (optional)
docker compose exec php_fpm php artisan db:seed

# 7. Clear caches
docker compose exec php_fpm php artisan config:clear

# 8. Start queue worker (keep running)
docker compose exec php_fpm php artisan queue:work redis
```

### Verify Installation

```bash
# Test health
curl http://localhost:8201/api/health

# Test login (after seeding)
curl -X POST http://localhost:8201/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Password123!"}'
```

## 🔌 API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/health` | ❌ | Health check |
| `POST` | `/api/v1/auth/register` | ❌ | Register new user |
| `POST` | `/api/v1/auth/login` | ❌ | Login & get token |
| `POST` | `/api/v1/auth/logout` | ✅ | Logout (idempotent) |
| `POST` | `/api/v1/auth/forgot-password` | ❌ | Request reset link |
| `POST` | `/api/v1/auth/reset-password` | ❌ | Reset password |
| `GET` | `/api/v1/auth/email/verify/{id}/{hash}` | ❌ | Verify email |
| `POST` | `/api/v1/auth/email/resend` | ✅ | Resend verification |
| `POST` | `/api/v1/auth/refresh` | ✅ | Refresh token |
| `GET` | `/api/v1/user` | ✅ | Get current user |

### Response Format

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error message",
  "error_code": "AUTH_XXX"
}
```

## 📚 Documentation

| Document | Description |
|----------|-------------|
| **[QUICK_START.md](./QUICK_START.md)** | ⚡ 8 commands to get running |
| **[docs/SETUP.md](./docs/SETUP.md)** | 📖 Complete setup guide |
| **[docs/API.md](./docs/API.md)** | 🔌 Full API reference |
| **[docs/TROUBLESHOOTING.md](./docs/TROUBLESHOOTING.md)** | 🔧 Common issues & solutions |
| **[IMPLEMENTATION_COMPLETE.md](./IMPLEMENTATION_COMPLETE.md)** | ✅ Implementation summary |

## 🔑 Test Accounts

Created by `php artisan db:seed`:

### Verified User (Full Access)
- **Email:** `test@example.com`
- **Password:** `Password123!`

### Unverified User (Limited Access)
- **Email:** `unverified@example.com`
- **Password:** `Password123!`

## 📧 Email Testing

**MailHog** is configured for local testing:
- **Web UI:** http://localhost:8025
- **SMTP:** mailhog:1025

All emails (verification, password reset) appear in MailHog.

## 🛡️ Security Features

### Rate Limiting
- **Login:** 5 attempts/15min (by email), 20 attempts/15min (by IP)
- **Forgot Password:** 10 requests/15min (by IP)
- **Token Refresh:** 10 requests/hour (by user)

### Password Requirements
- Minimum 8 characters
- Mixed case (upper & lower)
- At least 1 number
- At least 1 symbol
- Not in breach databases

### Account Lockout
- Triggered after 5 failed attempts
- Duration: 15 minutes
- Auto-unlocks after expiry

### Token Expiration
- Standard: 1 hour
- Remember Me: 30 days

## 🏗️ Tech Stack

- **Framework:** Laravel 12
- **Authentication:** Laravel Fortify
- **API Tokens:** Laravel Sanctum
- **Database:** MySQL 8.0
- **Cache/Queue:** Redis 7
- **Email:** MailHog (local) / SMTP (production)
- **Server:** Nginx + PHP-FPM 8.2

## 📊 Database Tables

- `users` - User accounts with audit fields
- `password_reset_tokens` - Password reset tokens
- `personal_access_tokens` - Sanctum API tokens
- `audit_logs` - Authentication event logs
- `sessions` - Session storage

## 🔍 Audit Events

All authentication events logged to `audit_logs`:
- `login_success` / `login_failed`
- `account_locked` / `account_unlocked`
- `password_reset_requested` / `password_reset_completed`
- `email_verification_sent` / `email_verified`

## 📝 Error Codes

| Code | Description |
|------|-------------|
| `AUTH_001` | Invalid credentials |
| `AUTH_002` | Account locked |
| `AUTH_003` | Email not verified |
| `AUTH_004` | Rate limit exceeded |
| `AUTH_005` | Invalid/expired token |
| `AUTH_006` | Password reset token invalid |
| `AUTH_007` | Email verification link invalid |
| `AUTH_008` | Account deleted |

## 🧪 Testing Examples

### Register
```bash
curl -X POST http://localhost:8201/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
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
    "password": "Password123!",
    "remember": false
  }'
```

### Get User
```bash
curl http://localhost:8201/api/v1/user \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🐛 Troubleshooting

### Common Issues

**CORS errors?**
- Verify `FRONTEND_URL` in `.env`
- Check `config/cors.php`
- Run: `php artisan config:clear`

**Emails not sending?**
- Check MailHog is running: `docker compose ps mailhog`
- Verify mail config in `.env`
- Visit: http://localhost:8025

**Queue not processing?**
- Start worker: `php artisan queue:work redis`
- Check Redis connection
- View failed jobs: `php artisan queue:failed`

**More help:** See [docs/TROUBLESHOOTING.md](./docs/TROUBLESHOOTING.md)

## 📞 Support

1. Check [TROUBLESHOOTING.md](./docs/TROUBLESHOOTING.md)
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify containers: `docker compose ps`
4. Check queue: `php artisan queue:monitor`

## 📄 License

MIT License - See project root for details

---

**🎉 Complete authentication system ready to use!**

Start with [QUICK_START.md](./QUICK_START.md) for the fastest setup.
