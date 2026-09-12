<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

/**
 * Manages users and roles.
 */
class UserController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');

        $users = $this->db()->fetchAll(
            'SELECT u.*, r.`name` AS `role`, r.`display_name` AS `role_name`
             FROM `users` u
             LEFT JOIN `roles` r ON r.`id` = u.`role_id`
             ORDER BY u.`created_at` DESC'
        );
        $this->render('users/index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->render('users/create', ['roles' => $this->db()->fetchAll('SELECT * FROM `roles` ORDER BY `id` ASC')]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'name' => 'required|max:100',
            'email' => 'required|email|max:191',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the user form.');
            $this->back();
        }

        $email = strtolower(trim((string) Request::post('email')));
        if ($this->db()->table('users')->where('email', $email)->first() !== null) {
            Session::flash('error', 'That email address is already in use.');
            $this->back();
        }

        $payload = [
            'role_id' => (int) Request::post('role_id'),
            'name' => trim((string) Request::post('name')),
            'email' => $email,
            'password' => Auth::hashPassword((string) Request::post('password')),
            'phone' => trim((string) Request::post('phone', '')) ?: null,
            'is_active' => $this->normalizeBoolean(Request::post('is_active', 1)),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db()->table('users')->insert($payload);
        $userId = (int) $this->db()->lastInsertId();
        $this->recordLog('user_created', 'users', $userId, 'user', [], $payload);
        Session::flash('success', 'User created successfully.');
        $this->redirect('/users');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->render('users/edit', [
            'user' => $this->requireRecord('users', $id),
            'roles' => $this->db()->fetchAll('SELECT * FROM `roles` ORDER BY `id` ASC'),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $user = $this->requireRecord('users', $id);
        $validator = Validator::make(Request::all(), [
            'name' => 'required|max:100',
            'email' => 'required|email|max:191',
            'role_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the user form.');
            $this->back();
        }

        $email = strtolower(trim((string) Request::post('email')));
        $duplicate = $this->db()->fetch(
            'SELECT `id` FROM `users` WHERE `email` = :email AND `id` != :id LIMIT 1',
            [':email' => $email, ':id' => (int) $id]
        );
        if ($duplicate !== false) {
            Session::flash('error', 'That email address is already in use.');
            $this->back();
        }

        $update = [
            'role_id' => (int) Request::post('role_id'),
            'name' => trim((string) Request::post('name')),
            'email' => $email,
            'phone' => trim((string) Request::post('phone', '')) ?: null,
            'is_active' => $this->normalizeBoolean(Request::post('is_active', 1)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $password = (string) Request::post('password', '');
        if ($password !== '') {
            $update['password'] = Auth::hashPassword($password);
        }

        $this->db()->table('users')->where('id', (int) $id)->update($update);
        $this->recordLog('user_updated', 'users', (int) $id, 'user', $user, $update);
        Session::flash('success', 'User updated successfully.');
        $this->redirect('/users');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        if ((int) $id === (int) Auth::id()) {
            Session::flash('error', 'You cannot delete your own account.');
            $this->back();
        }

        $user = $this->requireRecord('users', $id);
        $this->db()->table('users')->where('id', (int) $id)->delete();
        $this->recordLog('user_deleted', 'users', (int) $id, 'user', $user);
        Session::flash('success', 'User deleted successfully.');
        $this->redirect('/users');
    }
}
