<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function app_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function app_bootstrap(PDO $pdo): void
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    $bootstrapped = true;

    $pdo->exec('CREATE TABLE IF NOT EXISTS app_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(120) NOT NULL UNIQUE,
        display_name VARCHAR(160) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM("user", "admin") NOT NULL DEFAULT "user",
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $columnExists = (bool) $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'history' AND COLUMN_NAME = 'user_id'")->fetchColumn();
    if (!$columnExists) {
        try {
            $pdo->exec('ALTER TABLE history ADD COLUMN user_id INT NULL AFTER id');
        } catch (Throwable $throwable) {
            // Another request may have already migrated the table.
        }
    }

    $indexExists = (bool) $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'history' AND INDEX_NAME = 'idx_history_user_id'")->fetchColumn();
    if (!$indexExists) {
        try {
            $pdo->exec('ALTER TABLE history ADD INDEX idx_history_user_id (user_id)');
        } catch (Throwable $throwable) {
            // Index creation is optional on older MySQL/MariaDB setups.
        }
    }

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM app_users')->fetchColumn();
    if ($userCount === 0) {
        $statement = $pdo->prepare('INSERT INTO app_users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, :role)');
        $accounts = [
            ['admin', 'Administrator', 'admin123', 'admin'],
            ['user', 'Standard User', 'user123', 'user'],
        ];

        foreach ($accounts as [$username, $displayName, $password, $role]) {
            $statement->execute([
                ':username' => $username,
                ':display_name' => $displayName,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $role,
            ]);
        }
    }
}

function app_current_user(): ?array
{
    app_start_session();

    return isset($_SESSION['app_user']) && is_array($_SESSION['app_user']) ? $_SESSION['app_user'] : null;
}

function app_is_logged_in(): bool
{
    return app_current_user() !== null;
}

function app_is_admin(): bool
{
    return (app_current_user()['role'] ?? '') === 'admin';
}

function app_login_user(array $user): void
{
    app_start_session();
    session_regenerate_id(true);

    $_SESSION['app_user'] = [
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'display_name' => (string) ($user['display_name'] ?? ($user['username'] ?? 'User')),
        'role' => (string) ($user['role'] ?? 'user'),
    ];
}

function app_logout_user(): void
{
    app_start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function app_attempt_login(PDO $pdo, string $username, string $password): ?array
{
    $statement = $pdo->prepare('SELECT id, username, display_name, password_hash, role FROM app_users WHERE username = :username LIMIT 1');
    $statement->execute([':username' => $username]);
    $user = $statement->fetch();

    if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    unset($user['password_hash']);

    return $user;
}

function app_register_user(PDO $pdo, string $username, string $displayName, string $password): array
{
    $username = trim($username);
    $displayName = trim($displayName);

    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'missing'];
    }

    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username)) {
        return ['ok' => false, 'error' => 'username'];
    }

    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'password'];
    }

    if ($displayName === '') {
        $displayName = $username;
    }

    $existsStatement = $pdo->prepare('SELECT COUNT(*) FROM app_users WHERE username = :username');
    $existsStatement->execute([':username' => $username]);
    if ((int) $existsStatement->fetchColumn() > 0) {
        return ['ok' => false, 'error' => 'exists'];
    }

    $insertStatement = $pdo->prepare('INSERT INTO app_users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, :role)');
    $insertStatement->execute([
        ':username' => $username,
        ':display_name' => $displayName,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => 'user',
    ]);

    return ['ok' => true, 'error' => null];
}

function app_require_login(): void
{
    if (!app_is_logged_in()) {
        header('Location: ' . APP_URL_BASE . '/index.php?login=required');
        exit;
    }
}

function app_require_admin(): void
{
    if (!app_is_admin()) {
        header('Location: ' . APP_URL_BASE . '/index.php?login=required');
        exit;
    }
}
