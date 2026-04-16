$ErrorActionPreference = 'Stop'

$candidatePhpPaths = @(
    'C:\\xampp\\php\\php.exe',
    'C:\\Program Files\\xampp\\php\\php.exe',
    'C:\\Program Files (x86)\\xampp\\php\\php.exe'
)

$php = $candidatePhpPaths | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $php) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        $php = $phpCommand.Source
    }
}

if (-not $php) {
    throw 'PHP was not found. Install XAMPP or add php.exe to PATH.'
}

Write-Host "Using PHP at: $php"
Push-Location $PSScriptRoot
try {
    & $php -S 127.0.0.1:8000 -t ..\web
} finally {
    Pop-Location
}