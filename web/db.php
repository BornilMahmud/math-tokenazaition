<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db_last_error(?string $message = null): ?string
{
    static $error = null;

    if (func_num_args() > 0) {
        $error = $message;
    }

    return $error;
}

function db(): ?PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $candidates = [
        [DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS],
        [DB_HOST, DB_PORT, DB_NAME, 'root', ''],
        [DB_HOST, DB_PORT, 'mathlang_solver', DB_USER, DB_PASS],
        [DB_HOST, DB_PORT, 'mathlang_solver', 'root', ''],
    ];

    $lastError = null;

    foreach ($candidates as [$host, $port, $database, $user, $password]) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, DB_CHARSET);
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (Throwable $throwable) {
            $lastError = $throwable->getMessage();
        }
    }

    db_last_error($lastError);

    return null;
}
