<?php
/**
 * Datenbankverbindungsklasse
 */

namespace RSA21\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private $error;

    /**
     * Singleton Konstruktor
     */
    private function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die('Datenbankverbindung fehlgeschlagen: ' . $this->error);
        }
    }

    /**
     * Singleton getInstance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * PDO Instanz zurückgeben
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Query ausführen
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Fehler zurückgeben
     */
    public function getError() {
        return $this->error;
    }
}
