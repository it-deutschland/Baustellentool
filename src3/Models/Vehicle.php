<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Vehicle model.
 */
class Vehicle extends Model
{
    protected static string $table = 'vehicles';

    /**
     * Get the related project.
     */
    public function getProject(): ?Project
    {
        $projectId = (int) ($this->project_id ?? 0);

        return $projectId > 0 ? Project::find($projectId) : null;
    }
}
