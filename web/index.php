<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/solver.php';

$pdo = db();
if ($pdo instanceof PDO) {
    app_bootstrap($pdo);
}

$user = app_current_user();
$isLoggedIn = $user !== null;
$isAdmin = $isLoggedIn && (($user['role'] ?? '') === 'admin');
$authError = (string) ($_GET['error'] ?? '');
$registerNotice = (string) ($_GET['register'] ?? '');
$loginNotice = (string) ($_GET['login'] ?? '');
$logoutNotice = isset($_GET['logout']);
$accessDenied = isset($_GET['denied']);

$input = $_POST['input'] ?? 'sum of 5 and 3 multiplied by 2';
$result = null;
$message = null;

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim((string) $input);
    if ($input === '') {
        $message = 'Please enter a math sentence.';
    } else {
        $result = run_solver($input);

        if ($pdo instanceof PDO) {
            $statement = $pdo->prepare('INSERT INTO history (user_id, input_text, output_json, result_value) VALUES (:user_id, :input_text, :output_json, :result_value)');
            $statement->execute([
                ':user_id' => (int) ($user['id'] ?? 0),
                ':input_text' => $input,
                ':output_json' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ':result_value' => isset($result['result']) ? (string) $result['result'] : null,
            ]);
        }
    }
}

$historyRows = [];
$recentCount = 0;
$todaySolves = 0;
$ruleCount = 0;
$userCount = 0;
$grammarCount = 0;

if ($isLoggedIn && $pdo instanceof PDO) {
    $grammarCount = (int) $pdo->query('SELECT COUNT(*) FROM grammar_rules')->fetchColumn();

    if ($isAdmin) {
        $historyRows = $pdo->query('SELECT h.input_text, h.result_value, h.created_at, COALESCE(u.display_name, u.username, "Guest") AS author_name, COALESCE(u.role, "user") AS author_role FROM history h LEFT JOIN app_users u ON u.id = h.user_id ORDER BY h.created_at DESC, h.id DESC LIMIT 6')->fetchAll();
        $recentCount = (int) $pdo->query('SELECT COUNT(*) FROM history')->fetchColumn();
        $todaySolves = (int) $pdo->query('SELECT COUNT(*) FROM history WHERE DATE(created_at) = CURDATE()')->fetchColumn();
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM app_users')->fetchColumn();
        $ruleCount = (int) $pdo->query('SELECT COUNT(*) FROM grammar_rules')->fetchColumn();
    } else {
        $statement = $pdo->prepare('SELECT input_text, result_value, created_at FROM history WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 6');
        $statement->execute([':user_id' => (int) $user['id']]);
        $historyRows = $statement->fetchAll();
        $recentCount = (int) $pdo->prepare('SELECT COUNT(*) FROM history WHERE user_id = :user_id')->execute([':user_id' => (int) $user['id']]);
    }
}

if ($isLoggedIn && !$isAdmin && $pdo instanceof PDO) {
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM history WHERE user_id = :user_id');
    $countStatement->execute([':user_id' => (int) $user['id']]);
    $recentCount = (int) $countStatement->fetchColumn();
    $todayStatement = $pdo->prepare('SELECT COUNT(*) FROM history WHERE user_id = :user_id AND DATE(created_at) = CURDATE()');
    $todayStatement->execute([':user_id' => (int) $user['id']]);
    $todaySolves = (int) $todayStatement->fetchColumn();
    $ruleCount = (int) $pdo->query('SELECT COUNT(*) FROM grammar_rules')->fetchColumn();
}

