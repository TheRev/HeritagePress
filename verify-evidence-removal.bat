@echo off
REM Heritage Press Evidence System Removal Verification
REM
REM This batch file runs a complete verification of the Evidence System removal,
REM checking both the database schema and file system to ensure the refactoring
REM was successful.
REM
REM Usage: verify-evidence-removal.bat [wordpress_path]

REM Set colors
set "YELLOW=Yellow"
set "GREEN=Green"
set "RED=Red"
set "CYAN=Cyan"

REM Default WordPress path
set "WP_PATH="

REM Check if WordPress path was provided
if not "%~1"=="" (
    set "WP_PATH=%~1"
    if not exist "%WP_PATH%\wp-load.php" (
        echo [31mX Error: %WP_PATH% does not appear to be a valid WordPress installation[0m
        exit /b 1
    )
) else (
    REM Try to find WordPress
    for %%p in ("..\..\" "..\..\..\" "..\..\..\..") do (
        if exist "%%p\wp-load.php" (
            set "WP_PATH=%%p"
            goto :wordpress_found
        )
    )
    
    echo [31mX Error: Could not locate WordPress installation[0m
    echo Please provide the path to your WordPress installation:
    echo Usage: verify-evidence-removal.bat [wordpress_path]
    exit /b 1
)

:wordpress_found
echo ===================================================
echo [36mHeritage Press Evidence System Removal Verification[0m
echo ===================================================
echo.
echo WordPress found at: %WP_PATH%
echo.

REM Step 1: Run database schema verification
echo [33mStep 1: Verifying database schema...[0m
php admin\tools\heritage-press-schema-verify.php "%WP_PATH%\wp-load.php"

REM Step 2: Run unit tests
echo.
echo [33mStep 2: Running unit tests for Evidence System removal...[0m
if exist ".\vendor\bin\phpunit.bat" (
    call .\vendor\bin\phpunit.bat tests\EvidenceRemovalTest.php
) else if exist ".\vendor\bin\phpunit" (
    php .\vendor\bin\phpunit tests\EvidenceRemovalTest.php
) else (
    echo [31mX PHPUnit not found. Please install dependencies first:[0m
    echo composer install
    exit /b 1
)

REM Step 3: Check for evidence files
echo.
echo [33mStep 3: Checking for Evidence System files...[0m
set "EVIDENCE_FILES_FOUND=0"
for /r %%f in (*.php) do (
    echo "%%f" | findstr /i /c:"evidence" > nul
    if not errorlevel 1 (
        echo "%%f" | findstr /i /c:"remover" /c:"cleanup" > nul
        if errorlevel 1 (
            echo [31mX Evidence system file found: %%f[0m
            set "EVIDENCE_FILES_FOUND=1"
        )
    )
)

if %EVIDENCE_FILES_FOUND%==0 (
    echo [32m√ No Evidence system files found (correct)[0m
) else (
    echo.
    echo Consider running the Evidence File Cleanup utility to remove these files.
)

REM Step 4: Check for option flags in WordPress
echo.
echo [33mStep 4: Checking WordPress option flags...[0m
for /f %%i in ('php -r "require_once('%WP_PATH%\wp-load.php'); $option = get_option('heritage_press_evidence_system_removed'); echo $option === 'yes' ? 'PASS' : 'FAIL';"') do set "OPTION_CHECK=%%i"

if "%OPTION_CHECK%"=="PASS" (
    echo [32m√ Evidence system removal flag is correctly set in WordPress options[0m
) else (
    echo [31mX Evidence system removal flag is not correctly set in WordPress options[0m
    echo Run the following SQL to fix:
    echo UPDATE wp_options SET option_value = 'yes' WHERE option_name = 'heritage_press_evidence_system_removed';
)

REM Step 5: Perform basic functionality test
echo.
echo [33mStep 5: Testing basic genealogy functionality...[0m
for /f "delims=" %%i in ('php -r "require_once('%WP_PATH%\wp-load.php'); require_once('includes/repositories/class-individual-repository.php'); try { $repo = new \HeritagePress\Repositories\Individual_Repository(); $count = $repo->count(); echo \"PASS: Found $count individuals\"; } catch (Exception $e) { echo \"FAIL: \" . $e->getMessage(); }"') do set "FUNCTIONALITY_TEST=%%i"

echo %FUNCTIONALITY_TEST%

REM Final assessment
echo.
echo ===================================================
echo Verification Complete
echo.

echo %FUNCTIONALITY_TEST% | findstr /i /c:"PASS" > nul
set "FUNCTIONALITY_PASS=%errorlevel%"

if %EVIDENCE_FILES_FOUND%==0 if "%OPTION_CHECK%"=="PASS" if %FUNCTIONALITY_PASS%==0 (
    echo [32m✅ All checks passed! The Evidence Explained system has been successfully removed.[0m
    echo Heritage Press is now running as a standard genealogy plugin.
) else (
    echo [33m⚠️ Some checks failed. Please review the output above and take appropriate action.[0m
)
echo ===================================================
