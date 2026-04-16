<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function run_solver(string $input): array
{
    if (!is_file(SOLVER_BINARY)) {
        return [
            'ok' => false,
            'error' => 'Solver binary was not found. Build the C project first.',
            'suggestion' => 'Run scripts/build.ps1 or scripts/build.bat, then retry.',
            'steps' => [],
            'tokens' => [],
        ];
    }

    $command = escapeshellarg(SOLVER_BINARY) . ' ' . escapeshellarg($input);
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        return [
            'ok' => false,
            'error' => 'Unable to launch the solver process.',
            'suggestion' => 'Check your PHP execution policy and binary permissions.',
            'steps' => [],
            'tokens' => [],
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 && trim($stdout) === '') {
        return [
            'ok' => false,
            'error' => trim($stderr) !== '' ? trim($stderr) : 'Solver exited with an error.',
            'suggestion' => 'Verify the input sentence and make sure the solver binary is up to date.',
            'steps' => [],
            'tokens' => [],
        ];
    }

    $decoded = json_decode($stdout, true);
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'error' => 'Solver output could not be parsed as JSON.',
            'suggestion' => trim($stderr) !== '' ? trim($stderr) : 'Rebuild the solver and try again.',
            'raw_output' => $stdout,
            'steps' => [],
            'tokens' => [],
        ];
    }

    return $decoded;
}
