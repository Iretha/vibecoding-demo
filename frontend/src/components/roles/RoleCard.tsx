'use client';

import React, { useState } from 'react';

interface Role {
  id: string;
  name: string;
  order: number;
}

interface RoleCardProps {
  role: Role;
  onDelete: (roleId: string) => void;
  isDragging?: boolean;
}

export default function RoleCard({ role, onDelete, isDragging = false }: RoleCardProps) {
  const [isHovered, setIsHovered] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const handleDelete = () => {
    if (showDeleteConfirm) {
      onDelete(role.id);
    } else {
      setShowDeleteConfirm(true);
      // Auto-hide confirmation after 3 seconds
      setTimeout(() => setShowDeleteConfirm(false), 3000);
    }
  };

  return (
    <div
      className={`
        bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4
        transition-all duration-200 ease-in-out
        ${isDragging 
          ? 'opacity-50 scale-105 border-cyan-500 shadow-lg' 
          : 'hover:shadow-lg hover:border-indigo-500'
        }
        ${isHovered ? 'transform -translate-y-1' : ''}
      `}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className="flex items-center justify-between">
        {/* Drag Handle */}
        <div className="flex items-center space-x-3">
          <div className="cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
            <svg 
              className="w-5 h-5" 
              fill="currentColor" 
              viewBox="0 0 24 24"
            >
              <path d="M8 6h2v2H8V6zm0 4h2v2H8v-2zm0 4h2v2H8v-2zm6-8h2v2h-2V6zm0 4h2v2h-2v-2zm0 4h2v2h-2v-2z" />
            </svg>
          </div>
          
          {/* Role Name */}
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            {role.name}
          </h3>
        </div>

        {/* Delete Button */}
        <div className="flex items-center">
          {showDeleteConfirm ? (
            <div className="flex items-center space-x-2">
              <span className="text-sm text-red-600 dark:text-red-400">
                Delete?
              </span>
              <button
                onClick={handleDelete}
                className="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <button
                onClick={() => setShowDeleteConfirm(false)}
                className="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors duration-200"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          ) : (
            <button
              onClick={handleDelete}
              className={`
                p-2 rounded-md transition-all duration-200
                ${isHovered 
                  ? 'text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20' 
                  : 'text-gray-400 hover:text-red-500'
                }
              `}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path 
                  strokeLinecap="round" 
                  strokeLinejoin="round" 
                  strokeWidth={2} 
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" 
                />
              </svg>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
