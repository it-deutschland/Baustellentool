<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Upload model.
 */
class Upload extends Model
{
    protected static string $table = 'uploads';

    /**
     * Get the public upload URL.
     */
    public function getUrl(): string
    {
        return '/dateien/' . ltrim((string) ($this->stored_name ?? ''), '/');
    }

    /**
     * Get the thumbnail URL for the upload.
     */
    public function getThumbnailUrl(): string
    {
        if ($this->isImage() || $this->isSvg()) {
            return $this->getUrl();
        }

        if (strtolower((string) ($this->file_type ?? '')) === 'pdf') {
            return '/assets/img/pdf-icon.svg';
        }

        return $this->getUrl();
    }

    /**
     * Check whether the upload is an image.
     */
    public function isImage(): bool
    {
        return in_array(strtolower((string) ($this->file_type ?? '')), ['png', 'jpg', 'jpeg', 'gif'], true);
    }

    /**
     * Check whether the upload is an SVG.
     */
    public function isSvg(): bool
    {
        return strtolower((string) ($this->file_type ?? '')) === 'svg';
    }

    /**
     * Get uploads for a project.
     *
     * @return array<int, static>
     */
    public static function forProject(int $projectId, string $purpose = ''): array
    {
        $sql = 'SELECT * FROM `uploads` WHERE `project_id` = :project_id';
        $params = [':project_id' => $projectId];

        if ($purpose !== '') {
            $sql .= ' AND `purpose` = :purpose';
            $params[':purpose'] = $purpose;
        }

        $sql .= ' ORDER BY `created_at` DESC';
        $rows = Database::getInstance()->fetchAll($sql, $params);

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    /**
     * Get uploads for a plan.
     *
     * @return array<int, static>
     */
    public static function forPlan(int $planId): array
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT * FROM `uploads` WHERE `plan_id` = :plan_id ORDER BY `created_at` DESC',
            [':plan_id' => $planId]
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }
}
