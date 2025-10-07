'use client';

import React, { useState, useEffect } from 'react';
import Header from '@/components/layout/Header';
import RoleList from '@/components/roles/RoleList';
import EmptyState from '@/components/roles/EmptyState';
import AddRoleModal from '@/components/roles/AddRoleModal';
import { getUserRoles, UserRoleItem, removeUserRole, reorderUserRoles, RoleOrderItem } from '@/lib/api';

interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}

interface Role {
  id: string;
  name: string;
  order: number;
}

export default function MyJobRolesPage() {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthLoading, setIsAuthLoading] = useState(true);
  const [isRolesLoading, setIsRolesLoading] = useState(false);
  const [roles, setRoles] = useState<Role[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);

  useEffect(() => {
    // Check if user is logged in
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
      try {
        const parsedUser = JSON.parse(userData);
        setUser(parsedUser);
        
        // Load user's roles from API
        loadUserRoles(parsedUser.id);
      } catch (error) {
        console.error('Error parsing user data:', error);
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
      }
    }
    
    setIsAuthLoading(false);
  }, []);

  const loadUserRoles = async (userId: number, showLoading: boolean = true) => {
    if (showLoading) {
      setIsRolesLoading(true);
      setError(null);
    }
    try {
      const response = await getUserRoles(userId);
      if (response.success && response.data) {
        // Convert API response to local format
        const convertedRoles: Role[] = response.data.roles.map((role: UserRoleItem) => ({
          id: role.role_id.toString(),
          name: role.role_name,
          order: role.display_order || 0,
        }));
        convertedRoles.sort((a, b) => a.order - b.order);
        console.log('Converted roles:', convertedRoles);
        setRoles(convertedRoles);
      }
    } catch (error) {
      console.error('Error loading user roles:', error);
      if (showLoading) {
        setError('Failed to load roles. Please try again.');
      }
    } finally {
      if (showLoading) {
        setIsRolesLoading(false);
      }
    }
  };

  // Redirect to login if not authenticated
  useEffect(() => {
    if (!isAuthLoading && !user) {
      window.location.href = '/login';
    }
  }, [user, isAuthLoading]);

  const handleAddRole = () => {
    // Reload user roles after adding a new one
    if (user) {
      loadUserRoles(user.id);
    }
    setIsAddModalOpen(false);
  };

  const handleDeleteRole = async (roleId: string) => {
    if (!user) return;
    
    console.log('Deleting role:', roleId, 'for user:', user.id);
    
    try {
      const response = await removeUserRole(user.id, parseInt(roleId));
      console.log('Delete response:', response);
      
      if (response.success) {
        console.log('Role deleted successfully, updating local state');
        // Optimistically update the local state immediately
        setRoles(prevRoles => {
          const newRoles = prevRoles.filter(role => role.id !== roleId);
          console.log('Updated roles:', newRoles);
          return newRoles;
        });
        
        // Also reload from API to ensure consistency (without showing loading state)
        console.log('Refreshing roles from API');
        loadUserRoles(user.id, false);
      } else {
        console.error('Failed to delete role:', response.message);
        // Show error to user - you might want to add a toast notification here
      }
    } catch (error) {
      console.error('Error deleting role:', error);
      // Show error to user - you might want to add a toast notification here
    }
  };

  const handleReorderRoles = async (newRoles: Role[]) => {
    if (!user) return;
    
    // Store the original order in case we need to revert
    const originalRoles = [...roles];
    
    // Optimistically update the local state immediately
    setRoles(newRoles);
    
    try {
      // Prepare the role orders for the API
      const roleOrders: RoleOrderItem[] = newRoles.map((role, index) => ({
        role_id: parseInt(role.id),
        display_order: index + 1, // 1-based ordering
      }));
      
      console.log('Reordering roles:', roleOrders);
      
      const response = await reorderUserRoles(user.id, roleOrders);
      
      if (response.success) {
        console.log('Roles reordered successfully:', response.message);
        // Success - the optimistic update was correct
      } else {
        console.error('Failed to reorder roles:', response.message);
        // Revert to original order on failure
        setRoles(originalRoles);
        // You might want to show a toast notification here
      }
    } catch (error) {
      console.error('Error reordering roles:', error);
      // Revert to original order on error
      setRoles(originalRoles);
      // You might want to show a toast notification here
    }
  };

  // Show authentication loading
  if (isAuthLoading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Header />
        <div className="flex items-center justify-center min-h-[400px]">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
      </div>
    );
  }

  // Redirect to login if not authenticated
  if (!user) {
    return null; // Will redirect to login
  }

  // Show roles loading state with skeleton cards
  if (isRolesLoading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Header />
        <main className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              My Roles
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              List your roles to get AI tool recommendations.
            </p>
          </div>

          {/* Loading State with Skeleton Cards */}
          <div className="space-y-3">
            <div className="flex justify-end mb-6">
              <div className="h-10 w-24 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse"></div>
            </div>
            
            {/* Skeleton Cards */}
            {[1, 2, 3].map((i) => (
              <div key={i} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 animate-pulse">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-5 h-5 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    <div className="h-6 bg-gray-300 dark:bg-gray-600 rounded w-48"></div>
                  </div>
                  <div className="w-5 h-5 bg-gray-300 dark:bg-gray-600 rounded"></div>
                </div>
              </div>
            ))}
          </div>
        </main>
      </div>
    );
  }

  // Show error state
  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Header />
        <main className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              My Roles
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
            List your roles to get AI tool recommendations.
            </p>
          </div>

          <div className="text-center py-16">
            <div className="mb-6">
              <div className="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mb-4">
                <svg className="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Failed to load roles
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-6">
                {error}
              </p>
            </div>
            
            <button
              onClick={() => user && loadUserRoles(user.id)}
              className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
            >
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Try Again
            </button>
          </div>
        </main>
      </div>
    );
  }

  // Show content based on roles (empty state is now only shown when we know there are no roles)
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Header />
      
      <main className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            My Roles
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
          List your roles to get AI tool recommendations.
          </p>
        </div>

        {roles.length === 0 ? (
          <EmptyState onAddRole={() => setIsAddModalOpen(true)} />
        ) : (
          <RoleList
            roles={roles}
            onDeleteRole={handleDeleteRole}
            onReorderRoles={handleReorderRoles}
            onAddRole={() => setIsAddModalOpen(true)}
          />
        )}

        <AddRoleModal
          isOpen={isAddModalOpen}
          onClose={() => setIsAddModalOpen(false)}
          onAddRole={handleAddRole}
          userId={user?.id || 0}
        />
      </main>
    </div>
  );
}