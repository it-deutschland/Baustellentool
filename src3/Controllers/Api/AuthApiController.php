<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Validator;

/**
 * API authentication endpoints.
 */
class AuthApiController extends Controller
{
    use ControllerHelpers;

    public function login(): void
    {
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            $this->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim((string) Request::input('email')));
        $user = $this->db()->fetch(
            'SELECT u.*, r.`name` AS `role`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`email` = :email
             LIMIT 1',
            [':email' => $email]
        );

        if ($user === false || !Auth::verifyPassword((string) Request::input('password'), (string) ($user['password'] ?? ''))) {
            $this->json(['message' => 'Invalid credentials.'], 401);
        }

        if ((int) ($user['is_active'] ?? 1) !== 1) {
            $this->json(['message' => 'Account disabled.'], 403);
        }

        $token = JWT::encode([
            'sub' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) ($user['role'] ?? ''),
        ], 3600);
        $this->recordLog('api_login', 'api', (int) $user['id'], 'user');
        $this->json(['token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3600]);
    }

    public function refresh(): void
    {
        $this->ensureCsrf();

        $payload = JWT::fromRequest();
        if ($payload === null) {
            $this->json(['message' => 'Invalid token.'], 401);
        }

        $userId = (int) ($payload['sub'] ?? 0);
        $user = $this->loadUserById($userId);
        if ($user === null) {
            $this->json(['message' => 'Invalid token.'], 401);
        }

        $token = JWT::encode([
            'sub' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) ($user['role'] ?? ''),
        ], 3600);
        $this->recordLog('api_token_refreshed', 'api', $userId, 'user');
        $this->json(['token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3600]);
    }
}
