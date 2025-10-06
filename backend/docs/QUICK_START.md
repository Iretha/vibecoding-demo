# 🚀 Quick Start Guide - Backend Authentication

## ⚡ 8 Commands to Get Running

Run these commands in order to set up the authentication system:

### 1️⃣ Install Fortify
```bash
docker compose exec php_fpm composer require laravel/fortify
```

### 2️⃣ Publish Sanctum
```bash
docker compose exec php_fpm php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3️⃣ Setup Environment (if .env doesn't exist)
```bash
cp backend/env.template backend/.env
# Then edit backend/.env:
# - Replace ##USERNAME## with: vibecode-full-stack-starter-kit
# - Replace ##BACKEND_PORT## with: 8201
```

### 4️⃣ Generate App Key
```bash
docker compose exec php_fpm php artisan key:generate
```

### 5️⃣ Run Migrations
```bash
docker compose exec php_fpm php artisan migrate
```

### 6️⃣ Seed Test Users
```bash
docker compose exec php_fpm php artisan db:seed
```

### 7️⃣ Clear Caches
```bash
docker compose exec php_fpm php artisan config:clear
```

### 8️⃣ Start Queue Worker
```bash
docker compose exec php_fpm php artisan queue:work redis
```

---

## ✅ Test It Works

### Test Health
```bash
curl http://localhost:8201/api/health
```

### Test Login
```bash
curl -X POST http://localhost:8201/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Password123!"}'
```

### Expected: You get a token! 🎉

---

## 📧 View Emails

**MailHog Web Interface:**  
http://localhost:8025

All verification and password reset emails appear here.

---

## 🔑 Test Accounts

After seeding (`php artisan db:seed`):

**Verified User:**
- Email: `test@example.com`
- Password: `Password123!`

**Unverified User:**
- Email: `unverified@example.com`
- Password: `Password123!`

---

## 📚 Full Documentation

- **Setup:** `backend/docs/SETUP.md`
- **API Reference:** `backend/docs/API.md`
- **Troubleshooting:** `backend/docs/TROUBLESHOOTING.md`
- **Complete Summary:** `BACKEND_AUTH_COMPLETED.md`

---

## 🎯 What You Get

✅ User Registration  
✅ Login/Logout  
✅ Password Reset  
✅ Email Verification  
✅ Token Refresh  
✅ Rate Limiting  
✅ Account Lockout  
✅ Audit Logging  
✅ Multi-device Sessions  

**All 10 endpoints working!**

---

## ⚠️ Don't Forget

Keep queue worker running for audit logs:
```bash
docker compose exec php_fpm php artisan queue:work redis --verbose
```

---

**That's it! You're ready to authenticate! 🚀**

