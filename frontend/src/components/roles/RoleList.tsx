'use client';

import React, { useState } from 'react';
import RoleCard from './RoleCard';

interface Role {
  id: string;
  name: string;
  order: number;
}

interface RoleListProps {
  roles: Role[];
  onDeleteRole: (roleId: string) => void;
  onReorderRoles: (roles: Role[]) => void;
  onAddRole: () => void;
}

export default function RoleList({ roles, onDeleteRole, onReorderRoles, onAddRole }: RoleListProps) {
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);

  const handleDragStart = (e: React.DragEvent, index: number) => {
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.currentTarget.outerHTML);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    
    if (draggedIndex === null || draggedIndex === dropIndex) {
      setDraggedIndex(null);
      return;
    }

    const newRoles = [...roles];
    const draggedRole = newRoles[draggedIndex];
    
    // Remove the dragged item
    newRoles.splice(draggedIndex, 1);
    
    // Insert at new position
    newRoles.splice(dropIndex, 0, draggedRole);
    
    // Update order numbers
    const updatedRoles = newRoles.map((role, index) => ({
      ...role,
      order: index + 1,
    }));
    
    onReorderRoles(updatedRoles);
    setDraggedIndex(null);
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
  };

  return (
    <div className="space-y-4">
      {/* Add Role Button */}
      <div className="flex justify-end mb-6">
        <button
          onClick={onAddRole}
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
        >
          <svg 
            className="w-4 h-4 mr-2" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              strokeLinecap="round" 
              strokeLinejoin="round" 
              strokeWidth={2} 
              d="M12 6v6m0 0v6m0-6h6m-6 0H6" 
            />
          </svg>
          Add Role
        </button>
      </div>

      {/* Roles List */}
      <div className="space-y-3">
        {roles.map((role, index) => (
          <div
            key={role.id}
            draggable
            onDragStart={(e) => handleDragStart(e, index)}
            onDragOver={handleDragOver}
            onDrop={(e) => handleDrop(e, index)}
            onDragEnd={handleDragEnd}
            className={`
              transition-all duration-200
              ${draggedIndex === index ? 'opacity-50' : ''}
            `}
          >
            <RoleCard
              role={role}
              onDelete={onDeleteRole}
              isDragging={draggedIndex === index}
            />
          </div>
        ))}
      </div>

      {/* Drag Instructions */}
      <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div className="flex items-start">
          <svg className="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <h4 className="text-sm font-medium text-blue-800 dark:text-blue-200">
              Drag to reorder
            </h4>
            <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
              Use the drag handle (⋮⋮) to reorder your roles.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
