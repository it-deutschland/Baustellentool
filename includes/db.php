<?php
/**
 * Datenbankverbindung via PDO.
 * Wird genau einmal durch bootstrap() aufgerufen.
 */
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Führt eine Query aus und gibt alle Zeilen zurück. */
function db_all(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Führt eine Query aus und gibt die erste Zeile zurück (oder false). */
function db_row(string $sql, array $params = []): array|false {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetch();
}

/** Führt eine Query ohne Rückgabe aus (INSERT/UPDATE/DELETE). */
function db_run(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Gibt die zuletzt eingefügte ID zurück. */
function db_last_id(): int {
    return (int) db()->lastInsertId();
}
