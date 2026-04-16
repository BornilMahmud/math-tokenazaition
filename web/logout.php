<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';

app_logout_user();

header('Location: ' . APP_URL_BASE . '/index.php?logout=1');
exit;
