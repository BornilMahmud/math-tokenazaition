<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL_BASE . '/index.php');
    exit;
}

$pdo = db();
if (!($pdo instanceof PDO)) {
    header('Location: ' . APP_URL_BASE . '/index.php?error=db');
    exit;
}

app_bootstrap($pdo);

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: ' . APP_URL_BASE . '/index.php?error=missing');
    exit;
}

$user = app_attempt_login($pdo, $username, $password);
if (!is_array($user)) {
    header('Location: ' . APP_URL_BASE . '/index.php?error=invalid');
    exit;
}

app_login_user($user);

header('Location: ' . APP_URL_BASE . '/index.php');
exit;
