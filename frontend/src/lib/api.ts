// API client for Laravel backend integration
const API_BASE_URL = 'http://localhost:8201/api';

// Types for API requests and responses
export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  remember?: boolean;
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

export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
    token: string;
    expires_at: string;
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

  // Add authentication header if token exists
  const token = localStorage.getItem('auth_token');
  if (token) {
    defaultHeaders['Authorization'] = `Bearer ${token}`;
  }

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

// Login API function
export async function loginUser(credentials: LoginRequest): Promise<ApiResponse<{ user: User; token: string; expires_at: string }>> {
  return apiRequest<{ user: User; token: string; expires_at: string }>('/v1/auth/login', {
    method: 'POST',
    body: JSON.stringify(credentials),
  });
}

// Health check function (for testing API connectivity)
export async function checkApiHealth(): Promise<ApiResponse<{ status: string; services: Record<string, string> }>> {
  return apiRequest<{ status: string; services: Record<string, string> }>('/health', {
    method: 'GET',
  });
}

// Job Roles API functions
export interface Role {
  role_id: number;
  role_name: string;
  user_count?: number;
}

export interface AddSingleRoleRequest {
  role_name: string;
}

export interface AddRolesResponse {
  added: Array<{
    role_id: number;
    role_name: string;
  }>;
  skipped: Array<{
    role_id: number;
    role_name: string;
    reason: string;
  }>;
}

export interface UserRoleItem {
  role_id: number;
  role_name: string;
  display_order: number | null;
  assigned_at: string;
}

export interface UserRolesResponse {
  roles: UserRoleItem[];
  total: number;
  page: number;
  limit: number;
}

// Get all available roles with search
export async function getAvailableRoles(search?: string, limit: number = 50): Promise<ApiResponse<{ roles: Role[]; total: number }>> {
  const params = new URLSearchParams();
  if (search) params.append('search', search);
  params.append('limit', limit.toString());
  
  return apiRequest<{ roles: Role[]; total: number }>(`/v1/roles?${params.toString()}`, {
    method: 'GET',
  });
}

// Add single role to user
export async function addUserRole(userId: number, roleName: string): Promise<ApiResponse<AddRolesResponse>> {
  return apiRequest<AddRolesResponse>(`/v1/users/${userId}/job-roles/single`, {
    method: 'POST',
    body: JSON.stringify({ role_name: roleName }),
  });
}

// Get user's roles
export async function getUserRoles(userId: number, page: number = 1, limit: number = 20): Promise<ApiResponse<UserRolesResponse>> {
  return apiRequest<UserRolesResponse>(`/v1/users/${userId}/job-roles?page=${page}&limit=${limit}`, {
    method: 'GET',
  });
}

// Remove role from user
export async function removeUserRole(userId: number, roleId: number): Promise<ApiResponse<null>> {
  return apiRequest<null>(`/v1/users/${userId}/job-roles/${roleId}`, {
    method: 'DELETE',
  });
}

// Reorder user's roles
export interface RoleOrderItem {
  role_id: number;
  display_order: number;
}

export interface ReorderRolesRequest {
  role_orders: RoleOrderItem[];
}

export interface ReorderRolesResponse {
  success: boolean;
  message: string;
}

export async function reorderUserRoles(userId: number, roleOrders: RoleOrderItem[]): Promise<ApiResponse<ReorderRolesResponse>> {
  return apiRequest<ReorderRolesResponse>(`/v1/users/${userId}/job-roles/reorder`, {
    method: 'PUT',
    body: JSON.stringify({ role_orders: roleOrders }),
  });
}
