# User Management Feature

## Overview
Admin-only user management system with comprehensive CRUD operations, account locking/unlocking, and password reset capabilities.

## Features

### 1. **User Listing & Filtering**
- View all users with their status and roles
- Filter by:
  - Search (username, email, fullname)
  - Role (admin, user)
  - Status (active, inactive)
- Display lock status with countdown timer
- Show failed login attempt count

### 2. **Create User**
- Username (unique)
- Full name
- Email (unique, validated)
- Phone (auto-format to +62)
- Password (minimum 8 characters)
- Role selection (admin/user)
- Auto-activate new users

### 3. **Edit User**
- Update full name
- Update phone number
- Change role
- Toggle active/inactive status

### 4. **Unlock Account**
- One-click unlock for locked accounts
- Clear failed login attempts
- Restore immediate access

### 5. **Reset Password**
- Admin sets new password directly
- Minimum 8 characters
- No email verification needed (direct access)

### 6. **Deactivate User (Soft Delete)**
- Set user inactive
- Revoke all active tokens
- Prevent self-deletion
- Data retention (no hard delete)

---

## API Endpoints

### Base URL
```
/api/admin/users
```

All endpoints require:
- `Authorization: Bearer <token>`
- Admin role

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/users` | List all users with filters |
| GET | `/api/admin/users/{id}` | Get user detail |
| POST | `/api/admin/users` | Create new user |
| PUT | `/api/admin/users/{id}` | Update user |
| DELETE | `/api/admin/users/{id}` | Deactivate user |
| POST | `/api/admin/users/{id}/unlock` | Unlock account |
| POST | `/api/admin/users/{id}/reset-password` | Force password reset |

---

## Request Examples

### List Users with Filters
```http
GET /api/admin/users?search=john&role=admin&status=active
Authorization: Bearer <token>
```

**Response:**
```json
[
  {
    "id": 1,
    "username": "john_admin",
    "email": "john@example.com",
    "fullname": "John Doe",
    "phone": "+628123456789",
    "active": 1,
    "roles": "admin",
    "is_locked": false,
    "lockout_remaining": 0,
    "attempt_count": 0,
    "created_at": "2026-09-15 10:00:00",
    "updated_at": "2026-09-16 08:30:00"
  }
]
```

### Create User
```http
POST /api/admin/users
Authorization: Bearer <token>
Content-Type: application/json

{
  "username": "jane_doe",
  "fullname": "Jane Doe",
  "email": "jane@example.com",
  "phone": "081234567890",
  "password": "SecurePass123",
  "role": "user"
}
```

**Response:**
```json
{
  "message": "User created successfully",
  "user_id": 5
}
```

### Update User
```http
PUT /api/admin/users/5
Authorization: Bearer <token>
Content-Type: application/json

{
  "fullname": "Jane Smith",
  "phone": "+628129876543",
  "role": "admin",
  "active": 1
}
```

**Response:**
```json
{
  "message": "User updated successfully"
}
```

### Unlock Account
```http
POST /api/admin/users/5/unlock
Authorization: Bearer <token>
```

**Response:**
```json
{
  "message": "Account unlocked successfully"
}
```

### Reset Password
```http
POST /api/admin/users/5/reset-password
Authorization: Bearer <token>
Content-Type: application/json

{
  "new_password": "NewSecure123"
}
```

**Response:**
```json
{
  "message": "Password reset successfully"
}
```

### Deactivate User
```http
DELETE /api/admin/users/5
Authorization: Bearer <token>
```

**Response:**
```json
{
  "message": "User deactivated successfully"
}
```

---

## Frontend Usage

### Navigation
`Admin Sidebar > User Management`

### User Interface

**Toolbar:**
- Search box (username/email/fullname)
- Role filter dropdown
- Status filter dropdown
- Apply button
- Add User button

**User Table:**
- ID, Username, Email, Full Name
- Roles (colored chips)
- Status (Active/Inactive)
- Lock Status (with countdown if locked)
- Actions (Edit, Unlock, Reset Password, Deactivate)

**Modals:**
- Create User (form with validation)
- Edit User (update fields)
- Deactivate User (confirmation)
- Reset Password (password input)

---

## Security Features

### Access Control
- Admin role required for all operations
- Token validation on every request
- Prevent self-deletion

### Lock Status Integration
- Display current lock status
- Show remaining lockout time
- Show failed attempt count
- One-click unlock by admin

### Password Security
- Minimum 8 characters
- Hashed with `password_hash()` (bcrypt)
- Admin cannot see existing passwords
- Force reset creates new password immediately

### Audit Logging
All operations are logged:
- User creation: `Admin created new user: {email} with role: {role}`
- User update: `Admin updated user ID: {id}`
- Account unlock: `Admin unlocked account: {email}`
- Password reset: `Admin reset password for user: {email}`
- User deactivation: `Admin deactivated user: {email}`

---

## Deployment Notes

### Database Requirements
- Existing `users` table (Myth:Auth)
- Existing `auth_groups` and `auth_groups_users` tables
- Existing `api_tokens` table
- No new migrations needed

### Dependencies
- Backend: CodeIgniter 4, Myth:Auth
- Frontend: React, Material-UI, lucide-react

### Branch
- `security/auth-hardening` (both backend and frontend)

### VPS Deployment
Pull latest from `security/auth-hardening` branch:

```bash
# Backend
cd /var/www/marine-backend
git fetch origin
git checkout security/auth-hardening
git pull origin security/auth-hardening

# Frontend
cd /var/www/marine-frontend
git fetch origin
git checkout security/auth-hardening
git pull origin security/auth-hardening
npm run build
```

---

## Integration with Rate Limiting & Login Tracking

User Management integrates with existing security features:

1. **Rate Limiting** (`RateLimitFilter`)
   - Applied to login/register endpoints
   - 10 requests per minute per IP

2. **Login Attempt Tracker** (`LoginAttemptTracker`)
   - Tracks failed login attempts
   - 5 failures = 15 minute lockout
   - Admin can unlock via User Management UI

3. **Session Security**
   - Token-based authentication
   - Tokens revoked on user deactivation
   - Admin role required for management operations

---

## Testing Checklist

- [ ] List users (empty, populated, filtered)
- [ ] Create user (valid, duplicate email, duplicate username, weak password)
- [ ] Edit user (name, phone, role, status)
- [ ] Deactivate user (confirmation, self-deletion prevention)
- [ ] Unlock account (locked user, unlocked user)
- [ ] Reset password (valid, weak password)
- [ ] Filter by search term
- [ ] Filter by role
- [ ] Filter by status
- [ ] Lock status display accuracy
- [ ] Failed attempt count display
- [ ] Admin-only access enforcement

---

## Future Enhancements (Optional)

1. Email notification on password reset
2. Activity log viewer per user
3. Bulk operations (bulk unlock, bulk deactivate)
4. Password strength indicator
5. User import from CSV
6. Two-factor authentication management
7. Session management (view/revoke active sessions)
8. User activity dashboard
9. Role-based permission granularity (beyond admin/user)
10. User profile picture upload

---

## Support

For issues or questions:
1. Check logs in `writable/logs/`
2. Verify admin role in `auth_groups_users` table
3. Confirm rate limiting is not blocking requests
4. Check CORS configuration in `backend/app/Config/Cors.php`