$tokens = is_array($result['tokens'] ?? null) ? $result['tokens'] : [];
$steps = is_array($result['steps'] ?? null) ? $result['steps'] : [];
$tree = (string) ($result['tree'] ?? '');
$expression = (string) ($result['expression'] ?? '');
$error = (string) ($result['error'] ?? '');
$suggestion = (string) ($result['suggestion'] ?? '');
$ok = (bool) ($result['ok'] ?? false);
$currentName = $isLoggedIn ? (string) ($user['display_name'] ?? $user['username'] ?? 'User') : 'Guest';
$avatar = strtoupper(substr($currentName, 0, 1));
$roleLabel = $isAdmin ? 'Admin' : 'User';
$loginMessage = null;
$loginMessageType = 'error';
if ($authError === 'invalid') {
    $loginMessage = 'Invalid name or password.';
} elseif ($authError === 'missing') {
    $loginMessage = 'Enter both name and password.';
} elseif ($authError === 'db') {
    $loginMessage = 'Database is not available right now.';
} elseif ($loginNotice === 'required') {
    $loginMessage = 'Please sign in to continue.';
} elseif ($accessDenied) {
    $loginMessage = 'Admin access is required for that page.';
} elseif ($logoutNotice) {
    $loginMessage = 'You have been signed out.';
    $loginMessageType = 'info';
}

