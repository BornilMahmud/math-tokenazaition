<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';

app_require_login();

$pdo = db();
$currentUser = app_current_user();
$isAdmin = app_is_admin();
$rows = [];
$recordCount = 0;

if ($pdo instanceof PDO) {
    app_bootstrap($pdo);

    if ($isAdmin) {
        $rows = $pdo->query('SELECT h.input_text, h.output_json, h.result_value, h.created_at, COALESCE(u.display_name, u.username, "Guest") AS author_name FROM history h LEFT JOIN app_users u ON u.id = h.user_id ORDER BY h.created_at DESC, h.id DESC LIMIT 50')->fetchAll();
        $recordCount = (int) $pdo->query('SELECT COUNT(*) FROM history')->fetchColumn();
    } else {
        $statement = $pdo->prepare('SELECT input_text, output_json, result_value, created_at FROM history WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 50');
        $statement->execute([':user_id' => (int) ($currentUser['id'] ?? 0)]);
        $rows = $statement->fetchAll();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM history WHERE user_id = :user_id');
        $countStatement->execute([':user_id' => (int) ($currentUser['id'] ?? 0)]);
        $recordCount = (int) $countStatement->fetchColumn();
    }
}

$currentName = (string) ($currentUser['display_name'] ?? $currentUser['username'] ?? 'User');
$avatar = strtoupper(substr($currentName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - MathLang Solver</title>
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
                        <p>History</p>
                    </div>
                </div>

                <nav class="clean-nav">
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/index.php">Dashboard</a>
                    <a class="clean-nav-item active" href="<?= APP_URL_BASE ?>/history.php">History</a>
                    <?php if ($isAdmin): ?>
                        <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/admin.php">Admin</a>
                        <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/status.php">Status</a>
                    <?php endif; ?>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                </nav>
            </aside>

            <div class="clean-content">
                <header class="clean-topbar">
                    <div>
                        <p>Pages / History</p>
                        <h2><?= $isAdmin ? 'All Solve Records' : 'Your Solve Records' ?></h2>
                    </div>
                    <div class="clean-top-actions">
                        <span class="role-pill <?= $isAdmin ? 'admin' : 'user' ?>"><?= $isAdmin ? 'Admin' : 'User' ?></span>
                        <a class="clean-btn ghost" href="<?= APP_URL_BASE ?>/index.php">Dashboard</a>
                    </div>
                </header>

                <section class="clean-stats">
                    <article class="clean-stat-card">
                        <p>Records</p>
                        <h3><?= (int) $recordCount ?></h3>
                        <small class="up">Stored entries</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Scope</p>
                        <h3><?= $isAdmin ? 'All Users' : 'Mine Only' ?></h3>
                        <small class="up">Based on role</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Status</p>
                        <h3><?= $recordCount > 0 ? 'Active' : 'Empty' ?></h3>
                        <small class="<?= $recordCount > 0 ? 'up' : 'down' ?>"><?= $recordCount > 0 ? 'History available' : 'No solves yet' ?></small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Format</p>
                        <h3>JSON</h3>
                        <small class="up">Detailed solver output</small>
                    </article>
                </section>

                <section class="clean-card">
                    <header>
                        <h3>Execution Log</h3>
                        <p>Recent solver runs and outcomes.</p>
                    </header>
                    <?php if ($rows === []): ?>
                        <p class="muted">No records available yet.</p>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>Input</th>
                                        <th>Result</th>
                                        <th>Time</th>
                                        <?php if ($isAdmin): ?>
                                            <th>User</th>
                                        <?php endif; ?>
                                        <th>Output JSON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['input_text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($row['result_value'] ?? 'N/A'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                            <?php if ($isAdmin): ?>
                                                <td><?= htmlspecialchars((string) ($row['author_name'] ?? 'Guest'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                            <?php endif; ?>
                                            <td><pre><?= htmlspecialchars((string) $row['output_json'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
