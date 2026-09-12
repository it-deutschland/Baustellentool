<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Document model.
 */
class Document extends Model
{
    protected static string $table = 'documents';

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
     * Get the linked template.
     */
    public function getTemplate(): ?Template
    {
        $templateId = (int) ($this->template_id ?? 0);

        return $templateId > 0 ? Template::find($templateId) : null;
    }

    /**
     * Get the German document type label.
     */
    public function getTypeLabel(): string
    {
        return self::getTypeOptions()[(string) ($this->type ?? '')] ?? 'Sonstiges';
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
     * Get available document type options.
     *
     * @return array<string, string>
     */
    public static function getTypeOptions(): array
    {
        return [
            'vra' => 'Verkehrsrechtliche Anordnung',
            'signlist' => 'Verkehrszeichenliste',
            'materiallist' => 'Materialliste',
            'dailyreport' => 'Tagesbericht',
            'sitecheck' => 'Baustellenkontrolle',
            'acceptance' => 'Abnahmeprotokoll',
            'report' => 'Projektbericht',
            'other' => 'Sonstiges',
        ];
    }
}
