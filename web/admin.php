<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';

app_require_admin();

$pdo = db();
$statusMessage = null;
$statusType = 'status-ok';

if (!($pdo instanceof PDO)) {
    $rules = [];
    $users = [];
    $statusMessage = 'Database connection failed. Check web/config.php and MySQL service availability.';
    $statusType = 'status-error';
} else {
    app_bootstrap($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'save-rule') {
                $keyword = trim((string) ($_POST['keyword'] ?? ''));
                $operation = trim((string) ($_POST['operation'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));

                if ($keyword === '' || $operation === '') {
                    throw new RuntimeException('Keyword and operation are required.');
                }

                $statement = $pdo->prepare('INSERT INTO grammar_rules (keyword, operation, description) VALUES (:keyword, :operation, :description) ON DUPLICATE KEY UPDATE operation = VALUES(operation), description = VALUES(description)');
                $statement->execute([
                    ':keyword' => $keyword,
                    ':operation' => $operation,
                    ':description' => $description !== '' ? $description : null,
                ]);
                $statusMessage = 'Grammar rule saved.';
            } elseif ($action === 'delete-rule') {
                $ruleId = (int) ($_POST['rule_id'] ?? 0);
                if ($ruleId <= 0) {
                    throw new RuntimeException('Invalid rule identifier.');
                }
                $statement = $pdo->prepare('DELETE FROM grammar_rules WHERE id = :id');
                $statement->execute([':id' => $ruleId]);
                $statusMessage = 'Grammar rule deleted.';
            } elseif ($action === 'create-user') {
                $username = trim((string) ($_POST['username'] ?? ''));
                $displayName = trim((string) ($_POST['display_name'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $role = (string) ($_POST['role'] ?? 'user');

                if ($username === '' || $displayName === '' || $password === '') {
                    throw new RuntimeException('All user fields are required.');
                }

                if (!in_array($role, ['user', 'admin'], true)) {
                    $role = 'user';
                }

                $statement = $pdo->prepare('INSERT INTO app_users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, :role)');
                $statement->execute([
                    ':username' => $username,
                    ':display_name' => $displayName,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role,
                ]);
                $statusMessage = 'User account created.';
            } elseif ($action === 'save-user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                $displayName = trim((string) ($_POST['display_name'] ?? ''));
                $role = (string) ($_POST['role'] ?? 'user');
                $password = (string) ($_POST['password'] ?? '');

                if ($userId <= 0 || $displayName === '') {
                    throw new RuntimeException('Display name is required.');
                }

                if (!in_array($role, ['user', 'admin'], true)) {
                    $role = 'user';
                }

                $currentUser = app_current_user();
                if ((int) ($currentUser['id'] ?? 0) === $userId && $role !== 'admin') {
                    $role = 'admin';
                }

                if ($password !== '') {
                    $statement = $pdo->prepare('UPDATE app_users SET display_name = :display_name, role = :role, password_hash = :password_hash WHERE id = :id');
                    $statement->execute([
                        ':display_name' => $displayName,
                        ':role' => $role,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':id' => $userId,
                    ]);
                } else {
                    $statement = $pdo->prepare('UPDATE app_users SET display_name = :display_name, role = :role WHERE id = :id');
                    $statement->execute([
                        ':display_name' => $displayName,
                        ':role' => $role,
                        ':id' => $userId,
                    ]);
                }

                $statusMessage = 'User account updated.';
            } else {
                throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $throwable) {
            $statusMessage = $throwable->getMessage();
            $statusType = 'status-error';
        }
    }

    $rules = $pdo->query('SELECT id, keyword, operation, description, created_at FROM grammar_rules ORDER BY keyword ASC')->fetchAll();
    $users = $pdo->query('SELECT id, username, display_name, role, created_at FROM app_users ORDER BY role DESC, username ASC')->fetchAll();
}

$rulesCount = is_array($rules) ? count($rules) : 0;
$usersCount = is_array($users) ? count($users) : 0;
$adminCount = 0;
foreach ($users as $userRow) {
    if (($userRow['role'] ?? '') === 'admin') {
        $adminCount++;
    }
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
    <title>Admin - MathLang Solver</title>
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
                        <p>Admin tools</p>
                    </div>
                </div>

                <nav class="clean-nav">
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/index.php">Dashboard</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/history.php">History</a>
                    <a class="clean-nav-item active" href="<?= APP_URL_BASE ?>/admin.php">Admin</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/status.php">Status</a>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                </nav>
            </aside>

            <div class="clean-content">
                <header class="clean-topbar">
                    <div>
                        <p>Pages / Admin</p>
                        <h2>Manage Rules and Users</h2>
                    </div>
                    <div class="clean-top-actions">
                        <span class="role-pill admin">Admin</span>
                        <a class="clean-btn ghost" href="<?= APP_URL_BASE ?>/index.php">Dashboard</a>
                        <a class="clean-btn primary" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                    </div>
                </header>

                <section class="clean-stats">
                    <article class="clean-stat-card">
                        <p>Users</p>
                        <h3><?= (int) $usersCount ?></h3>
                        <small class="up">Accounts registered</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Admins</p>
                        <h3><?= (int) $adminCount ?></h3>
                        <small class="up">Privileged access</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Rules</p>
                        <h3><?= (int) $rulesCount ?></h3>
                        <small class="up">Grammar keywords</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Control</p>
                        <h3>On</h3>
                        <small class="up">User and rule editing enabled</small>
                    </article>
                </section>

                <?php if ($statusMessage !== null): ?>
                    <div class="clean-card">
                        <p class="state-line <?= $statusType === 'status-error' ? 'error' : '' ?>" style="margin: 0;"><?= htmlspecialchars($statusMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <section class="clean-grid two-col">
                    <article class="clean-card">
                        <header>
                            <h3>Grammar Rules</h3>
                            <p>Update solver keyword mappings.</p>
                        </header>
                        <form method="post" class="clean-form">
                            <input type="hidden" name="action" value="save-rule">
                            <input type="text" name="keyword" placeholder="Keyword (e.g., sum)">
                            <input type="text" name="operation" placeholder="Operation (e.g., add)">
                            <textarea name="description" placeholder="Description"></textarea>
                            <div class="clean-actions">
                                <button class="clean-btn primary" type="submit">Save Rule</button>
                            </div>
                        </form>

                        <?php if ($rules === []): ?>
                            <p class="muted" style="margin-top: 14px;">No rules stored yet.</p>
                        <?php else: ?>
                            <div class="table-scroll">
                                <table class="clean-table">
                                    <thead>
                                        <tr>
                                            <th>Keyword</th>
                                            <th>Operation</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rules as $rule): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) $rule['keyword'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars((string) $rule['operation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars((string) ($rule['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                                <td>
                                                    <form method="post" onsubmit="return confirm('Delete this rule?');">
                                                        <input type="hidden" name="action" value="delete-rule">
                                                        <input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>">
                                                        <button class="clean-btn ghost" type="submit">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="clean-card">
                        <header>
                            <h3>Users</h3>
                            <p>Create accounts and change roles or passwords.</p>
                        </header>

                        <form method="post" class="clean-form">
                            <input type="hidden" name="action" value="create-user">
                            <input type="text" name="username" placeholder="Username">
                            <input type="text" name="display_name" placeholder="Display name">
                            <input type="password" name="password" placeholder="Password">
                            <select name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="clean-actions">
                                <button class="clean-btn primary" type="submit">Create User</button>
                            </div>
                        </form>

                        <div class="user-grid" style="margin-top: 14px;">
                            <?php foreach ($users as $userRow): ?>
                                <article class="user-card">
                                    <header>
                                        <div>
                                            <h4><?= htmlspecialchars((string) $userRow['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h4>
                                            <p>Created <?= htmlspecialchars((string) $userRow['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                        </div>
                                        <span class="role-pill <?= ($userRow['role'] ?? '') === 'admin' ? 'admin' : 'user' ?>"><?= htmlspecialchars((string) $userRow['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                    </header>

                                    <form method="post">
                                        <input type="hidden" name="action" value="save-user">
                                        <input type="hidden" name="user_id" value="<?= (int) $userRow['id'] ?>">
                                        <input type="text" name="display_name" value="<?= htmlspecialchars((string) $userRow['display_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Display name">
                                        <select name="role">
                                            <option value="user"<?= ($userRow['role'] ?? '') === 'user' ? ' selected' : '' ?>>User</option>
                                            <option value="admin"<?= ($userRow['role'] ?? '') === 'admin' ? ' selected' : '' ?>>Admin</option>
                                        </select>
                                        <input type="password" name="password" placeholder="New password (optional)">
                                        <div class="clean-actions">
                                            <button class="clean-btn primary" type="submit">Save</button>
                                        </div>
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
