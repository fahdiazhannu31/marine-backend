<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\ApiTokenModel;
use Myth\Auth\Models\UserModel;

/**
 * ApiAuthFilter
 *
 * Protects /api/admin/* routes by validating Bearer token.
 * Returns 401 JSON if token is missing, invalid, or expired.
 * Returns 403 JSON if user does not have 'admin' role.
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Extract Bearer token from Authorization header
        $header = $request->getHeaderLine('Authorization');
        $token  = null;

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        }

        if (!$token) {
            return self::unauthorized('No authentication token provided.');
        }

        // Validate token exists and not expired
        $tokenModel = new ApiTokenModel();
        $row = $tokenModel->findValid($token);

        if (!$row) {
            return self::unauthorized('Invalid or expired token. Please login again.');
        }

        // Load user
        $userModel = new UserModel();
        $user = $userModel->find($row['user_id']);

        if (!$user) {
            return self::unauthorized('User not found.');
        }

        // Check if user is active (not banned/inactive)
        if ($user->active == 0) {
            return self::unauthorized('Account is inactive.');
        }

        // Check admin role (if 'admin' argument passed)
        if ($arguments && in_array('admin', $arguments)) {
            $groups = $user->getRoles();
            if (!in_array('admin', $groups)) {
                return self::forbidden('Admin access required.');
            }
        }

        // Store user in request for controllers to use
        $request->user = $user;
        $request->userId = $row['user_id'];
        $request->tokenRow = $row;

        return null; // Continue to controller
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private static function unauthorized(string $message)
    {
        $response = service('response');
        return $response
            ->setStatusCode(401)
            ->setHeader('Content-Type', 'application/json')
            ->setJSON([
                'error'   => 'Unauthorized',
                'message' => $message,
            ]);
    }

    private static function forbidden(string $message)
    {
        $response = service('response');
        return $response
            ->setStatusCode(403)
            ->setHeader('Content-Type', 'application/json')
            ->setJSON([
                'error'   => 'Forbidden',
                'message' => $message,
            ]);
    }
}
