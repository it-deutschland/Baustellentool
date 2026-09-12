<?php
/**
 * PDF Dokumentgenerator
 */

namespace RSA21\Classes;

use Mpdf\Mpdf;

class PDFGenerator {
    private $mpdf;
    private $project;
    private $signs;

    public function __construct() {
        try {
            $this->mpdf = new Mpdf([
                'tempDir' => PDF_TEMP_DIR,
                'fontDir' => PDF_FONT_DIR,
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 15,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('PDF-Bibliothek Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Verkehrszeichenplan generieren
     */
    public function generateTrafficSignPlan($projectId, $userId) {
        $project = new Project();
        $this->project = $project->getProject($projectId, $userId);
        
        if (!$this->project) {
            throw new \Exception('Projekt nicht gefunden');
        }

        $html = $this->getTrafficSignPlanHTML();
        $this->mpdf->WriteHTML($html);
        
        return $this->savePDF('Verkehrszeichenplan_' . $projectId);
    }

    /**
     * Umleitungsplan generieren
     */
    public function generateDiversionPlan($projectId, $userId) {
        $project = new Project();
        $this->project = $project->getProject($projectId, $userId);
        
        if (!$this->project) {
            throw new \Exception('Projekt nicht gefunden');
        }

        $html = $this->getDiversionPlanHTML();
        $this->mpdf->WriteHTML($html);
        
        return $this->savePDF('Umleitungsplan_' . $projectId);
    }

    /**
     * Antrag auf verkehrsrechtliche Anordnung generieren
     */
    public function generateTrafficOrderRequest($projectId, $userId) {
        $project = new Project();
        $this->project = $project->getProject($projectId, $userId);
        
        if (!$this->project) {
            throw new \Exception('Projekt nicht gefunden');
        }

        $html = $this->getTrafficOrderRequestHTML();
        $this->mpdf->WriteHTML($html);
        
        return $this->savePDF('Verkehrsrechtliche_Anordnung_' . $projectId);
    }

    /**
     * Kontroll- und Prüfprotokoll generieren
     */
    public function generateControlProtocol($projectId, $userId) {
        $project = new Project();
        $this->project = $project->getProject($projectId, $userId);
        
        if (!$this->project) {
            throw new \Exception('Projekt nicht gefunden');
        }

        $html = $this->getControlProtocolHTML();
        $this->mpdf->WriteHTML($html);
        
        return $this->savePDF('Kontrollprotokoll_' . $projectId);
    }

    /**
     * Baustellendokumentation generieren
     */
    public function generateSiteDocumentation($projectId, $userId) {
        $project = new Project();
        $this->project = $project->getProject($projectId, $userId);
        
        if (!$this->project) {
            throw new \Exception('Projekt nicht gefunden');
        }

        $html = $this->getSiteDocumentationHTML();
        $this->mpdf->WriteHTML($html);
        
        return $this->savePDF('Baustellendokumentation_' . $projectId);
    }

    /**
     * HTML für Verkehrszeichenplan
     */
    private function getTrafficSignPlanHTML() {
        $html = '<h1>Verkehrszeichenplan nach RSA 21</h1>';
        $html .= '<div style="background-color: #f5f5f5; padding: 10px; margin: 10px 0;">';
        $html .= '<h2>Projektinformationen</h2>';
        $html .= '<p><strong>Projektname:</strong> ' . htmlspecialchars($this->project['project_name']) . '</p>';
        $html .= '<p><strong>Ort:</strong> ' . htmlspecialchars($this->project['street'] . ', ' . $this->project['zip_code'] . ' ' . $this->project['city']) . '</p>';
        $html .= '<p><strong>Zeitraum:</strong> ' . $this->project['start_date'] . ' bis ' . $this->project['end_date'] . '</p>';
        $html .= '<p><strong>Beschreibung:</strong> ' . htmlspecialchars($this->project['description']) . '</p>';
        $html .= '</div>';
        $html .= '<h3>Platzierte Verkehrszeichen</h3>';
        $html .= '<table border="1" cellpadding="5" style="width: 100%;">';
        $html .= '<tr style="background-color: #ddd;"><th>Zeichen-Code</th><th>Bezeichnung</th><th>Kategorie</th><th>Position</th></tr>';
        
        // TODO: Zeichen aus Datenbank laden und einfügen
        
        $html .= '</table>';
        $html .= '<p style="margin-top: 20px; font-size: 10px;">Dieses Dokument wurde automatisch generiert. Bitte vor der Verwendung überprüfen.</p>';
        
        return $html;
    }

    /**
     * HTML für Umleitungsplan
     */
    private function getDiversionPlanHTML() {
        $html = '<h1>Umleitungsplan</h1>';
        $html .= '<div style="background-color: #f5f5f5; padding: 10px; margin: 10px 0;">';
        $html .= '<h2>Projektinformationen</h2>';
        $html .= '<p><strong>Projektname:</strong> ' . htmlspecialchars($this->project['project_name']) . '</p>';
        $html .= '<p><strong>Ort:</strong> ' . htmlspecialchars($this->project['street'] . ', ' . $this->project['zip_code'] . ' ' . $this->project['city']) . '</p>';
        $html .= '</div>';
        $html .= '<h3>Umleitungsrouten</h3>';
        $html .= '<p>Karte und Routeninformationen folgen...</p>';
        
        return $html;
    }

    /**
     * HTML für Antrag auf verkehrsrechtliche Anordnung
     */
    private function getTrafficOrderRequestHTML() {
        $html = '<h1>Antrag auf verkehrsrechtliche Anordnung</h1>';
        $html .= '<div style="background-color: #f5f5f5; padding: 10px; margin: 10px 0;">';
        $html .= '<h2>Antragsteller</h2>';
        $html .= '<p>Antragsteller-Details hier einfügen</p>';
        $html .= '<h2>Baustelle</h2>';
        $html .= '<p><strong>Ort:</strong> ' . htmlspecialchars($this->project['street'] . ', ' . $this->project['zip_code'] . ' ' . $this->project['city']) . '</p>';
        $html .= '<p><strong>Zeitraum:</strong> ' . $this->project['start_date'] . ' bis ' . $this->project['end_date'] . '</p>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * HTML für Kontroll- und Prüfprotokoll
     */
    private function getControlProtocolHTML() {
        $html = '<h1>Kontroll- und Prüfprotokoll</h1>';
        $html .= '<p>Validierungsstatus und Prüfdetails...</p>';
        
        return $html;
    }

    /**
     * HTML für Baustellendokumentation
     */
    private function getSiteDocumentationHTML() {
        $html = '<h1>Baustellendokumentation</h1>';
        $html .= '<div style="background-color: #f5f5f5; padding: 10px; margin: 10px 0;">';
        $html .= '<h2>Projekt: ' . htmlspecialchars($this->project['project_name']) . '</h2>';
        $html .= '<p><strong>Ort:</strong> ' . htmlspecialchars($this->project['street'] . ', ' . $this->project['zip_code'] . ' ' . $this->project['city']) . '</p>';
        $html .= '<p><strong>Zeitraum:</strong> ' . $this->project['start_date'] . ' bis ' . $this->project['end_date'] . '</p>';
        $html .= '<p><strong>Status:</strong> ' . ucfirst($this->project['status']) . '</p>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * PDF speichern
     */
    private function savePDF($filename) {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $filepath = UPLOAD_DIR . $filename . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $this->mpdf->Output($filepath, 'F');
        
        return $filepath;
    }
}
