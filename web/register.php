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
    header('Location: ' . APP_URL_BASE . '/index.php?register=db');
    exit;
}

app_bootstrap($pdo);

$username = trim((string) ($_POST['username'] ?? ''));
$displayName = trim((string) ($_POST['display_name'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$result = app_register_user($pdo, $username, $displayName, $password);
if (($result['ok'] ?? false) !== true) {
    $error = (string) ($result['error'] ?? 'failed');
    header('Location: ' . APP_URL_BASE . '/index.php?register=' . urlencode($error));
    exit;
}

header('Location: ' . APP_URL_BASE . '/index.php?register=success');
exit;
