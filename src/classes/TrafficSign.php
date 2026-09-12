<?php
/**
 * Verkehrszeichen Klasse
 */

namespace RSA21\Classes;

use RSA21\Config\Database;
use PDO;

class TrafficSign {
    private $db;
    private $table = 'traffic_signs';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Verkehrszeichen abrufen
     */
    public function getSign($signId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $signId]);
        return $stmt->fetch();
    }

    /**
     * Verkehrszeichen nach Code abrufen
     */
    public function getSignByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE sign_code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code]);
        return $stmt->fetch();
    }

    /**
     * Alle Zeichen einer Kategorie
     */
    public function getSignsByCategory($category) {
        $sql = "SELECT * FROM {$this->table} WHERE category = :category AND rsa21_compliant = TRUE 
                ORDER BY sign_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category' => $category]);
        return $stmt->fetchAll();
    }

    /**
     * Alle RSA21-konformen Zeichen
     */
    public function getAllSigns() {
        $sql = "SELECT * FROM {$this->table} WHERE rsa21_compliant = TRUE ORDER BY sign_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Zeichen gruppiert nach Kategorie
     */
    public function getSignsByCategories() {
        $sql = "SELECT * FROM {$this->table} WHERE rsa21_compliant = TRUE 
                ORDER BY category, sign_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $signs = $stmt->fetchAll();
        $grouped = [];
        
        foreach ($signs as $sign) {
            $category = $sign['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $sign;
        }
        
        return $grouped;
    }

    /**
     * Neues Zeichen hinzufügen
     */
    public function addSign($data) {
        $sql = "INSERT INTO {$this->table} 
                (sign_code, sign_name, description, category, svg_path, width, height)
                VALUES 
                (:sign_code, :sign_name, :description, :category, :svg_path, :width, :height)";

        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':sign_code' => $data['sign_code'],
            ':sign_name' => $data['sign_name'],
            ':description' => $data['description'] ?? null,
            ':category' => $data['category'],
            ':svg_path' => $data['svg_path'] ?? null,
            ':width' => $data['width'] ?? 1050,
            ':height' => $data['height'] ?? 1050,
        ]);
    }
}
