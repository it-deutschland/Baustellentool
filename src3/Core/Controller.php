<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller.
 */
abstract class Controller
{
    /**
     * Renders a view with an optional layout.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], string $layout = 'default'): void
    {
        View::render($view, $data, $layout);
    }

    /**
     * Outputs a JSON response.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    /**
     * Redirects to a given URL.
     */
    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    /**
     * Redirects the client to the previous page.
     */
    protected function back(): void
    {
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    /**
     * Requires an authenticated user.
     */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('/login');
        }
    }

    /**
     * Requires a specific role.
     */
    protected function requireRole(string $role): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('/login');
        }

        if (!Auth::hasRole($role)) {
            Response::forbidden();
        }
    }
}
