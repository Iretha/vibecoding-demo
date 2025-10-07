'use client';

import React from 'react';

interface FormFieldProps {
  label: string;
  type?: string;
  name: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  onBlur?: (e: React.FocusEvent<HTMLInputElement>) => void;
  error?: string;
  placeholder?: string;
  required?: boolean;
  autoComplete?: string;
  className?: string;
  children?: React.ReactNode;
}

export default function FormField({
  label,
  type = 'text',
  name,
  value,
  onChange,
  onBlur,
  error,
  placeholder,
  required = false,
  autoComplete,
  className = '',
  children,
}: FormFieldProps) {
  const fieldId = `field-${name}`;
  const errorId = `error-${name}`;

  return (
    <div className={`space-y-2 ${className}`}>
      <label
        htmlFor={fieldId}
        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>
      
      {children || (
        <input
          id={fieldId}
          type={type}
          name={name}
          value={value}
          onChange={onChange}
          onBlur={onBlur}
          placeholder={placeholder}
          required={required}
          autoComplete={autoComplete}
          aria-invalid={error ? 'true' : 'false'}
          aria-describedby={error ? errorId : undefined}
          className={`
            w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400
            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
            transition-colors duration-200
            ${error 
              ? 'border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20' 
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'
            }
            dark:text-white dark:placeholder-gray-500
          `}
        />
      )}
      
      {error && (
        <p
          id={errorId}
          className="text-sm text-red-600 dark:text-red-400"
          role="alert"
        >
          {error}
        </p>
      )}
    </div>
  );
}
