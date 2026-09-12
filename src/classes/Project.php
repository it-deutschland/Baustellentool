<?php
/**
 * Projekt Klasse
 */

namespace RSA21\Classes;

use RSA21\Config\Database;
use PDO;

class Project {
    private $db;
    private $table = 'projects';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Neues Projekt erstellen
     */
    public function createProject($userId, $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, project_name, description, location, street, zip_code, city, 
                 start_date, end_date, status, template_used)
                VALUES 
                (:user_id, :project_name, :description, :location, :street, :zip_code, :city,
                 :start_date, :end_date, :status, :template_used)";

        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':project_name' => $data['project_name'],
            ':description' => $data['description'] ?? null,
            ':location' => $data['location'] ?? null,
            ':street' => $data['street'] ?? null,
            ':zip_code' => $data['zip_code'] ?? null,
            ':city' => $data['city'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':status' => 'draft',
            ':template_used' => $data['template_used'] ?? null,
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Projekt abrufen
     */
    public function getProject($projectId, $userId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $projectId, ':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Alle Projekte eines Benutzers abrufen
     */
    public function getUserProjects($userId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id 
                ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Projekt aktualisieren
     */
    public function updateProject($projectId, $userId, $data) {
        $fields = [];
        $params = [':id' => $projectId, ':user_id' => $userId];
        
        $allowedFields = ['project_name', 'description', 'location', 'street', 'zip_code', 'city', 'start_date', 'end_date', 'status'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Projekt löschen
     */
    public function deleteProject($projectId, $userId) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $projectId, ':user_id' => $userId]);
    }

    /**
     * Projektanzahl des Benutzers
     */
    public function getUserProjectCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
