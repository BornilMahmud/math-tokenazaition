<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

app_require_admin();

$pdo = db();
$phpVersion = PHP_VERSION;
$solverExists = is_file(SOLVER_BINARY);
$dbConnected = $pdo instanceof PDO;
$dbMessage = $dbConnected ? 'Connected to MySQL successfully.' : (db_last_error() ?? 'Database connection failed.');
$userCount = 0;
$adminCount = 0;
$historyCount = 0;
$grammarCount = 0;

if ($dbConnected) {
    app_bootstrap($pdo);
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM app_users')->fetchColumn();
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM app_users WHERE role = 'admin'")->fetchColumn();
    $historyCount = (int) $pdo->query('SELECT COUNT(*) FROM history')->fetchColumn();
    $grammarCount = (int) $pdo->query('SELECT COUNT(*) FROM grammar_rules')->fetchColumn();
}

$currentUser = app_current_user();
$currentName = (string) ($currentUser['display_name'] ?? $currentUser['username'] ?? 'Admin');
$avatar = strtoupper(substr($currentName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status - MathLang Solver</title>
    <link rel="stylesheet" href="<?= APP_URL_BASE ?>/assets/clean-dashboard.css">
</head>
<body>
    <main class="clean-app">
        <section class="clean-shell">
            <aside class="clean-sidebar">
                <div class="clean-logo">
                    <div class="clean-logo-mark"><?= htmlspecialchars($avatar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div>
                        <h1><?= htmlspecialchars($currentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
                        <p>Status</p>
                    </div>
                </div>

                <nav class="clean-nav">
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/index.php">Dashboard</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/history.php">History</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/admin.php">Admin</a>
                    <a class="clean-nav-item active" href="<?= APP_URL_BASE ?>/status.php">Status</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                </nav>
            </aside>

            <div class="clean-content">
                <header class="clean-topbar">
                    <div>
                        <p>Pages / Status</p>
                        <h2>System Health</h2>
                    </div>
                    <div class="clean-top-actions">
                        <span class="role-pill admin">Admin</span>
                        <a class="clean-btn ghost" href="<?= APP_URL_BASE ?>/admin.php">Admin</a>
                    </div>
                </header>

                <section class="clean-stats">
                    <article class="clean-stat-card">
                        <p>PHP Runtime</p>
                        <h3><?= htmlspecialchars($phpVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                        <small class="up">Apache handler</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Database</p>
                        <h3><?= $dbConnected ? 'Connected' : 'Failed' ?></h3>
                        <small class="<?= $dbConnected ? 'up' : 'down' ?>"><?= htmlspecialchars(DB_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Users</p>
                        <h3><?= (int) $userCount ?></h3>
                        <small class="up"><?= (int) $adminCount ?> admin account<?= $adminCount === 1 ? '' : 's' ?></small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Solver</p>
                        <h3><?= $solverExists ? 'Ready' : 'Missing' ?></h3>
                        <small class="<?= $solverExists ? 'up' : 'down' ?>">bin/mathlang_solver.exe</small>
                    </article>
                </section>

                <section class="clean-grid two-col">
                    <article class="clean-card">
                        <header>
                            <h3>Current Connection</h3>
                            <p>Runtime details for the authenticated workspace.</p>
                        </header>
                        <div class="usage-list-clean">
                            <div><span>Database status</span><strong><?= htmlspecialchars($dbMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></div>
                            <div><span>Database name</span><strong><?= htmlspecialchars(DB_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></div>
                            <div><span>History rows</span><strong><?= (int) $historyCount ?></strong></div>
                            <div><span>Grammar rules</span><strong><?= (int) $grammarCount ?></strong></div>
                        </div>
                    </article>

                    <article class="clean-card">
                        <header>
                            <h3>Access</h3>
                            <p>Security and app state.</p>
                        </header>
                        <div class="usage-list-clean">
                            <div><span>Signed in as</span><strong><?= htmlspecialchars($currentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></div>
                            <div><span>Role</span><strong>Admin</strong></div>
                            <div><span>Solver binary</span><strong><?= $solverExists ? 'Detected' : 'Not found' ?></strong></div>
                            <div><span>App mode</span><strong>Clean authenticated UI</strong></div>
                        </div>
                    </article>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
