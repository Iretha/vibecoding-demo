# Backend Authentication Setup Guide

## Prerequisites

- Docker and Docker Compose
- PHP 8.2+ (for local development without Docker)
- Composer (for local development without Docker)
- MySQL 8.0 (running on port 8203 via Docker)
- Redis 7 (running on port 8204 via Docker)
- MailHog (for local email testing, included in docker-compose.yml)

## Installation Steps

### 1. Install Dependencies

**Using Docker (Recommended):**
```bash
docker compose exec php_fpm composer install
```

**Local Development:**
```bash
cd backend
composer install
```

### 2. Install Laravel Fortify and Sanctum

```bash
# Fortify is already required in composer.json
# Sanctum is already required in composer.json

# Publish Sanctum migrations and config
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Fortify config
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

### 3. Environment Configuration

Create `.env` file from template:
```bash
cp env.template .env
```

Update these values in `.env`:
```env
APP_NAME="YourAppName"
APP_KEY=base64:YOUR_KEY_HERE  # Run: php artisan key:generate
APP_URL=http://localhost:8201

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vibecode-full-stack-starter-kit_app
DB_USERNAME=root
DB_PASSWORD=vibecode-full-stack-starter-kit_mysql_pass

CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=vibecode-full-stack-starter-kit_redis_pass
REDIS_PORT=6379

FRONTEND_URL=http://localhost:8200

# Mail Configuration (MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8200,localhost,127.0.0.1
SESSION_DOMAIN=localhost

# Queue
QUEUE_CONNECTION=redis
```

### 4. Generate Application Key

```bash
docker compose exec php_fpm php artisan key:generate
```

### 5. Run Migrations

```bash
docker compose exec php_fpm php artisan migrate
```

This will create:
- `users` table with audit fields
- `password_reset_tokens` table
- `personal_access_tokens` table (Sanctum)
- `audit_logs` table
- `sessions` table

### 6. Seed Test Data (Optional)

```bash
docker compose exec php_fpm php artisan db:seed
```

This creates:
- **Verified User**: `test@example.com` / `Password123!`
- **Unverified User**: `unverified@example.com` / `Password123!`

### 7. Clear Configuration Cache

```bash
docker compose exec php_fpm php artisan config:clear
docker compose exec php_fpm php artisan route:clear
docker compose exec php_fpm php artisan cache:clear
```

### 8. Start Queue Worker

The queue worker is required for audit logging:

```bash
docker compose exec php_fpm php artisan queue:work redis
```

**For persistent queue worker (recommended for development):**
Add to your Docker setup or run in a separate terminal.

### 9. Start Development Server

If using Docker, the server is already running via nginx on port 8201.

**Local Development:**
```bash
php artisan serve --host=localhost --port=8201
```

## Email Testing with MailHog

MailHog is included in the docker-compose.yml file.

- **SMTP Port**: 1025 (for sending emails)
- **Web UI**: http://localhost:8025 (to view emails)

Access MailHog web interface to see:
- Email verification emails
- Password reset emails
- Account locked notifications

## Test Credentials

### Verified User (Email Verified)
- **Email**: test@example.com
- **Password**: Password123!
- **Status**: Can login and access all endpoints

### Unverified User (Email Not Verified)
- **Email**: unverified@example.com
- **Password**: Password123!
- **Status**: Can login but limited access (403 on most endpoints)

## Verify Installation

### 1. Check Health Endpoint
```bash
curl http://localhost:8201/api/health
```

Expected response:
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

### 2. Test Registration
```bash
curl -X POST http://localhost:8201/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### 3. Test Login
```bash
curl -X POST http://localhost:8201/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123!"
  }'
```

## Troubleshooting

### Issue: "Could not find driver" (PDO MySQL)
- Ensure PHP MySQL extension is installed
- In Docker: Already included in Dockerfile

### Issue: "Connection refused" (Redis)
- Ensure Redis is running: `docker compose ps redis`
- Check Redis password in `.env`

### Issue: "CORS errors"
- Verify `FRONTEND_URL` in `.env`
- Check `config/cors.php` settings
- Clear config cache: `php artisan config:clear`

### Issue: Emails not sending
- Check MailHog is running: `docker compose ps mailhog`
- Verify mail config in `.env`
- Check MailHog web UI: http://localhost:8025

### Issue: Queue not processing
- Start queue worker: `php artisan queue:work redis`
- Check Redis connection
- View failed jobs: `php artisan queue:failed`

### Issue: Migrations fail
- Ensure MySQL is running: `docker compose ps mysql`
- Check database credentials in `.env`
- Try: `php artisan migrate:fresh` (WARNING: Drops all tables)

## Next Steps

1. Review [API Documentation](./API.md)
2. Import [Postman Collection](./auth-api.postman_collection.json)
3. Check [Troubleshooting Guide](./TROUBLESHOOTING.md)
4. Test all endpoints using Postman or curl

## Development Workflow

### Running Tests
```bash
docker compose exec php_fpm php artisan test
```

### Code Formatting (Laravel Pint)
```bash
docker compose exec php_fpm ./vendor/bin/pint
```

### View Logs
```bash
# Application logs
docker compose exec php_fpm tail -f storage/logs/laravel.log

# Queue logs
docker compose exec php_fpm php artisan queue:listen --verbose
```

### Database Management
```bash
# Fresh migration with seed
docker compose exec php_fpm php artisan migrate:fresh --seed

# Rollback last migration
docker compose exec php_fpm php artisan migrate:rollback

# Check migration status
docker compose exec php_fpm php artisan migrate:status
```




