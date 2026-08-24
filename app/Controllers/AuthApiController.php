<?php

namespace App\Controllers;

use App\Models\ApiTokenModel;
use Myth\Auth\Models\UserModel;
use Myth\Auth\Entities\User;
use Myth\Auth\Config\Auth as AuthConfig;

/**
 * AuthApiController
 *
 * Token-based auth endpoints for the React frontend.
 * Uses myth/auth for credential validation but issues a Bearer token
 * so that React can stay stateless (no PHP session cookies needed).
 *
 * Routes (add to Routes.php):
 *   POST /api/auth/login
 *   POST /api/auth/register
 *   POST /api/auth/logout      (requires Bearer token)
 *   GET  /api/auth/me          (requires Bearer token)
 */
class AuthApiController extends BaseController
{
    private const ALLOWED_ORIGINS = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:5174',
        'https://namamarine.cloud',
        'http://namamarine.cloud',
        'https://www.namamarine.cloud',
    ];

    private function getAllowedOrigin(): string
    {
        $origin = $this->request->getHeaderLine('Origin');
        return in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : self::ALLOWED_ORIGINS[0];
    }

    private function json(array $data, int $status = 200)
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin())
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setJSON($data);
    }

    private function error(string $message, int $status = 400): \CodeIgniter\HTTP\Response
    {
        return $this->json(['error' => $message], $status);
    }

    public function options()
    {
        $origin = $this->request->getHeaderLine('Origin');
        $allowed = in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : self::ALLOWED_ORIGINS[0];

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Access-Control-Allow-Origin', $allowed)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setBody('');
    }

    /** Extract Bearer token from Authorization header. */
    private function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    /** Resolve authenticated user from Bearer token or return null. */
    private function authUser(): ?array
    {
        $raw = $this->bearerToken();
        if (!$raw) return null;

        $tokenModel = new ApiTokenModel();
        $row = $tokenModel->findValid($raw);
        if (!$row) return null;

        $userModel = new UserModel();
        $user = $userModel->find($row['user_id']);
        return $user ? (array) $user : null;
    }

    // ── POST /api/auth/login ──────────────────────────────────────

    public function login()
    {
        $email    = trim($this->request->getJSON(true)['email']    ?? '');
        $password = trim($this->request->getJSON(true)['password'] ?? '');

        if (!$email || !$password) {
            return $this->error('Email and password are required.');
        }

        /** @var \Myth\Auth\Authentication\LocalAuthenticator $auth */
        $auth = service('authentication');

        if (!$auth->attempt(['email' => $email, 'password' => $password])) {
            return $this->error('Invalid email or password.', 401);
        }

        $user       = $auth->user();
        $tokenModel = new ApiTokenModel();
        $rawToken   = $tokenModel->generateFor((int) $user->id);

        // Determine role
        $groups = $user->getRoles();
        $role   = in_array('admin', $groups) ? 'admin' : 'users';

        return $this->json([
            'token' => $rawToken,
            'user'  => [
                'id'       => (int) $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'fullname' => $user->fullname ?? '',
                'phone'    => $user->phone    ?? '',
                'role'     => $role,
            ],
        ]);
    }

    // ── POST /api/auth/register ───────────────────────────────────

    public function register()
    {
        $body = $this->request->getJSON(true) ?? [];

        $username       = trim($body['username']         ?? '');
        $fullname       = trim($body['fullname']         ?? '');
        $email          = trim($body['email']            ?? '');
        $phone          = trim($body['phone']            ?? '');
        $password       = $body['password']              ?? '';
        $passConfirm    = $body['password_confirm']      ?? '';

        // Basic validation
        if (!$username || !$fullname || !$email || !$phone || !$password) {
            return $this->error('All fields are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Invalid email address.');
        }
        if ($password !== $passConfirm) {
            return $this->error('Passwords do not match.');
        }
        if (strlen($password) < 8) {
            return $this->error('Password must be at least 8 characters.');
        }

        $userModel = new UserModel();

        // Check uniqueness
        if ($userModel->where('email', $email)->first()) {
            return $this->error('Email is already registered.', 409);
        }
        if ($userModel->where('username', $username)->first()) {
            return $this->error('Username is already taken.', 409);
        }

        // Normalise phone to +62
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+62')) {
            $phone = '+62' . $phone;
        }

        // Split fullname
        $nameParts   = explode(' ', $fullname);
        $givenNames  = $nameParts[0];
        $surname     = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '-';

        // Build user entity
        $user = new User([
            'username'    => $username,
            'email'       => $email,
            'password'    => $password,
            'pass_confirm'=> $password,
            'fullname'    => $fullname,
            'phone'       => $phone,
            'given_names' => $givenNames,
            'surname'     => $surname,
        ]);
        $user->activate(); // no email activation required

        $config = config('Auth');
        if (!empty($config->defaultUserGroup)) {
            $userModel = $userModel->withGroup($config->defaultUserGroup);
        }

        if (!$userModel->save($user)) {
            return $this->error(implode(', ', $userModel->errors()), 422);
        }

        return $this->json(['message' => 'Registration successful. You can now log in.'], 201);
    }

    // ── POST /api/auth/logout ─────────────────────────────────────

    public function logout()
    {
        $raw = $this->bearerToken();
        if ($raw) {
            $tokenModel = new ApiTokenModel();
            $row = $tokenModel->findValid($raw);
            if ($row) {
                $tokenModel->revokeFor((int) $row['user_id']);
            }
        }
        return $this->json(['message' => 'Logged out successfully.']);
    }

    // ── GET /api/auth/me ──────────────────────────────────────────

    public function me()
    {
        $user = $this->authUser();
        if (!$user) {
            return $this->error('Unauthenticated.', 401);
        }

        $userObj = new \Myth\Auth\Entities\User($user);
        $groups  = $userObj->getRoles();
        $role    = in_array('admin', $groups) ? 'admin' : 'users';

        return $this->json([
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'fullname' => $user['fullname'] ?? '',
            'phone'    => $user['phone']    ?? '',
            'role'     => $role,
        ]);
    }
}
