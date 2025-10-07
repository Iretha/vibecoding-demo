# Swagger UI Setup Guide

## Overview
Swagger UI has been integrated into your Docker setup to provide an interactive web interface for your API documentation.

## Access Swagger UI

### URL
```
http://localhost:8206/swagger
```

### What You'll See
- **Interactive API Documentation**: Browse all endpoints with detailed descriptions
- **Try It Out**: Test API endpoints directly from the browser
- **Request/Response Examples**: See example payloads and responses
- **Authentication**: Test with Bearer tokens
- **Code Generation**: Copy cURL commands for different platforms

## Features

### 🔍 **Interactive Testing**
- Click "Try it out" on any endpoint
- Fill in parameters and request body
- Execute requests and see real responses
- Test authentication flows

### 📋 **Request Examples**
- **cURL (bash)**: For Linux/Mac terminals
- **cURL (PowerShell)**: For Windows PowerShell
- **cURL (CMD)**: For Windows Command Prompt

### 🔐 **Authentication Testing**
1. Register a new user
2. Login to get a token
3. Copy the token from the response
4. Click "Authorize" button in Swagger UI
5. Enter: `Bearer YOUR_TOKEN_HERE`
6. Test protected endpoints

## Quick Test Flow

### 1. Health Check
```
GET /health
```
Verify the API is running.

### 2. Register User
```
POST /api/v1/auth/register
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

### 3. Login
```
POST /api/v1/auth/login
{
  "email": "test@example.com",
  "password": "SecurePass123!"
}
```

### 4. Use Token
Copy the `access_token` from login response and use it in the "Authorize" button.

### 5. Test Protected Endpoints
- Get current user: `GET /api/v1/user`
- Refresh token: `POST /api/v1/auth/refresh`
- Logout: `POST /api/v1/auth/logout`

## Troubleshooting

### Swagger UI Not Loading
```bash
# Check if container is running
docker compose ps

# Restart Swagger UI
docker compose restart swagger-ui

# View logs
docker compose logs swagger-ui
```

### API Not Responding
```bash
# Check backend container
docker compose logs backend

# Check PHP-FPM container
docker compose logs php_fpm
```

### CORS Issues
If you get CORS errors when testing from Swagger UI:
1. Check that your backend CORS is configured for `localhost:8206`
2. Verify the API base URL in Swagger UI matches your backend

## Customization

### Modify Swagger Config
Edit `backend/docs/swagger-config.json` to customize:
- Default expansion level
- Request snippet languages
- UI appearance
- Validation settings

### Update API Documentation
Edit `backend/docs/auth-api.yaml` to:
- Add new endpoints
- Update examples
- Modify descriptions
- Change response schemas

## Port Summary
- **Frontend**: http://localhost:8200
- **Backend API**: http://localhost:8201/api
- **Swagger UI**: http://localhost:8206/swagger
- **MailHog**: http://localhost:8025
- **MySQL**: localhost:8203
- **Redis**: localhost:8204

## Next Steps
1. Start the containers: `docker compose up -d`
2. Open Swagger UI: http://localhost:8206/swagger
3. Test the authentication flow
4. Explore all available endpoints
5. Use the generated code snippets in your frontend