if ($registerNotice === 'success') {
    $loginMessage = 'Account created. You can now sign in.';
    $loginMessageType = 'success';
} elseif ($registerNotice === 'missing') {
    $loginMessage = 'Enter username and password to register.';
} elseif ($registerNotice === 'username') {
    $loginMessage = 'Username must be 3-40 characters: letters, numbers, dot, underscore, or dash.';
} elseif ($registerNotice === 'password') {
    $loginMessage = 'Password must be at least 6 characters.';
} elseif ($registerNotice === 'exists') {
    $loginMessage = 'That username is already taken.';
} elseif ($registerNotice === 'db') {
    $loginMessage = 'Database is not available right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathLang Solver</title>
    <link rel="stylesheet" href="<?= APP_URL_BASE ?>/assets/clean-dashboard.css">
</head>
<body>
<?php if (!$isLoggedIn): ?>
    <main class="auth-app">
        <section class="auth-shell">
            <div class="auth-hero">
                <div class="auth-logo-wrap">
                    <img class="auth-logo-img" src="<?= APP_URL_BASE ?>/assets/mathsolver-logo.svg" alt="MathSolver logo">
                    <div class="clean-logo-mark auth-mark">M</div>
                </div>
                <p class="eyebrow">Math Solver</p>
                <h1>Simple access for solving.</h1>
                <p class="auth-copy">Sign in or create a user account to start solving and save your history.</p>
            </div>

            <div class="auth-card">
                <header>
                    <h2>Sign in</h2>
                    <p>Use your account name and password.</p>
                </header>

                <?php if ($loginMessage !== null): ?>
                    <p class="auth-alert <?= $loginMessageType === 'success' ? 'success' : ($loginMessageType === 'info' ? 'info' : '') ?>"><?= htmlspecialchars($loginMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <?php endif; ?>

                <form method="post" action="<?= APP_URL_BASE ?>/login.php" class="auth-form">
                    <label>
                        <span>Name</span>
                        <input type="text" name="username" autocomplete="username" placeholder="admin or user" required>
                    </label>
                    <label>
                        <span>Password</span>
                        <input type="password" name="password" autocomplete="current-password" placeholder="Your password" required>
                    </label>
                    <button class="clean-btn primary auth-submit" type="submit">Log in</button>
                </form>

                <div class="auth-divider"><span>or</span></div>

                <form method="post" action="<?= APP_URL_BASE ?>/register.php" class="auth-form auth-register-form">
                    <label>
                        <span>New username</span>
                        <input type="text" name="username" autocomplete="username" placeholder="choose a username" required>
                    </label>
                    <label>
                        <span>Display name (optional)</span>
                        <input type="text" name="display_name" autocomplete="name" placeholder="your display name">
                    </label>
                    <label>
                        <span>New password</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="minimum 6 characters" required>
                    </label>
                    <button class="clean-btn ghost auth-submit" type="submit">Create account</button>
                </form>

                <div class="auth-seeds">
                    <div><strong>Admin</strong><span>admin / admin123</span></div>
                    <div><strong>User</strong><span>user / user123</span></div>
                </div>
            </div>
        </section>
    </main>
<?php else: ?>
    <main class="clean-app">
        <section class="clean-shell">
            <aside class="clean-sidebar">
                <div class="clean-logo">
                    <div class="clean-logo-mark"><?= htmlspecialchars($avatar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div>
                        <h1><?= htmlspecialchars($currentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
                        <p><?= htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    </div>
                </div>

                <nav class="clean-nav">
                    <a class="clean-nav-item active" href="#top-dashboard" data-section="top-dashboard">Dashboard</a>
                    <a class="clean-nav-item" href="#solver-panel" data-section="solver-panel">Solve Problem</a>
                    <a class="clean-nav-item" href="#history-panel" data-section="history-panel">History</a>
                    <?php if ($isAdmin): ?>
                        <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/admin.php">Admin</a>
                        <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/status.php">System Status</a>
                    <?php endif; ?>
                    <a class="clean-nav-item" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                </nav>
            </aside>

            <div class="clean-content">
                <header id="top-dashboard" class="clean-topbar">
                    <div>
                        <p>Signed in as <?= htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <h2>Math Solver Dashboard</h2>
                    </div>
                    <div class="clean-top-actions">
                        <a class="clean-btn soft mini" href="#solver-panel">New Solve</a>
                        <a class="clean-btn ghost mini" href="#history-panel">Jump History</a>
                        <span class="role-pill <?= $isAdmin ? 'admin' : 'user' ?>"><?= htmlspecialchars($currentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <a class="clean-btn ghost" href="<?= APP_URL_BASE ?>/logout.php">Logout</a>
                    </div>
                </header>

                <section class="clean-stats">
                    <article class="clean-stat-card">
                        <p>Your Solves</p>
                        <h3><?= (int) $recentCount ?></h3>
                        <small class="up">Saved in history</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Today</p>
                        <h3><?= (int) $todaySolves ?></h3>
                        <small class="up">This session's activity</small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Role</p>
                        <h3><?= htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                        <small class="up"><?= $isAdmin ? 'Full access enabled' : 'Solver access enabled' ?></small>
                    </article>
                    <article class="clean-stat-card">
                        <p>Grammar Rules</p>
                        <h3><?= (int) $ruleCount ?></h3>
                        <small class="up">Current compiler rules</small>
                    </article>
                </section>

                <section class="clean-card clean-banner">
                    <div>
                        <p class="eyebrow">Workspace</p>
                        <h3>Keep the screen focused on solving and management.</h3>
                        <p class="muted">Unused analytics and clutter were removed so the interface stays simple and responsive.</p>
                    </div>
                    <a class="clean-btn primary" href="#solver-panel">Start Solving</a>
                </section>

                <section id="solver-panel" class="clean-grid two-col">
                    <article class="clean-card">
                        <header>
                            <h3>Solver Input</h3>
                            <p>Enter a math sentence or expression.</p>
                        </header>
                        <form method="post" class="clean-form">
                            <textarea id="input" name="input" placeholder="Enter math expression (e.g., (5+3)*2)"><?= htmlspecialchars((string) $input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                            <div class="clean-actions">
                                <button class="clean-btn primary" type="submit">Solve</button>
                                <button class="clean-btn ghost" type="button" id="clear-solver">Clear Input</button>
                                <a class="clean-btn ghost" href="#history-panel">View History</a>
                            </div>
                        </form>
                        <?php if ($message !== null): ?>
                            <p class="state-line error"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </article>

                    <article class="clean-card">
                        <header class="clean-card-head">
                            <div>
                                <h3>Output</h3>
                                <p>Result and explanation.</p>
                            </div>
                            <button class="clean-btn ghost mini" type="button" id="copy-output">Copy Output</button>
                        </header>

                        <?php if ($result !== null): ?>
                            <div class="result-chip <?= $ok ? 'ok' : 'error' ?>"><?= $ok ? 'Solved' : 'Failed' ?></div>
                            <div class="result-value"><?= htmlspecialchars($ok ? (string) $result['result'] : 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                            <div class="clean-mini-grid">
                                <div class="clean-mini">
                                    <h4>Expression</h4>
                                    <pre><?= htmlspecialchars($expression !== '' ? $expression : 'Not generated', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                                </div>
                                <div class="clean-mini">
                                    <h4>Tokens</h4>
                                    <pre><?= htmlspecialchars($tokens === [] ? 'No tokens' : implode(PHP_EOL, $tokens), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                                </div>
                            </div>
                            <div class="clean-mini">
                                <h4>Step-by-step</h4>
                                <?php if ($steps === []): ?>
                                    <p class="muted">No evaluation steps available.</p>
                                <?php else: ?>
                                    <ol class="clean-steps">
                                        <?php foreach ($steps as $step): ?>
                                            <li><?= htmlspecialchars((string) $step, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php endif; ?>
                            </div>
                            <div class="clean-mini">
                                <h4>AST</h4>
                                <pre><?= htmlspecialchars($tree !== '' ? $tree : 'No AST generated.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                            </div>
                            <?php if (!$ok): ?>
                                <div class="clean-mini">
                                    <h4>Error</h4>
                                    <pre><?= htmlspecialchars($error !== '' ? $error : 'Unknown error', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                                    <?php if ($suggestion !== ''): ?>
                                        <p class="muted"><strong>Suggestion:</strong> <?= htmlspecialchars($suggestion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="result-value">--</div>
                            <p class="muted">Run a query to see output details.</p>
                        <?php endif; ?>
                    </article>
                </section>

                <section id="history-panel" class="clean-card">
                    <header>
                        <h3>Recent Solves</h3>
                        <p><?= $isAdmin ? 'All users' : 'Your last entries' ?></p>
                    </header>
                    <?php if ($historyRows === []): ?>
                        <p class="muted">No history entries yet.</p>
                    <?php else: ?>
                        <div class="history-toolbar">
                            <input id="history-filter" type="search" placeholder="Filter by text, result, date, or user">
                        </div>
                        <div class="recent-clean">
                            <?php foreach ($historyRows as $row): ?>
                                <div class="recent-clean-item" data-history-item>
                                    <p><?= htmlspecialchars((string) $row['input_text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                    <span>
                                        <?= htmlspecialchars((string) ($row['result_value'] ?? 'N/A'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ·
                                        <?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        <?php if ($isAdmin): ?>
                                            · <?= htmlspecialchars((string) ($row['author_name'] ?? 'Guest'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var navLinks = Array.prototype.slice.call(document.querySelectorAll('.clean-nav-item[data-section]'));
    var sections = navLinks
        .map(function (link) {
            return document.getElementById(link.getAttribute('data-section') || '');
        })
        .filter(function (section) {
            return section !== null;
        });

    function setActiveNav(sectionId) {
        navLinks.forEach(function (link) {
            if (link.getAttribute('data-section') === sectionId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            var targetId = link.getAttribute('data-section');
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setActiveNav(targetId);
        });
    });

    if (sections.length > 0 && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && entry.intersectionRatio >= 0.35) {
                    setActiveNav(entry.target.id);
                }
            });
        }, {
            threshold: [0.35, 0.7],
            rootMargin: '-20% 0px -55% 0px'
        });

        sections.forEach(function (section) {
            observer.observe(section);
        });
    }

    var filterInput = document.getElementById('history-filter');
    if (filterInput) {
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-history-item]'));
        filterInput.addEventListener('input', function () {
            var term = filterInput.value.trim().toLowerCase();
            items.forEach(function (item) {
                var text = (item.textContent || '').toLowerCase();
                item.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    var clearButton = document.getElementById('clear-solver');
    var inputArea = document.getElementById('input');
    if (clearButton && inputArea) {
        clearButton.addEventListener('click', function () {
            inputArea.value = '';
            inputArea.focus();
        });
    }

    var copyButton = document.getElementById('copy-output');
    if (copyButton) {
        copyButton.addEventListener('click', function () {
            var outputParts = [];
            var valueNode = document.querySelector('.result-value');
            var exprNode = document.querySelector('.clean-mini pre');

            if (valueNode) {
                outputParts.push('Result: ' + valueNode.textContent.trim());
            }

            if (exprNode) {
                outputParts.push('Expression: ' + exprNode.textContent.trim());
            }

            var payload = outputParts.join('\n');
            if (payload === '') {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(payload).then(function () {
                    copyButton.textContent = 'Copied';
                    window.setTimeout(function () {
                        copyButton.textContent = 'Copy Output';
                    }, 1300);
                });
            }
        });
    }
});
</script>
</body>
</html>
