<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Plan model.
 */
class Plan extends Model
{
    protected static string $table = 'plans';

    /**
     * Get the related project.
     */
    public function getProject(): ?Project
    {
        $projectId = (int) ($this->project_id ?? 0);

        return $projectId > 0 ? Project::find($projectId) : null;
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
     * Get the approving user.
     */
    public function getApprover(): ?User
    {
        $userId = (int) ($this->approved_by ?? 0);

        return $userId > 0 ? User::find($userId) : null;
    }

    /**
     * Get uploads attached to the plan.
     *
     * @return array<int, Upload>
     */
    public function getUploads(): array
    {
        return Upload::forPlan((int) ($this->id ?? 0));
    }

    /**
     * Get the German status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'draft' => 'Entwurf',
            'review' => 'In Prüfung',
            'approved' => 'Freigegeben',
            'archived' => 'Archiviert',
        ];

        return $labels[(string) ($this->status ?? '')] ?? 'Unbekannt';
    }

    /**
     * Get the Bootstrap badge class for the status.
     */
    public function getStatusBadgeClass(): string
    {
        $classes = [
            'draft' => 'bg-secondary',
            'review' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'archived' => 'bg-dark',
        ];

        return $classes[(string) ($this->status ?? '')] ?? 'bg-secondary';
    }
}
