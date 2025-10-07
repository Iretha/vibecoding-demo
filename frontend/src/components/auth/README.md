# Authentication Components

This directory contains reusable authentication components for the registration system.

## Components

### FormField
A reusable form field component with built-in error handling and accessibility features.

**Features:**
- Label and error display
- Accessibility attributes (ARIA)
- Dark mode support
- Customizable styling

### PasswordInput
A specialized password input component with strength indicator and show/hide toggle.

**Features:**
- Password strength validation
- Visual strength indicator
- Show/hide password toggle
- Real-time validation feedback
- Accessibility compliant

### RegisterForm
The main registration form component that handles the complete registration flow.

**Features:**
- Client-side validation
- API integration
- Loading states
- Success/error handling
- Responsive design
- Dark mode support

## API Integration

The components integrate with the Laravel backend API:

- **Endpoint:** `POST /v1/auth/register`
- **Base URL:** `http://localhost:8201/api`
- **Content-Type:** `application/json`

## Usage

```tsx
import RegisterForm from '@/components/auth/RegisterForm';

export default function RegisterPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <RegisterForm />
    </div>
  );
}
```

## Validation Rules

### Name
- Required
- Minimum 2 characters
- Maximum 255 characters

### Email
- Required
- Valid email format

### Password
- Required
- Minimum 8 characters
- At least one lowercase letter
- At least one uppercase letter
- At least one number
- At least one special character

### Password Confirmation
- Required
- Must match password

## Error Handling

The form handles both client-side and server-side validation errors:

- **Client-side:** Real-time validation with immediate feedback
- **Server-side:** API validation errors displayed inline
- **Network errors:** User-friendly error messages
- **Success state:** Clear success message with next steps

## Accessibility

All components are built with accessibility in mind:

- Proper ARIA labels and descriptions
- Keyboard navigation support
- Screen reader compatibility
- High contrast support
- Focus management
