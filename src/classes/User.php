<?php
/**
 * Benutzer Klasse
 */

namespace RSA21\Classes;

use RSA21\Config\Database;
use PDO;

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Benutzer registrieren
     */
    public function register($data) {
        $sql = "INSERT INTO {$this->table} (username, email, password_hash, firstname, lastname, company, phone, role)
                VALUES (:username, :email, :password_hash, :firstname, :lastname, :company, :phone, :role)";

        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':firstname' => $data['firstname'] ?? null,
            ':lastname' => $data['lastname'] ?? null,
            ':company' => $data['company'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':role' => 'user'
        ]);
    }

    /**
     * Benutzer authentifizieren
     */
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last_login
            $updateSql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([':id' => $user['id']]);
            
            return $user;
        }
        
        return false;
    }

    /**
     * Benutzer nach ID abrufen
     */
    public function getUserById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Benutzer aktualisieren
     */
    public function updateUser($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['firstname', 'lastname', 'company', 'phone', 'email'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Email existiert bereits?
     */
    public function emailExists($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->rowCount() > 0;
    }
}
