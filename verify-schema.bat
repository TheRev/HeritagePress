@echo off
REM Heritage Press Schema Verification Tool
REM This batch file helps run the schema verification script

echo Heritage Press Schema Verification Tool
echo =====================================
echo.
echo This tool will check your database schema after removing the Evidence Explained system.
echo.

REM Check if WordPress path is provided
if "%~1"=="" (
    echo Please enter the path to your WordPress root directory:
    set /p WP_PATH=
) else (
    set WP_PATH=%~1
)

REM Ensure path ends with a backslash
if not "%WP_PATH:~-1%"=="\" set WP_PATH=%WP_PATH%\

REM Check if path exists
if not exist "%WP_PATH%wp-load.php" (
    echo Error: Could not find WordPress at the specified path.
    echo Please make sure you provide a valid WordPress root directory.
    echo.
    goto END
)

REM Try to find PHP
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo Error: PHP not found in PATH. Please install PHP or provide the full path.
    echo.
    set /p PHP_PATH=Enter full path to php.exe (e.g., C:\php\php.exe):
) else (
    set PHP_PATH=php
)

REM Check that the verification script exists
set SCRIPT_PATH=%~dp0admin\tools\heritage-press-schema-verify.php
if not exist "%SCRIPT_PATH%" (
    echo Error: Schema verification script not found.
    echo Expected at: %SCRIPT_PATH%
    echo.
    goto END
)

echo.
echo Running schema verification...
echo.

REM Copy the script to the WordPress directory
copy "%SCRIPT_PATH%" "%WP_PATH%heritage-press-schema-verify.php" >nul

REM Run the verification
cd /d "%WP_PATH%"
%PHP_PATH% heritage-press-schema-verify.php

REM Clean up
del "%WP_PATH%heritage-press-schema-verify.php"

:END
echo.
echo Press any key to exit...
pause >nul
