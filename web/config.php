<?php

declare(strict_types=1);

define('DB_HOST', getenv('MATHSOLVER_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('MATHSOLVER_DB_PORT') ?: '3306');
define('DB_NAME', getenv('MATHSOLVER_DB_NAME') ?: 'mathsolver_db');
define('DB_USER', getenv('MATHSOLVER_DB_USER') ?: 'mathsolver');
define('DB_PASS', getenv('MATHSOLVER_DB_PASS') ?: '');
define('DB_CHARSET', getenv('MATHSOLVER_DB_CHARSET') ?: 'utf8mb4');
define('APP_URL_BASE', getenv('MATHSOLVER_APP_BASE') ?: '');

define('SOLVER_BINARY', realpath(__DIR__ . '/../bin/mathlang_solver.exe') ?: (__DIR__ . '/../bin/mathlang_solver.exe'));
