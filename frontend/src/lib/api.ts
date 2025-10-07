// API client for Laravel backend integration
const API_BASE_URL = 'http://localhost:8201/api';

// Types for API requests and responses
export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface RegisterResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
  };
}

export interface ApiError {
  success: false;
  message: string;
  errors?: {
    [field: string]: string[];
  };
  error_code?: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: {
    [field: string]: string[];
  };
  error_code?: string;
}

// Generic API client function
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  const config: RequestInit = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  };

  try {
    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      // Handle API errors
      return {
        success: false,
        message: data.message || 'An error occurred',
        errors: data.errors,
        error_code: data.error_code,
      };
    }

    return {
      success: true,
      data: data.data,
      message: data.message,
    };
  } catch {
    // Handle network errors
    return {
      success: false,
      message: 'Network error. Please check your connection and try again.',
    };
  }
}

// Registration API function
export async function registerUser(userData: RegisterRequest): Promise<ApiResponse<{ user: User }>> {
  return apiRequest<{ user: User }>('/v1/auth/register', {
    method: 'POST',
    body: JSON.stringify(userData),
  });
}

// Health check function (for testing API connectivity)
export async function checkApiHealth(): Promise<ApiResponse<{ status: string; services: Record<string, string> }>> {
  return apiRequest<{ status: string; services: Record<string, string> }>('/health', {
    method: 'GET',
  });
}
