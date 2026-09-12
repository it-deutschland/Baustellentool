<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

/**
 * Handles authentication flows.
 */
class AuthController extends Controller
{
    use ControllerHelpers;

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login');
    }

    public function login(): void
    {
        $this->ensureCsrf();

        $input = Request::all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct the login form.');
            $this->back();
        }

        $email = strtolower(trim((string) Request::post('email')));
        $password = (string) Request::post('password');
        $user = $this->db()->fetch(
            'SELECT u.*, r.`name` AS `role`, r.`display_name` AS `role_name`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             WHERE u.`email` = :email
             LIMIT 1',
            [':email' => $email]
        );

        if ($user === false) {
            Session::flash('error', 'Invalid credentials.');
            $this->recordLog('login_failed', 'auth', null, 'user', [], ['email' => $email]);
            $this->back();
        }

        if ((int) ($user['is_active'] ?? 1) !== 1) {
            Session::flash('error', 'This account is disabled.');
            $this->back();
        }

        $lockedUntil = isset($user['locked_until']) ? strtotime((string) $user['locked_until']) : false;
        if ($lockedUntil !== false && $lockedUntil > time()) {
            Session::flash('error', 'Too many failed attempts. Please try again later.');
            $this->back();
        }

        $hash = (string) ($user['password'] ?? '');
        if ($hash === '' || !Auth::verifyPassword($password, $hash)) {
            $attempts = (int) ($user['login_attempts'] ?? 0) + 1;
            $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? (int) MAX_LOGIN_ATTEMPTS : 5;
            $update = ['login_attempts' => $attempts];
            if ($attempts >= $maxAttempts) {
                $lockSeconds = defined('LOGIN_LOCKOUT_TIME') ? (int) LOGIN_LOCKOUT_TIME : 900;
                $update['locked_until'] = date('Y-m-d H:i:s', time() + $lockSeconds);
                $update['login_attempts'] = 0;
            }

            $this->db()->table('users')->where('id', (int) $user['id'])->update($update);
            $this->recordLog('login_failed', 'auth', (int) $user['id'], 'user', [], ['email' => $email]);
            Session::flash('error', 'Invalid credentials.');
            $this->back();
        }

        $this->db()->table('users')->where('id', (int) $user['id'])->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        if ((int) ($user['two_factor_enabled'] ?? 0) === 1 && !empty($user['two_factor_secret'])) {
            Session::set('pending_2fa_user', (int) $user['id']);
            Session::set('pending_2fa_time', time());
            Session::flash('success', 'Enter your authentication code to continue.');
            $this->redirect('/2fa');
        }

        $this->completeLogin((int) $user['id']);
        Session::flash('success', 'Welcome back.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if (Auth::id() !== null) {
            $this->recordLog('logout', 'auth', Auth::id(), 'user');
        }

        Auth::logout();
        Session::destroy();
        Session::start();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        if (!$this->isRegistrationEnabled()) {
            Session::flash('error', 'Registration is currently disabled.');
            $this->redirect('/login');
        }

        $this->render('auth/register');
    }

    public function register(): void
    {
        $this->ensureCsrf();

        if (!$this->isRegistrationEnabled()) {
            Session::flash('error', 'Registration is currently disabled.');
            $this->redirect('/login');
        }

        $input = Request::all();
        $validator = Validator::make($input, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:191',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct the registration form.');
            $this->back();
        }

        $email = strtolower(trim((string) Request::post('email')));
        if ($this->db()->table('users')->where('email', $email)->first() !== null) {
            Session::flash('error', 'That email address is already in use.');
            $this->back();
        }

        $userCount = $this->db()->table('users')->count();
        $roleId = $userCount === 0 ? 1 : 4;
        $this->db()->table('users')->insert([
            'role_id' => $roleId,
            'name' => trim((string) Request::post('name')),
            'email' => $email,
            'password' => Auth::hashPassword((string) Request::post('password')),
            'phone' => trim((string) Request::post('phone', '')) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $this->db()->lastInsertId();
        $this->completeLogin($userId);
        $this->recordLog('register', 'auth', $userId, 'user', [], ['email' => $email, 'role_id' => $roleId]);
        Session::flash('success', 'Your account has been created.');
        $this->redirect('/dashboard');
    }

    public function showForgot(): void
    {
        $this->render('auth/forgot');
    }

    public function forgotPassword(): void
    {
        $this->ensureCsrf();

        $input = Request::all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please enter a valid email address.');
            $this->back();
        }

        $email = strtolower(trim((string) Request::post('email')));
        $user = $this->db()->table('users')->where('email', $email)->first();
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $this->db()->table('users')->where('id', (int) $user['id'])->update([
                'token' => $token,
                'token_expires_at' => $expiresAt,
            ]);

            $resetLink = rtrim((string) APP_URL, '/') . '/reset/' . $token;
            $body = sprintf(
                '<p>Hello %s,</p><p>Reset your password by clicking <a href="%s">this link</a>.</p><p>This link expires in 60 minutes.</p>',
                htmlspecialchars((string) ($user['name'] ?? 'User'), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8')
            );
            Mailer::send($email, 'Password reset request', $body, true);
            $this->recordLog('password_reset_requested', 'auth', (int) $user['id'], 'user');
        }

        Session::flash('success', 'If the address exists, a password reset link has been sent.');
        $this->redirect('/login');
    }

    public function showReset(string $token): void
    {
        $user = $this->db()->fetch(
            'SELECT `id` FROM `users` WHERE `token` = :token AND (`token_expires_at` IS NULL OR `token_expires_at` >= :now) LIMIT 1',
            [':token' => $token, ':now' => date('Y-m-d H:i:s')]
        );

        if ($user === false) {
            Session::flash('error', 'That reset link is invalid or has expired.');
            $this->redirect('/forgot');
        }

        $this->render('auth/reset', ['token' => $token]);
    }

    public function resetPassword(): void
    {
        $this->ensureCsrf();

        $input = Request::all();
        $validator = Validator::make($input, [
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct the password reset form.');
            $this->back();
        }

        $token = (string) Request::post('token');
        $user = $this->db()->fetch(
            'SELECT * FROM `users` WHERE `token` = :token AND (`token_expires_at` IS NULL OR `token_expires_at` >= :now) LIMIT 1',
            [':token' => $token, ':now' => date('Y-m-d H:i:s')]
        );

        if ($user === false) {
            Session::flash('error', 'That reset link is invalid or has expired.');
            $this->redirect('/forgot');
        }

        $this->db()->table('users')->where('id', (int) $user['id'])->update([
            'password' => Auth::hashPassword((string) Request::post('password')),
            'token' => null,
            'token_expires_at' => null,
        ]);
        $this->recordLog('password_reset_completed', 'auth', (int) $user['id'], 'user');
        Session::flash('success', 'Your password has been updated.');
        $this->redirect('/login');
    }

    public function show2fa(): void
    {
        if (!Session::has('pending_2fa_user')) {
            Session::flash('error', 'There is no pending two-factor login.');
            $this->redirect('/login');
        }

        $this->render('auth/2fa');
    }

    public function verify2fa(): void
    {
        $this->ensureCsrf();

        $pendingUserId = (int) Session::get('pending_2fa_user', 0);
        if ($pendingUserId <= 0) {
            Session::flash('error', 'There is no pending two-factor login.');
            $this->redirect('/login');
        }

        $validator = Validator::make(Request::all(), [
            'code' => 'required|min:6|max:6',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors());
            Session::flash('error', 'Enter a valid 2FA code.');
            $this->back();
        }

        $user = $this->requireRecord('users', $pendingUserId);
        $code = preg_replace('/\D+/', '', (string) Request::post('code')) ?? '';
        if ($code === '' || !$this->verifyTotp((string) ($user['two_factor_secret'] ?? ''), $code)) {
            Session::flash('error', 'The authentication code is invalid.');
            $this->recordLog('two_factor_failed', 'auth', $pendingUserId, 'user');
            $this->back();
        }

        Session::delete('pending_2fa_user');
        Session::delete('pending_2fa_time');
        $this->completeLogin($pendingUserId);
        Session::flash('success', 'Two-factor authentication complete.');
        $this->redirect('/dashboard');
    }

    public function profile(): void
    {
        $this->requireAuth();
        $user = $this->loadUserById((int) Auth::id());
        $this->render('auth/profile', ['user' => $user]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $input = Request::all();
        $validator = Validator::make($input, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:191',
        ]);

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $input);
            Session::flash('error', 'Please correct your profile details.');
            $this->back();
        }

        $userId = (int) Auth::id();
        $user = $this->requireRecord('users', $userId);
        $email = strtolower(trim((string) Request::post('email')));
        $duplicate = $this->db()->fetch(
            'SELECT `id` FROM `users` WHERE `email` = :email AND `id` != :id LIMIT 1',
            [':email' => $email, ':id' => $userId]
        );

        if ($duplicate !== false) {
            Session::flash('error', 'That email address is already in use.');
            $this->back();
        }

        $update = [
            'name' => trim((string) Request::post('name')),
            'email' => $email,
            'phone' => trim((string) Request::post('phone', '')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $avatar = Request::file('avatar');
        if ($avatar !== null && (int) ($avatar['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $stored = $this->storeUploadedFile($avatar, 'avatars', ['png', 'jpg', 'jpeg', 'webp', 'gif']);
            $update['avatar'] = $stored['file_path'];
        }

        $this->db()->table('users')->where('id', $userId)->update($update);
        $this->refreshAuthenticatedUser($userId);
        $this->recordLog('profile_updated', 'auth', $userId, 'user', $user, $update);
        Session::flash('success', 'Your profile has been updated.');
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'old_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors());
            Session::flash('error', 'Please correct the password form.');
            $this->back();
        }

        $userId = (int) Auth::id();
        $user = $this->requireRecord('users', $userId);
        if (!Auth::verifyPassword((string) Request::post('old_password'), (string) ($user['password'] ?? ''))) {
            Session::flash('error', 'Your current password is incorrect.');
            $this->back();
        }

        $this->db()->table('users')->where('id', $userId)->update([
            'password' => Auth::hashPassword((string) Request::post('password')),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->recordLog('password_changed', 'auth', $userId, 'user');
        Session::flash('success', 'Your password has been changed.');
        $this->redirect('/profile');
    }

    public function enable2fa(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $user = $this->loadUserById((int) Auth::id());
        $secret = $this->generateBase32Secret();
        Session::set('pending_2fa_secret', $secret);

        $issuer = rawurlencode(defined('APP_NAME') ? (string) APP_NAME : 'Sonka Bau & Sonnenimmobilien - Multi Administration');
        $label = rawurlencode(($user['email'] ?? 'user') . '@' . $issuer);
        $qrUrl = sprintf('otpauth://totp/%s?secret=%s&issuer=%s', $label, $secret, $issuer);

        $this->render('auth/2fa-enable', [
            'secret' => $secret,
            'qrUrl' => $qrUrl,
            'user' => $user,
        ]);
    }

    public function disable2fa(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $userId = (int) Auth::id();
        $this->db()->table('users')->where('id', $userId)->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::delete('pending_2fa_secret');
        $this->refreshAuthenticatedUser($userId);
        $this->recordLog('two_factor_disabled', 'auth', $userId, 'user');
        Session::flash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('/profile');
    }

    private function completeLogin(int $userId): void
    {
        $this->db()->table('users')->where('id', $userId)->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => Request::ip(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->refreshAuthenticatedUser($userId);
        $this->recordLog('login', 'auth', $userId, 'user');
    }

    private function verifyTotp(string $secret, string $code): bool
    {
        $decoded = $this->base32Decode($secret);
        $time = intdiv(time(), 30);
        for ($i = -1; $i <= 1; $i++) {
            $msg = pack('J', $time + $i);
            $hash = hash_hmac('sha1', $msg, $decoded, true);
            $offset = ord($hash[19]) & 0x0F;
            $otp = ((ord($hash[$offset]) & 0x7F) << 24 | ord($hash[$offset + 1]) << 16 | ord($hash[$offset + 2]) << 8 | ord($hash[$offset + 3])) % 1000000;
            if (str_pad((string) $otp, 6, '0', STR_PAD_LEFT) === $code) {
                return true;
            }
        }

        return false;
    }

    private function base32Decode(string $s): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $s = strtoupper(rtrim($s, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $pos = strpos($alphabet, $s[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }
}
