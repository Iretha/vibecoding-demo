'use client';

import React, { useState } from 'react';
import FormField from './FormField';
import PasswordInput from './PasswordInput';
import { registerUser, ApiResponse } from '@/lib/api';

interface FormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface FormErrors {
  name?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  general?: string;
}

export default function RegisterForm() {
  const [formData, setFormData] = useState<FormData>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  // Client-side validation functions
  const validateName = (name: string): string | undefined => {
    if (!name.trim()) return 'Name is required';
    if (name.trim().length < 2) return 'Name must be at least 2 characters';
    if (name.trim().length > 255) return 'Name must be less than 255 characters';
    return undefined;
  };

  const validateEmail = (email: string): string | undefined => {
    if (!email.trim()) return 'Email is required';
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) return 'Please enter a valid email address';
    return undefined;
  };

  const validatePassword = (password: string): string | undefined => {
    if (!password) return 'Password is required';
    if (password.length < 8) return 'Password must be at least 8 characters';
    
    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSymbol = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

    if (!hasLowercase) return 'Password must contain at least one lowercase letter';
    if (!hasUppercase) return 'Password must contain at least one uppercase letter';
    if (!hasNumber) return 'Password must contain at least one number';
    if (!hasSymbol) return 'Password must contain at least one special character';
    
    return undefined;
  };

  const validatePasswordConfirmation = (password: string, confirmation: string): string | undefined => {
    if (!confirmation) return 'Please confirm your password';
    if (password !== confirmation) return 'Passwords do not match';
    return undefined;
  };

  const validateForm = (): boolean => {
    const newErrors: FormErrors = {};

    const nameError = validateName(formData.name);
    if (nameError) newErrors.name = nameError;

    const emailError = validateEmail(formData.email);
    if (emailError) newErrors.email = emailError;

    const passwordError = validatePassword(formData.password);
    if (passwordError) newErrors.password = passwordError;

    const confirmationError = validatePasswordConfirmation(formData.password, formData.password_confirmation);
    if (confirmationError) newErrors.password_confirmation = confirmationError;

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error when user starts typing
    if (errors[name as keyof FormErrors]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    setErrors({});

    try {
      const response: ApiResponse<{ user: { id: number; name: string; email: string; email_verified_at: string | null; last_login_at: string | null; created_at: string; updated_at: string } }> = await registerUser(formData);
      
      if (response.success) {
        setIsSuccess(true);
        setSuccessMessage(response.message || 'Registration successful! Please check your email for verification.');
        setFormData({
          name: '',
          email: '',
          password: '',
          password_confirmation: '',
        });
      } else {
        // Handle API validation errors
        if (response.errors) {
          const apiErrors: FormErrors = {};
          Object.entries(response.errors).forEach(([field, messages]) => {
            if (messages && messages.length > 0) {
              apiErrors[field as keyof FormErrors] = messages[0];
            }
          });
          setErrors(apiErrors);
        } else {
          setErrors({ general: response.message || 'Registration failed. Please try again.' });
        }
      }
    } catch {
      setErrors({ general: 'An unexpected error occurred. Please try again.' });
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isSuccess) {
    return (
      <div className="max-w-md mx-auto bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
        <div className="text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 mb-4">
            <svg className="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Registration Successful!
          </h2>
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {successMessage}
          </p>
          <div className="text-center">
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Already have an account?{' '}
              <a href="/login" className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                Sign in
              </a>
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-md mx-auto bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
      <div className="text-center">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Create Account
        </h2>
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Join us today and start your journey
        </p>
      </div>

      {errors.general && (
        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-800 dark:text-red-200">
                {errors.general}
              </p>
            </div>
          </div>
        </div>
      )}

      <FormField
        label="Full Name"
        name="name"
        value={formData.name}
        onChange={handleInputChange}
        error={errors.name}
        placeholder="Enter your full name"
        required
        autoComplete="name"
      />

      <FormField
        label="Email Address"
        name="email"
        type="email"
        value={formData.email}
        onChange={handleInputChange}
        error={errors.email}
        placeholder="Enter your email address"
        required
        autoComplete="email"
      />

      <PasswordInput
        label="Password"
        name="password"
        value={formData.password}
        onChange={handleInputChange}
        error={errors.password}
        placeholder="Create a strong password"
        required
        autoComplete="new-password"
        showStrengthIndicator={true}
      />

      <PasswordInput
        label="Confirm Password"
        name="password_confirmation"
        value={formData.password_confirmation}
        onChange={handleInputChange}
        error={errors.password_confirmation}
        placeholder="Confirm your password"
        required
        autoComplete="new-password"
        showStrengthIndicator={false}
      />

      <button
        type="submit"
        disabled={isSubmitting}
        className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 flex items-center justify-center"
      >
        {isSubmitting ? (
          <>
            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Creating Account...
          </>
        ) : (
          'Create Account'
        )}
      </button>

      <div className="text-center">
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Already have an account?{' '}
          <a href="/login" className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
            Sign in
          </a>
        </p>
      </div>
    </form>
  );
}
