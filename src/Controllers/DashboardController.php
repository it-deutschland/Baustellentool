<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;

/**
 * Renders the application dashboard.
 */
class DashboardController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $statusRows = $this->db()->fetchAll('SELECT `status`, COUNT(*) AS `count` FROM `projects` GROUP BY `status` ORDER BY `status`');
        $projectStats = [];
        foreach ($statusRows as $row) {
            $projectStats[(string) $row['status']] = (int) $row['count'];
        }

        $recentProjects = $this->db()->fetchAll(
            'SELECT p.*, c.`company` AS `customer_name`
             FROM `projects` p
             LEFT JOIN `customers` c ON c.`id` = p.`customer_id`
             ORDER BY p.`created_at` DESC
             LIMIT 5'
        );
        $recentLogs = $this->db()->fetchAll(
            'SELECT l.*, u.`name` AS `user_name`
             FROM `logs` l
             LEFT JOIN `users` u ON u.`id` = l.`user_id`
             ORDER BY l.`created_at` DESC
             LIMIT 10'
        );
        $lowStockMaterials = $this->db()->fetchAll(
            'SELECT * FROM `materials` WHERE `stock` <= `min_stock` ORDER BY (`min_stock` - `stock`) DESC, `name` ASC LIMIT 10'
        );
        $unreadNotifications = $this->db()->fetch(
            'SELECT COUNT(*) AS `aggregate` FROM `notifications` WHERE `user_id` = :user_id AND `read_at` IS NULL',
            [':user_id' => Auth::id()]
        );
        $recentDocuments = $this->db()->fetchAll(
            'SELECT d.*, p.`title` AS `project_title`
             FROM `documents` d
             INNER JOIN `projects` p ON p.`id` = d.`project_id`
             ORDER BY d.`updated_at` DESC
             LIMIT 5'
        );
        $plansCount = $this->db()->fetch('SELECT COUNT(*) AS `aggregate` FROM `plans`');

        $this->render('dashboard/index', [
            'projectStats' => $projectStats,
            'recentProjects' => $recentProjects,
            'recentLogs' => $recentLogs,
            'lowStockMaterials' => $lowStockMaterials,
            'unreadNotificationsCount' => (int) ($unreadNotifications['aggregate'] ?? 0),
            'recentDocuments' => $recentDocuments,
            'plansCount' => (int) ($plansCount['aggregate'] ?? 0),
        ]);
    }
}
