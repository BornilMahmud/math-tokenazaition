@echo off
setlocal

set PHP_EXE=
if exist C:\xampp\php\php.exe set PHP_EXE=C:\xampp\php\php.exe
if not defined PHP_EXE if exist "C:\Program Files\xampp\php\php.exe" set PHP_EXE=C:\Program Files\xampp\php\php.exe
if not defined PHP_EXE if exist "C:\Program Files (x86)\xampp\php\php.exe" set PHP_EXE=C:\Program Files (x86)\xampp\php\php.exe

if not defined PHP_EXE (
    where php >nul 2>nul
    if errorlevel 1 (
        echo PHP was not found. Install XAMPP or add php.exe to PATH.
        exit /b 1
    )
    set PHP_EXE=php
)

%PHP_EXE% -S 127.0.0.1:8000 -t web