# Frontend Coding Agent Prompt: User Registration Page

# Project Context
You are working on a Next.js 15 frontend application with TypeScript and Tailwind CSS v4. The project is part of a full-stack starter kit with a Laravel backend API.
Task: Create a User Registration Page
Create a complete user registration page that integrates with the Laravel backend API. The backend API is documented in /backend/docs/auth-api.yaml and the registration endpoint is POST /v1/auth/register.

# Technical Requirements
Framework & Styling:
Next.js 15 with App Router
TypeScript
Tailwind CSS v4 (already configured)
React 19.1.0
API Integration:
Backend URL: http://localhost:8201/api
Registration endpoint: POST /v1/auth/register
Content-Type: application/json

# API Specification
Request Payload:
```
interface RegisterRequest {
  name: string;           // max 255 characters
  email: string;          // valid email format
  password: string;       // min 8 chars, mixed case, numbers, symbols
  password_confirmation: string; // must match password
}
```

Success Response (201):
```
interface RegisterResponse {
  success: boolean;
  message: string; // "Registration successful. Please check your email for verification."
  data: {
    user: {
      id: number;
      name: string;
      email: string;
      email_verified_at: string | null;
      last_login_at: string | null;
      created_at: string;
      updated_at: string;
    }
  }
}
```

Error Response (422):
```
interface ErrorResponse {
  success: false;
  message: string;
  errors?: {
    [field: string]: string[];
  };
  error_code?: string;
}
```

### Password Requirements
Implement client-side validation for:
Minimum 8 characters
Mixed case (upper and lower)
At least one number
At least one symbol
Password confirmation must match

## UI/UX Requirements
### Design:
Modern, clean, and professional design
Responsive layout (mobile-first)
Use Tailwind CSS for styling
Follow the existing design system (Inter font, dark/light mode support)
Include proper loading states and error handling
### Form Features:
Real-time validation with helpful error messages
Password strength indicator
Show/hide password toggle
Form submission with loading spinner
Success message after registration
Clear error display for API validation errors
### User Experience:
Smooth animations and transitions
Accessible form controls (proper labels, ARIA attributes)
Keyboard navigation support
Clear visual feedback for all states

### File Structure
Create the following files:
```
frontend/src/
├── app/
│   └── register/
│       └── page.tsx          # Registration page component
├── components/
│   └── auth/
│       ├── RegisterForm.tsx  # Main registration form
│       ├── PasswordInput.tsx # Password input with strength indicator
│       └── FormField.tsx     # Reusable form field component
└── lib/
    └── api.ts               # API client functions
```

## Implementation Details
### API Client (lib/api.ts):
Create a reusable API client function for registration
Handle HTTP errors properly
Include proper TypeScript types
Use fetch API with proper error handling
### Form Validation:
Client-side validation before API call
Display validation errors inline
Prevent submission if validation fails
Show API validation errors from backend
### State Management:
Use React hooks for form state
Handle loading, success, and error states
Implement proper form reset after successful submission
### Error Handling:
Display network errors
Show API validation errors
Handle unexpected errors gracefully
Provide user-friendly error messages

## Success Flow
After successful registration:
Show success message: "Registration successful. Please check your email for verification."
Optionally redirect to a "check your email" page
Clear the form
Provide link to resend verification email (if needed)

## Additional Considerations
Make the form accessible (WCAG guidelines)
Include proper meta tags for SEO
Add proper TypeScript types for all data structures
Ensure the page works in both light and dark modes
Test responsive design on different screen sizes
Include proper error boundaries

## Example Usage
The registration page should be accessible at /register and provide a complete user registration experience that seamlessly integrates with the Laravel backend API.