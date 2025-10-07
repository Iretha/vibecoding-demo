'use client';

import React, { useState } from 'react';
import FormField from './FormField';

interface PasswordInputProps {
  label: string;
  name: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  onBlur?: (e: React.FocusEvent<HTMLInputElement>) => void;
  error?: string;
  placeholder?: string;
  required?: boolean;
  autoComplete?: string;
  showStrengthIndicator?: boolean;
  className?: string;
}

interface PasswordStrength {
  score: number;
  feedback: string[];
  isValid: boolean;
}

function calculatePasswordStrength(password: string): PasswordStrength {
  const feedback: string[] = [];
  let score = 0;

  if (password.length >= 8) {
    score += 1;
  } else {
    feedback.push('At least 8 characters');
  }

  if (/[a-z]/.test(password)) {
    score += 1;
  } else {
    feedback.push('Lowercase letter');
  }

  if (/[A-Z]/.test(password)) {
    score += 1;
  } else {
    feedback.push('Uppercase letter');
  }

  if (/\d/.test(password)) {
    score += 1;
  } else {
    feedback.push('Number');
  }

  if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
    score += 1;
  } else {
    feedback.push('Special character');
  }

  return {
    score,
    feedback,
    isValid: score >= 4 && password.length >= 8,
  };
}

function getStrengthColor(score: number): string {
  if (score <= 1) return 'bg-red-500';
  if (score <= 2) return 'bg-orange-500';
  if (score <= 3) return 'bg-yellow-500';
  if (score <= 4) return 'bg-blue-500';
  return 'bg-green-500';
}

function getStrengthText(score: number): string {
  if (score <= 1) return 'Very Weak';
  if (score <= 2) return 'Weak';
  if (score <= 3) return 'Fair';
  if (score <= 4) return 'Good';
  return 'Strong';
}

export default function PasswordInput({
  label,
  name,
  value,
  onChange,
  onBlur,
  error,
  placeholder,
  required = false,
  autoComplete,
  showStrengthIndicator = true,
  className = '',
}: PasswordInputProps) {
  const [showPassword, setShowPassword] = useState(false);
  const [isFocused, setIsFocused] = useState(false);
  
  const strength = calculatePasswordStrength(value);
  const shouldShowStrength = showStrengthIndicator && value.length > 0 && isFocused;

  const handleFocus = () => setIsFocused(true);
  const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
    setIsFocused(false);
    onBlur?.(e);
  };

  const togglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  return (
    <div className={`space-y-2 ${className}`}>
      <FormField
        label={label}
        name={name}
        value={value}
        onChange={onChange}
        onBlur={handleBlur}
        error={error}
        placeholder={placeholder}
        required={required}
        autoComplete={autoComplete}
      >
        <div className="relative">
          <input
            id={`field-${name}`}
            type={showPassword ? 'text' : 'password'}
            name={name}
            value={value}
            onChange={onChange}
            onFocus={handleFocus}
            onBlur={handleBlur}
            placeholder={placeholder}
            required={required}
            autoComplete={autoComplete}
            aria-invalid={error ? 'true' : 'false'}
            aria-describedby={error ? `error-${name}` : undefined}
            className={`
              w-full px-3 py-2 pr-10 border rounded-md shadow-sm placeholder-gray-400
              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
              transition-colors duration-200
              ${error 
                ? 'border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20' 
                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'
              }
              dark:text-white dark:placeholder-gray-500
            `}
          />
          <button
            type="button"
            onClick={togglePasswordVisibility}
            className="absolute inset-y-0 right-0 pr-3 flex items-center"
            aria-label={showPassword ? 'Hide password' : 'Show password'}
          >
            {showPassword ? (
              <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
              </svg>
            ) : (
              <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            )}
          </button>
        </div>
      </FormField>

      {shouldShowStrength && (
        <div className="space-y-2">
          <div className="flex items-center space-x-2">
            <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                className={`h-2 rounded-full transition-all duration-300 ${getStrengthColor(strength.score)}`}
                style={{ width: `${(strength.score / 5) * 100}%` }}
              />
            </div>
            <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
              {getStrengthText(strength.score)}
            </span>
          </div>
          
          {strength.feedback.length > 0 && (
            <div className="text-xs text-gray-600 dark:text-gray-400">
              <p className="font-medium mb-1">Password must contain:</p>
              <ul className="space-y-1">
                {strength.feedback.map((item, index) => (
                  <li key={index} className="flex items-center">
                    <svg className="h-3 w-3 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                    </svg>
                    {item}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
