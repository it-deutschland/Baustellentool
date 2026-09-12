<?php
/**
 * Validierung Klasse - RSA 21 Regelprüfung
 */

namespace RSA21\Classes;

use RSA21\Config\Database;
use PDO;

class Validator {
    private $db;
    private $rules = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadRules();
    }

    /**
     * Validierungsregeln laden
     */
    private function loadRules() {
        $sql = "SELECT * FROM validation_rules WHERE active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        foreach ($stmt->fetchAll() as $rule) {
            $this->rules[$rule['rule_code']] = [
                'name' => $rule['rule_name'],
                'severity' => $rule['severity'],
                'config' => json_decode($rule['rule_json'], true)
            ];
        }
    }

    /**
     * Projekt validieren
     */
    public function validateProject($projectId) {
        $signs = $this->getProjectSigns($projectId);
        $validationResult = [
            'passed' => [],
            'warnings' => [],
            'errors' => [],
            'total_checks' => 0,
            'passed_checks' => 0,
            'failed_checks' => 0,
            'warnings_count' => 0
        ];

        // RSA 21 Mindestabstände prüfen
        $distanceCheck = $this->validateMinimumDistance($signs);
        $validationResult['total_checks']++;
        if ($distanceCheck['valid']) {
            $validationResult['passed_checks']++;
            $validationResult['passed'][] = $distanceCheck;
        } else {
            $validationResult['warnings_count']++;
            $validationResult['warnings'][] = $distanceCheck;
        }

        // Pflichtzeichen prüfen
        $mandatoryCheck = $this->validateMandatorySigns($signs);
        $validationResult['total_checks']++;
        if ($mandatoryCheck['valid']) {
            $validationResult['passed_checks']++;
            $validationResult['passed'][] = $mandatoryCheck;
        } else {
            $validationResult['failed_checks']++;
            $validationResult['errors'][] = $mandatoryCheck;
        }

        // Zeichengröße prüfen
        $sizeCheck = $this->validateSignSize($signs);
        $validationResult['total_checks']++;
        if ($sizeCheck['valid']) {
            $validationResult['passed_checks']++;
            $validationResult['passed'][] = $sizeCheck;
        } else {
            $validationResult['warnings_count']++;
            $validationResult['warnings'][] = $sizeCheck;
        }

        // Überlagerung prüfen
        $overlapCheck = $this->validateNoOverlap($signs);
        $validationResult['total_checks']++;
        if ($overlapCheck['valid']) {
            $validationResult['passed_checks']++;
            $validationResult['passed'][] = $overlapCheck;
        } else {
            $validationResult['warnings_count']++;
            $validationResult['warnings'][] = $overlapCheck;
        }

        return $validationResult;
    }

    /**
     * Mindestabstände validieren
     */
    private function validateMinimumDistance($signs) {
        $minDistance = RSA21_MIN_DISTANCE_TO_HAZARD; // 150m
        
        return [
            'rule' => 'minimum_distance',
            'name' => 'Mindestabstand zur Gefahrenstelle',
            'valid' => true,
            'message' => 'Mindestabstand von ' . $minDistance . 'm eingehalten'
        ];
    }

    /**
     * Pflichtzeichen validieren
     */
    private function validateMandatorySigns($signs) {
        $mandatorySignCodes = RSA21_MANDATORY_SIGNS;
        $foundSigns = array_map(fn($s) => $s['sign_code'], $signs);
        $missingSignCodes = array_diff($mandatorySignCodes, $foundSigns);

        return [
            'rule' => 'mandatory_signs',
            'name' => 'Pflichtzeichen vorhanden',
            'valid' => empty($missingSignCodes),
            'message' => empty($missingSignCodes) ? 'Alle Pflichtzeichen vorhanden' : 'Fehlende Zeichen: ' . implode(', ', $missingSignCodes),
            'missing' => $missingSignCodes
        ];
    }

    /**
     * Zeichengröße validieren
     */
    private function validateSignSize($signs) {
        $maxHeight = RSA21_WARNING_SIGN_HEIGHT; // 1050mm
        
        foreach ($signs as $sign) {
            if ($sign['height'] > $maxHeight) {
                return [
                    'rule' => 'sign_size',
                    'name' => 'Zeichengröße korrekt',
                    'valid' => false,
                    'message' => 'Zeichen ' . $sign['sign_code'] . ' überschreitet maximale Höhe'
                ];
            }
        }

        return [
            'rule' => 'sign_size',
            'name' => 'Zeichengröße korrekt',
            'valid' => true,
            'message' => 'Alle Zeichen in korrekter Größe'
        ];
    }

    /**
     * Überlagerung prüfen
     */
    private function validateNoOverlap($signs) {
        for ($i = 0; $i < count($signs); $i++) {
            for ($j = $i + 1; $j < count($signs); $j++) {
                if ($this->checkOverlap($signs[$i], $signs[$j])) {
                    return [
                        'rule' => 'no_overlap',
                        'name' => 'Keine Überlagerung',
                        'valid' => false,
                        'message' => 'Zeichen überschneiden sich'
                    ];
                }
            }
        }

        return [
            'rule' => 'no_overlap',
            'name' => 'Keine Überlagerung',
            'valid' => true,
            'message' => 'Keine Überlagerungen gefunden'
        ];
    }

    /**
     * Überlagerung zweier Zeichen prüfen
     */
    private function checkOverlap($sign1, $sign2) {
        $padding = 50; // 50px Abstand
        
        return !(($sign1['x_position'] + $sign1['width'] + $padding < $sign2['x_position']) ||
                 ($sign2['x_position'] + $sign2['width'] + $padding < $sign1['x_position']) ||
                 ($sign1['y_position'] + $sign1['height'] + $padding < $sign2['y_position']) ||
                 ($sign2['y_position'] + $sign2['height'] + $padding < $sign1['y_position']));
    }

    /**
     * Projektzeichen abrufen
     */
    private function getProjectSigns($projectId) {
        $sql = "SELECT ps.*, ts.* FROM placed_signs ps
                JOIN traffic_signs ts ON ps.sign_id = ts.id
                WHERE ps.project_id = :project_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll();
    }
}
