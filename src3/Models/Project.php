<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Project model.
 */
class Project extends Model
{
    protected static string $table = 'projects';

    /**
     * Get the linked customer.
     */
    public function getCustomer(): ?Customer
    {
        $customerId = (int) ($this->customer_id ?? 0);

        return $customerId > 0 ? Customer::find($customerId) : null;
    }

    /**
     * Get the creating user.
     */
    public function getCreator(): ?User
    {
        $userId = (int) ($this->created_by ?? 0);

        return $userId > 0 ? User::find($userId) : null;
    }

    /**
     * Get the assigned user.
     */
    public function getAssignee(): ?User
    {
        $userId = (int) ($this->assigned_to ?? 0);

        return $userId > 0 ? User::find($userId) : null;
    }

    /**
     * Get all project plans.
     *
     * @return array<int, Plan>
     */
    public function getPlans(): array
    {
        return Plan::where('project_id', (int) ($this->id ?? 0));
    }

    /**
     * Get all project documents.
     *
     * @return array<int, Document>
     */
    public function getDocuments(): array
    {
        return Document::where('project_id', (int) ($this->id ?? 0));
    }

    /**
     * Get all materials assigned to the project, including pivot data.
     *
     * @return array<int, Material>
     */
    public function getMaterials(): array
    {
        $projectId = (int) ($this->id ?? 0);
        if ($projectId <= 0) {
            return [];
        }

        $rows = Database::getInstance()->fetchAll(
            'SELECT m.*, pm.`id` AS `pivot_id`, pm.`quantity` AS `pivot_quantity`, pm.`notes` AS `pivot_notes`
             FROM `project_materials` pm
             INNER JOIN `materials` m ON m.`id` = pm.`material_id`
             WHERE pm.`project_id` = :project_id
             ORDER BY m.`category` ASC, m.`name` ASC',
            [':project_id' => $projectId]
        );

        return array_map(static fn (array $row): Material => new Material($row), $rows);
    }

    /**
     * Get uploads for the project.
     *
     * @return array<int, Upload>
     */
    public function getUploads(string $purpose = ''): array
    {
        return Upload::forProject((int) ($this->id ?? 0), $purpose);
    }

    /**
     * Generate the next project number for the current year.
     */
    public function generateProjectNumber(): string
    {
        $year = date('Y');
        $row = Database::getInstance()->fetch(
            'SELECT COUNT(*) AS `aggregate`
             FROM `projects`
             WHERE `project_number` LIKE :prefix',
            [':prefix' => sprintf('PRJ-%s-%%', $year)]
        );

        $nextNumber = ((int) ($row['aggregate'] ?? 0)) + 1;

        return sprintf('PRJ-%s-%04d', $year, $nextNumber);
    }

    /**
     * Get the German status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'draft' => 'Entwurf',
            'active' => 'Aktiv',
            'review' => 'In Prüfung',
            'completed' => 'Abgeschlossen',
            'archived' => 'Archiviert',
        ];

        return $labels[(string) ($this->status ?? '')] ?? 'Unbekannt';
    }

    /**
     * Get the German priority label.
     */
    public function getPriorityLabel(): string
    {
        $labels = [
            'low' => 'Niedrig',
            'normal' => 'Normal',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
        ];

        return $labels[(string) ($this->priority ?? '')] ?? 'Unbekannt';
    }

    /**
     * Get the Bootstrap badge class for the status.
     */
    public function getStatusBadgeClass(): string
    {
        $classes = [
            'draft' => 'bg-secondary',
            'active' => 'bg-primary',
            'review' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'archived' => 'bg-dark',
        ];

        return $classes[(string) ($this->status ?? '')] ?? 'bg-secondary';
    }

    /**
     * Count projects by status.
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        $counts = [
            'draft' => 0,
            'active' => 0,
            'review' => 0,
            'completed' => 0,
            'archived' => 0,
        ];

        $rows = Database::getInstance()->fetchAll(
            'SELECT `status`, COUNT(*) AS `aggregate`
             FROM `projects`
             GROUP BY `status`'
        );

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if ($status !== '') {
                $counts[$status] = (int) ($row['aggregate'] ?? 0);
            }
        }

        return $counts;
    }

    /**
     * Get recently updated projects.
     *
     * @return array<int, static>
     */
    public static function recentlyUpdated(int $limit = 10): array
    {
        $limit = max(1, $limit);
        $rows = Database::getInstance()->fetchAll(
            sprintf(
                'SELECT * FROM `projects` ORDER BY `updated_at` DESC LIMIT %d',
                $limit
            )
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }
}
