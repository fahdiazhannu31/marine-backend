<?php

namespace App\Controllers;

use App\Models\ApiTokenModel;
use Myth\Auth\Models\UserModel;
use Myth\Auth\Entities\User;

/**
 * User Management Controller
 * 
 * Admin-only endpoints for managing users, roles, and account status.
 * Requires admin role to access.
 */
class UserManagementController extends BaseController
{
    private function isAdmin(): bool
    {
        $token = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($token, 'Bearer ')) {
            return false;
        }

        $rawToken = substr($token, 7);
        $tokenModel = new ApiTokenModel();
        $tokenRow = $tokenModel->findValid($rawToken);

        if (!$tokenRow) {
            return false;
        }

        $userId = $tokenRow['user_id'];
        
        // Check if user has admin role directly from database
        $db = \Config\Database::connect();
        $role = $db->table('auth_groups_users agu')
            ->select('ag.name')
            ->join('auth_groups ag', 'ag.id = agu.group_id')
            ->where('agu.user_id', $userId)
            ->get()
            ->getRow();
        
        return $role && $role->name === 'admin';
    }

    private function jsonResponse(array $data, int $status = 200)
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON($data);
    }

    // ═══════════════════════════════════════════
    // OPTIONS - CORS Preflight
    // ═══════════════════════════════════════════
    public function options()
    {
        return $this->response->setStatusCode(200);
    }

    // ═══════════════════════════════════════════
    // GET /api/admin/users - List all users
    // ═══════════════════════════════════════════
    public function index()
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $search = $this->request->getVar('search');
        $role = $this->request->getVar('role');
        $status = $this->request->getVar('status');

        $userModel = new UserModel();
        $db = \Config\Database::connect();

        // Build query
        $builder = $db->table('users u')
            ->select('u.*, GROUP_CONCAT(ag.name) as roles')
            ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
            ->join('auth_groups ag', 'ag.id = agu.group_id', 'left')
            ->groupBy('u.id')
            ->orderBy('u.id', 'DESC');

        // Apply filters
        if ($search) {
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('u.email', $search)
                ->orLike('u.fullname', $search)
                ->groupEnd();
        }

        if ($role) {
            $builder->like('ag.name', $role);
        }

        if ($status === 'active') {
            $builder->where('u.active', 1);
        } elseif ($status === 'inactive') {
            $builder->where('u.active', 0);
        }

        $users = $builder->get()->getResultArray();

        // Check lock status for each user
        $attemptTracker = new \App\Libraries\LoginAttemptTracker();
        
        foreach ($users as &$user) {
            $user['is_locked'] = $attemptTracker->isLocked($user['email']);
            $user['lockout_remaining'] = $attemptTracker->getRemainingLockoutTime($user['email']);
            $user['attempt_count'] = $attemptTracker->getAttemptCount($user['email']);
            
            // Remove sensitive data
            unset($user['password_hash']);
            unset($user['reset_hash']);
            unset($user['reset_at']);
            unset($user['reset_expires']);
        }

        return $this->jsonResponse($users);
    }

    // ═══════════════════════════════════════════
    // GET /api/admin/users/{id} - Get user detail
    // ═══════════════════════════════════════════
    public function show(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }

        $userEntity = new \Myth\Auth\Entities\User((array)$user);
        $roles = $userEntity->getRoles();

        // Remove sensitive data
        $userArray = (array)$user;
        unset($userArray['password_hash']);
        unset($userArray['reset_hash']);
        unset($userArray['reset_at']);
        unset($userArray['reset_expires']);

        $userArray['roles'] = $roles;

        // Lock status
        $attemptTracker = new \App\Libraries\LoginAttemptTracker();
        $userArray['is_locked'] = $attemptTracker->isLocked($user->email);
        $userArray['lockout_remaining'] = $attemptTracker->getRemainingLockoutTime($user->email);

        return $this->jsonResponse($userArray);
    }

    // ═══════════════════════════════════════════
    // POST /api/admin/users - Create new user
    // ═══════════════════════════════════════════
    public function create()
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $body = $this->request->getJSON(true) ?? [];

        $username = trim($body['username'] ?? '');
        $fullname = trim($body['fullname'] ?? '');
        $email = trim($body['email'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'user';

        // Validation
        if (!$username || !$fullname || !$email || !$password) {
            return $this->jsonResponse(['error' => 'All fields are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse(['error' => 'Invalid email'], 400);
        }

        if (strlen($password) < 8) {
            return $this->jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
        }

        $userModel = new UserModel();

        // Check uniqueness
        if ($userModel->where('email', $email)->first()) {
            return $this->jsonResponse(['error' => 'Email already exists'], 409);
        }
        if ($userModel->where('username', $username)->first()) {
            return $this->jsonResponse(['error' => 'Username already exists'], 409);
        }

        // Normalize phone
        if ($phone && !str_starts_with($phone, '+62')) {
            $phone = str_starts_with($phone, '0') ? '+62' . substr($phone, 1) : '+62' . $phone;
        }

        // Create user
        $nameParts = explode(' ', $fullname);
        $givenNames = $nameParts[0];
        $surname = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '-';

        $user = new User([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'pass_confirm' => $password,
            'fullname' => $fullname,
            'phone' => $phone,
            'given_names' => $givenNames,
            'surname' => $surname,
        ]);
        $user->activate();

        // Set role
        $userModel = $userModel->withGroup($role);

        if (!$userModel->save($user)) {
            return $this->jsonResponse(['error' => implode(', ', $userModel->errors())], 422);
        }

        log_message('info', "Admin created new user: {$email} with role: {$role}");

        return $this->jsonResponse(['message' => 'User created successfully', 'user_id' => $userModel->getInsertID()], 201);
    }

    // ═══════════════════════════════════════════
    // PUT /api/admin/users/{id} - Update user
    // ═══════════════════════════════════════════
    public function update(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }

        $body = $this->request->getJSON(true) ?? [];

        $updateData = [];

        if (isset($body['fullname'])) {
            $updateData['fullname'] = trim($body['fullname']);
        }
        if (isset($body['phone'])) {
            $phone = trim($body['phone']);
            if ($phone && !str_starts_with($phone, '+62')) {
                $phone = str_starts_with($phone, '0') ? '+62' . substr($phone, 1) : '+62' . $phone;
            }
            $updateData['phone'] = $phone;
        }
        if (isset($body['active'])) {
            $updateData['active'] = (int)$body['active'];
        }

        if (!empty($updateData)) {
            $userModel->update($id, $updateData);
        }

        // Update role if provided
        if (isset($body['role'])) {
            $db = \Config\Database::connect();
            
            // Remove existing roles
            $db->table('auth_groups_users')->where('user_id', $id)->delete();
            
            // Add new role
            $groupId = $db->table('auth_groups')->where('name', $body['role'])->get()->getRow()->id ?? null;
            if ($groupId) {
                $db->table('auth_groups_users')->insert(['group_id' => $groupId, 'user_id' => $id]);
            }
        }

        log_message('info', "Admin updated user ID: {$id}");

        return $this->jsonResponse(['message' => 'User updated successfully']);
    }

    // ═══════════════════════════════════════════
    // POST /api/admin/users/{id}/unlock - Unlock account
    // ═══════════════════════════════════════════
    public function unlock(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }

        $attemptTracker = new \App\Libraries\LoginAttemptTracker();
        $attemptTracker->clearAttempts($user->email);

        log_message('info', "Admin unlocked account: {$user->email}");

        return $this->jsonResponse(['message' => 'Account unlocked successfully']);
    }

    // ═══════════════════════════════════════════
    // POST /api/admin/users/{id}/reset-password
    // ═══════════════════════════════════════════
    public function resetPassword(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }

        $body = $this->request->getJSON(true) ?? [];
        $newPassword = $body['new_password'] ?? '';

        if (strlen($newPassword) < 8) {
            return $this->jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
        }

        // Hash password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $userModel->update($id, ['password_hash' => $hashedPassword]);

        log_message('warning', "Admin reset password for user: {$user->email}");

        return $this->jsonResponse(['message' => 'Password reset successfully']);
    }

    // ═══════════════════════════════════════════
    // DELETE /api/admin/users/{id} - Deactivate user
    // ═══════════════════════════════════════════
    public function delete(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        // Prevent self-deletion
        $token = substr($this->request->getHeaderLine('Authorization'), 7);
        $tokenModel = new ApiTokenModel();
        $tokenRow = $tokenModel->findValid($token);
        
        if ($tokenRow && $tokenRow['user_id'] == $id) {
            return $this->jsonResponse(['error' => 'Cannot delete your own account'], 400);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->jsonResponse(['error' => 'User not found'], 404);
        }

        // Soft delete - set active = 0
        $userModel->update($id, ['active' => 0]);

        // Revoke tokens
        $tokenModel->revokeFor($id);

        log_message('warning', "Admin deactivated user: {$user->email}");

        return $this->jsonResponse(['message' => 'User deactivated successfully']);
    }
}
