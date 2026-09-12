<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * PDO singleton wrapper.
 */
class Database
{
    private static ?self $instance = null;

    private ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function getInstance(): static
    {
        return self::$instance ??= new static();
    }

    /**
     * @throws RuntimeException
     */
    public function connect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        try {
            $host = (string) DB_HOST;
            $database = (string) DB_NAME;
            $charset = defined('DB_CHARSET') ? (string) DB_CHARSET : 'utf8mb4';
            $port = defined('DB_PORT') ? ';port=' . (string) DB_PORT : '';
            $dsn = sprintf('mysql:host=%s%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
            $this->connection = new PDO($dsn, (string) DB_USER, (string) DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            Logger::error('Database connection failed.', ['exception' => $exception->getMessage()]);
            throw new RuntimeException('Unable to connect to the database.', 0, $exception);
        }

        return $this->connection;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>|false
     */
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() >= 0;
    }

    public function lastInsertId(): string
    {
        return $this->connect()->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    public function rollback(): bool
    {
        return $this->connect()->rollBack();
    }

    public function table(string $name): QueryBuilder
    {
        return new QueryBuilder($this, $name);
    }
}
