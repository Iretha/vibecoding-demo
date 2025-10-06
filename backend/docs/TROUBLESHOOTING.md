# Troubleshooting Guide

## Common Issues and Solutions

### Installation Issues

#### Issue: "Class 'Laravel\Fortify\FortifyServiceProvider' not found"

**Cause**: Fortify package not installed.

**Solution**:
```bash
docker compose exec php_fpm composer require laravel/fortify
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

#### Issue: "Class 'Laravel\Sanctum\HasApiTokens' not found"

**Cause**: Sanctum package not installed.

**Solution**:
```bash
docker compose exec php_fpm composer require laravel/sanctum
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker compose exec php_fpm php artisan migrate
```

---

### Database Issues

#### Issue: "SQLSTATE[HY000] [2002] Connection refused"

**Cause**: MySQL container not running or wrong credentials.

**Solution**:
```bash
# Check if MySQL is running
docker compose ps mysql

# Restart MySQL
docker compose restart mysql

# Verify .env database credentials match docker-compose.yml
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vibecode-full-stack-starter-kit_app
DB_USERNAME=root
DB_PASSWORD=vibecode-full-stack-starter-kit_mysql_pass
```

#### Issue: "SQLSTATE[42S02]: Base table or view not found"

**Cause**: Migrations not run.

**Solution**:
```bash
docker compose exec php_fpm php artisan migrate
```

#### Issue: Migration fails with foreign key constraint error

**Cause**: Tables created in wrong order or previous partial migration.

**Solution**:
```bash
# Fresh migration (WARNING: Deletes all data)
docker compose exec php_fpm php artisan migrate:fresh

# Or rollback and re-run
docker compose exec php_fpm php artisan migrate:rollback
docker compose exec php_fpm php artisan migrate
```

---

### Redis Issues

#### Issue: "Connection refused" (Redis)

**Cause**: Redis container not running or wrong credentials.

**Solution**:
```bash
# Check if Redis is running
docker compose ps redis

# Restart Redis
docker compose restart redis

# Verify .env Redis credentials
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=vibecode-full-stack-starter-kit_redis_pass
```

#### Issue: Rate limiting not working

**Cause**: Redis not connected or cache driver not set to redis.

**Solution**:
```bash
# Set cache driver in .env
CACHE_DRIVER=redis

# Clear config cache
docker compose exec php_fpm php artisan config:clear

# Test Redis connection
docker compose exec redis redis-cli -a vibecode-full-stack-starter-kit_redis_pass ping
# Expected: PONG
```

---

### CORS Issues

#### Issue: "Access to fetch blocked by CORS policy"

**Cause**: Frontend URL not in allowed origins.

**Solution**:
```bash
# Update .env
FRONTEND_URL=http://localhost:8200

# Verify config/cors.php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:8200')],

# Clear config
docker compose exec php_fpm php artisan config:clear
```

#### Issue: "Preflight request doesn't pass access control check"

**Cause**: Missing or incorrect CORS headers.

**Solution**:
```bash
# Ensure config/cors.php has correct settings
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],
'supports_credentials' => true,

# Clear config
docker compose exec php_fpm php artisan config:clear
```

---

### Email Issues

#### Issue: Emails not being sent

**Cause**: MailHog not running or mail config incorrect.

**Solution**:
```bash
# Check if MailHog is running
docker compose ps mailhog

# Restart MailHog
docker compose restart mailhog

# Verify .env mail config
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# Check MailHog web UI
Open: http://localhost:8025
```

#### Issue: Email verification link returns 403/404

**Cause**: Route not registered or signed URL expired.

**Solution**:
```bash
# Clear route cache
docker compose exec php_fpm php artisan route:clear

# Verify routes exist
docker compose exec php_fpm php artisan route:list | grep verify

# Resend verification email (links expire in 60 min)
POST /api/v1/auth/email/resend
```

---

### Authentication Issues

#### Issue: "Unauthenticated" when using valid token

**Cause**: Sanctum middleware not configured or token format incorrect.

**Solution**:
```bash
# Verify Authorization header format
Authorization: Bearer YOUR_TOKEN_HERE

# Check Sanctum config
SANCTUM_STATEFUL_DOMAINS=localhost:8200,localhost,127.0.0.1

# Ensure personal_access_tokens table exists
docker compose exec php_fpm php artisan migrate

# Clear config
docker compose exec php_fpm php artisan config:clear
```

#### Issue: Login returns 401 for correct credentials

**Cause**: Email not verified or account locked.

**Solution**:
```bash
# Check user in database
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "SELECT id, email, email_verified_at, locked_until FROM users WHERE email='test@example.com';"

# If email_verified_at is NULL, verify email
# If locked_until is in future, wait for unlock or manually unlock:
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "UPDATE users SET locked_until=NULL, failed_login_attempts=0 WHERE email='test@example.com';"
```

#### Issue: Account locked after failed login

**Cause**: More than 5 failed login attempts.

**Solution**:
```bash
# Wait 15 minutes for auto-unlock

