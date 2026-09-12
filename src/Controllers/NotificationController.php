<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;

/**
 * Handles user notifications.
 */
class NotificationController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $notifications = $this->db()->fetchAll(
            'SELECT * FROM `notifications` WHERE `user_id` = :user_id ORDER BY `created_at` DESC',
            [':user_id' => Auth::id()]
        );
        $this->render('notifications/index', ['notifications' => $notifications]);
    }

    public function markRead(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $this->db()->execute(
            'UPDATE `notifications` SET `read_at` = :read_at WHERE `id` = :id AND `user_id` = :user_id',
            [
                ':read_at' => date('Y-m-d H:i:s'),
                ':id' => (int) $id,
                ':user_id' => Auth::id(),
            ]
        );
        $this->recordLog('notification_read', 'notifications', (int) $id, 'notification');

        if (Request::isAjax()) {
            $this->json(['message' => 'Notification marked as read.']);
        }

        $this->back();
    }

    public function markAllRead(): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $this->db()->execute(
            'UPDATE `notifications` SET `read_at` = :read_at WHERE `user_id` = :user_id AND `read_at` IS NULL',
            [':read_at' => date('Y-m-d H:i:s'), ':user_id' => Auth::id()]
        );
        $this->recordLog('notifications_read_all', 'notifications');
        $this->json(['message' => 'All notifications marked as read.']);
    }
}
