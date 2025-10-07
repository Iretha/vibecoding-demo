# Backend Implementation Prompt: User Job Roles Management

## Context
Implement a complete job roles management system that allows users to manage multiple job roles associated with their account. Roles are stored in a normalized structure where a single role can be shared across multiple users.

## Requirements

### Database Schema
Create the following normalized database structure:

#### 1. **roles** table (master list of all roles)
- `id`: Primary key (auto-increment)
- `role_name`: Unique string field for the job role name
- `created_at`: Timestamp for when the role was first created
- `updated_at`: Timestamp for last modification
- Add **unique constraint** on `role_name` to ensure no duplicate roles exist
- Add index on `role_name` for faster lookups

#### 2. **user_roles** table (junction/association table)
- `id`: Primary key (auto-increment)
- `user_id`: Foreign key referencing the users table
- `role_id`: Foreign key referencing the roles table
- `created_at`: Timestamp for when the user was assigned this role
- Add **unique constraint** on `(user_id, role_id)` to prevent duplicate assignments
- Add composite index on `(user_id, role_id)` for query performance
- Add index on `user_id` for listing user's roles
- Add index on `role_id` for finding users with a specific role

**Relationship**: Many-to-Many between Users and Roles

### API Endpoints

#### 1. Add Job Roles (Single or Multiple)
- **Endpoint**: `POST /api/v1/users/{userId}/job-roles`
- **Request Body**:
  ```json
  {
    "roles": ["Software Engineer", "Product Manager"]
  }
  ```
- **Logic**:
  1. For each role name in the request:
     - Check if the role exists in the `roles` table
     - If exists: Get the role ID
     - If not exists: Create a new role and get its ID
  2. Create associations in the `user_roles` table
  3. Handle duplicates gracefully (skip if user already has the role)
- **Response** (201): 
  ```json
  {
    "added": [
      {
        "role_id": "456",
        "role_name": "Software Engineer"
      }
    ],
    "skipped": [
      {
        "role_id": "789",
        "role_name": "Product Manager",
        "reason": "User already has this role"
      }
    ]
  }
  ```

#### 2. List User's Job Roles
- **Endpoint**: `GET /api/v1/users/{userId}/job-roles`
- **Query Parameters** (optional):
  - `page`: For pagination (default: 1)
  - `limit`: Number of results per page (default: 20)
  - `sort`: Sort order (e.g., by role_name, created_at)
- **Logic**: 
  - Join `user_roles` with `roles` table
  - Filter by `user_id`
- **Response**: 
  ```json
  {
    "roles": [
      {
        "role_id": "456",
        "role_name": "Software Engineer",
        "assigned_at": "2025-01-15T10:30:00Z"
      },
      {
        "role_id": "789",
        "role_name": "Product Manager",
        "assigned_at": "2025-02-20T14:15:00Z"
      }
    ],
    "total": 2,
    "page": 1,
    "limit": 20
  }
  ```

#### 3. Remove a Job Role from User
- **Endpoint**: `DELETE /api/v1/users/{userId}/job-roles/{roleId}`
- **Logic**: 
  - Delete the association from `user_roles` table
  - **Do NOT delete** from the `roles` table (keep roles for other users)
- **Response**: 
  - Success (204): No content
  - Not Found (404): If association doesn't exist
- **Security**: Verify the association belongs to the authenticated user

#### 4. Add Single Job Role (Alternative)
- **Endpoint**: `POST /api/v1/users/{userId}/job-roles/single`
- **Request Body**:
  ```json
  {
    "role_name": "Data Analyst"
  }
  ```
- **Logic**: Same as bulk endpoint but for a single role

#### 5. List All Available Roles (Optional but Recommended)
- **Endpoint**: `GET /api/v1/roles`
- **Purpose**: Allow frontend to show autocomplete/dropdown of existing roles
- **Query Parameters**:
  - `search`: Filter roles by name (partial match)
  - `limit`: Number of results
- **Response**:
  ```json
  {
    "roles": [
      {
        "role_id": "456",
        "role_name": "Software Engineer",
        "user_count": 150
      }
    ],
    "total": 250
  }
  ```

### Implementation Guidelines

1. **Authentication & Authorization**
   - Ensure all endpoints require authentication
   - Verify that users can only manage their own job roles
   - Use middleware to validate JWT/session tokens

2. **Error Handling**
   - Return appropriate HTTP status codes
   - Provide clear error messages
   - Handle database connection errors gracefully
   - Handle concurrent requests properly (race conditions when creating roles)

3. **Data Validation**
   - Validate `userId` format
   - Sanitize role names to prevent SQL injection
   - Limit role name length (max 250 characters)
   - Reject empty or whitespace-only role names
   - Normalize role names (trim whitespace, consistent casing)

4. **Business Logic**
   - **Role Creation**: Use "upsert" or "find or create" pattern
   - **Prevent duplicates**: Check if user-role association exists before creating
   - **Case sensitivity**: "Software Engineer" and "software engineer" are the same
   - Adding a maximum limit on roles per user (e.g., 20 roles)

5. **Database Transactions**
   - Use transactions when adding multiple roles to ensure atomicity
   - Example flow:
     ```
     BEGIN TRANSACTION
       1. Find or create role in roles table
       2. Create association in user_roles table
     COMMIT
     ```

6. **Performance Considerations**
   - Use database indexes effectively
   - Consider caching frequently accessed roles
   - Batch operations when possible
   - Use JOIN queries efficiently to avoid N+1 problems

7. **Testing Requirements**
   - Unit tests for all endpoint handlers
   - Integration tests for database operations
   - Test concurrent role creation by multiple users
   - Test edge cases:
     - Adding duplicate roles to the same user
     - Removing non-existent associations
     - Concurrent creation of the same role
     - Case sensitivity handling

8. **Code Organization**
   - Create dedicated controllers: `UserRolesController`, `RolesController`
   - Service layer: `UserRolesService`, `RolesService`
   - Repository layer: `RolesRepository`, `UserRolesRepository`
   - Add appropriate logging for debugging

### Technology Stack Considerations
- Follow the existing project's architecture patterns
- Use the current ORM/database library
- Implement proper foreign key constraints
- Use ORM features for "findOrCreate" operations
- Maintain consistency with existing error handling patterns

### Additional Features
1. **Search and Filter**
   - Search roles by name
   - Filter users by role
   - Get all users with a specific role

## Database Migration Example (Pseudocode)

```sql
-- Create roles table
CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    role_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_roles junction table
CREATE TABLE user_roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id UUID NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, role_id)
);

-- Create indexes
CREATE INDEX idx_roles_name ON roles(role_name);
CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX idx_user_roles_composite ON user_roles(user_id, role_id);
```

## Deliverables
1. Database migration files for both tables
2. Model/Entity definitions for `Role` and `UserRole`
3. Controllers with all endpoints
4. Service layer with business logic (including find-or-create pattern)
5. Repository/DAO layer for database operations
6. API documentation (OpenAPI/Swagger)
7. Unit and integration tests
8. Update any relevant middleware or authentication logic

## Notes
- This normalized structure prevents data duplication and ensures consistency
- Multiple users can share the same role without storing duplicate role names
- Easier to maintain and update role names globally
- Consider future features like role hierarchies or permissions
- Log all role modifications for audit purposes
- Return consistent response formats across all endpoints
- Consider implementing role suggestions based on popular roles


## Implementation Approach:
Follow existing conventions for consistency
Use auto-incrementing IDs to match current schema
Implement proper service layer (currently missing but needed)
Add comprehensive logging for audit purposes
Use existing middleware stack for security