# Or manually unlock:
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "UPDATE users SET locked_until=NULL, failed_login_attempts=0 WHERE email='user@example.com';"
```

---

### Rate Limiting Issues

#### Issue: "Too many requests" error

**Cause**: Rate limit exceeded.

**Solution**:
```bash
# Wait for rate limit window to expire (15 min for most endpoints)

# Or manually clear rate limit in Redis
docker compose exec redis redis-cli -a vibecode-full-stack-starter-kit_redis_pass
> KEYS rate_limit:*
> DEL rate_limit:login-email:user@example.com
> EXIT
```

---

### Password Reset Issues

#### Issue: Password reset token expired

**Cause**: Token used after 60 minutes.

**Solution**:
```bash
# Request new password reset
POST /api/v1/auth/forgot-password

# Token expiry is 60 minutes (hard cutoff)
# Check config/auth.php:
'expire' => 60,
```

#### Issue: "This password reset token is invalid"

**Cause**: Token already used, expired, or doesn't match.

**Solution**:
```bash
# Request new password reset token
POST /api/v1/auth/forgot-password

# Check password_reset_tokens table
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "SELECT * FROM password_reset_tokens WHERE email='user@example.com';"
```

---

### Queue Issues

#### Issue: Audit logs not being created

**Cause**: Queue worker not running.

**Solution**:
```bash
# Start queue worker
docker compose exec php_fpm php artisan queue:work redis

# Or in detached mode
docker compose exec -d php_fpm php artisan queue:work redis

# View failed jobs
docker compose exec php_fpm php artisan queue:failed

# Retry failed jobs
docker compose exec php_fpm php artisan queue:retry all
```

#### Issue: Queue jobs failing

**Cause**: Redis not connected or job errors.

**Solution**:
```bash
# Check queue configuration
QUEUE_CONNECTION=redis

# View queue jobs
docker compose exec php_fpm php artisan queue:monitor

# View failed jobs details
docker compose exec php_fpm php artisan queue:failed
```

---

### Token Issues

#### Issue: Token expires too quickly

**Cause**: Not using "remember me" feature.

**Solution**:
```bash
# Include remember flag in login
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "Password123!",
  "remember": true
}

# Standard TTL: 1 hour
# Remember TTL: 30 days
```

#### Issue: Old tokens not being revoked

**Cause**: Manual cleanup required (not automated in MVP).

**Solution**:
```bash
# View tokens
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "SELECT * FROM personal_access_tokens;"

# Manual cleanup (delete expired)
docker compose exec mysql mysql -u root -p'vibecode-full-stack-starter-kit_mysql_pass' \
  vibecode-full-stack-starter-kit_app \
  -e "DELETE FROM personal_access_tokens WHERE expires_at < NOW();"
```

---

### Configuration Issues

#### Issue: Changes to config files not taking effect

**Cause**: Config cached.

**Solution**:
```bash
docker compose exec php_fpm php artisan config:clear
docker compose exec php_fpm php artisan route:clear
docker compose exec php_fpm php artisan cache:clear
```

#### Issue: .env changes not being recognized

**Cause**: Config cached or app not restarted.

**Solution**:
```bash
# Clear config
docker compose exec php_fpm php artisan config:clear

# Restart containers
docker compose restart php_fpm backend
```

---

## Debugging Tips

### Enable Debug Mode

In `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### View Logs

```bash
# Laravel logs
docker compose exec php_fpm tail -f storage/logs/laravel.log

# Nginx logs
docker compose logs -f backend

# PHP-FPM logs
docker compose logs -f php_fpm
```

### Test Database Connection

```bash
docker compose exec php_fpm php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::table('users')->count();
```

### Test Redis Connection

```bash
docker compose exec php_fpm php artisan tinker
>>> Illuminate\Support\Facades\Redis::connection()->ping();
```

### Check Routes

```bash
# List all routes
docker compose exec php_fpm php artisan route:list

# Filter auth routes
docker compose exec php_fpm php artisan route:list | grep auth
```

### Verify Middleware

```bash
# Check if middleware is registered
docker compose exec php_fpm php artisan route:list --columns=uri,middleware

# Test specific route middleware
docker compose exec php_fpm php artisan route:list | grep "v1/user"
```

---

## Getting Help

If you've tried the above solutions and still have issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Review error message carefully
3. Search Laravel documentation: https://laravel.com/docs
4. Check Fortify documentation: https://laravel.com/docs/fortify
5. Check Sanctum documentation: https://laravel.com/docs/sanctum

## Common Error Messages

### "SQLSTATE[42000]: Syntax error"
- Check migration syntax
- Ensure correct MySQL version (8.0+)

### "Target class [...] does not exist"
- Check namespace and use statements
- Clear composer autoload: `composer dump-autoload`

### "Route [...] not defined"
- Clear route cache: `php artisan route:clear`
- Check routes/api.php

### "Call to a member function on null"
- Check user authentication
- Verify relationships loaded

### "The given data was invalid"
- Check request payload format
- Review validation rules



