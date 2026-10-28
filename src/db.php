<?php
/**
 * Database Connection
 * Returns a PDO instance configured for MySQL/MariaDB with utf8mb4
 */

function db_connect(array $config): PDO
{
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";

    $options = $config['options'] ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

    // Set timezone for the connection
    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}

/**
 * Execute a SELECT query and return all results
 */
function db_select(string $sql, array $params = []): array
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a SELECT query and return a single row
 */
function db_select_one(string $sql, array $params = []): ?array
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Execute an INSERT query and return the last insert ID
 */
function db_insert(string $table, array $data): int
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }

    $columns = array_keys($data);
    $placeholders = ':' . implode(', :', $columns);
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES ({$placeholders})";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = (int)$pdo->lastInsertId();
    if (function_exists('live_bump_for_write')) {
        live_bump_for_write('insert', $table, $data);
    }
    return $id;
}

/**
 * Execute an UPDATE query and return affected rows
 * The WHERE clause uses positional `?` placeholders.
 */
function db_update(string $table, array $data, string $where, array $whereParams = []): int
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }

    $setParts = [];
    $params = [];
    foreach ($data as $col => $value) {
        $setParts[] = "`{$col}` = :set_{$col}";
        $params["set_{$col}"] = $value;
    }

    // Convert positional WHERE placeholders to named ones
    $i = 0;
    $whereNamed = preg_replace_callback('/\?/', function () use (&$i) {
        return ':w' . ($i++);
    }, $where);

    foreach (array_values($whereParams) as $idx => $value) {
        $params['w' . $idx] = $value;
    }

    $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$whereNamed}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    if ($affected > 0 && function_exists('live_bump_for_write')) {
        live_bump_for_write('update', $table, $data);
    }
    return $affected;
}

/**
 * Execute a DELETE query and return affected rows
 */
function db_delete(string $table, string $where, array $params = []): int
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }

    $sql = "DELETE FROM `{$table}` WHERE {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    if ($affected > 0 && function_exists('live_bump_for_write')) {
        live_bump_for_write('delete', $table);
    }
    return $affected;
}

/**
 * Begin a transaction
 */
function db_begin_transaction(): void
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }
    $pdo->beginTransaction();
}

/**
 * Commit a transaction
 */
function db_commit(): void
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }
    $pdo->commit();
}

/**
 * Rollback a transaction
 */
function db_rollback(): void
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        throw new RuntimeException('Database not initialized');
    }
    $pdo->rollBack();
}

/**
 * Execute a callable within a transaction
 */
function db_transaction(callable $callback): mixed
{
    db_begin_transaction();
    try {
        $result = $callback();
        db_commit();
        return $result;
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }
}