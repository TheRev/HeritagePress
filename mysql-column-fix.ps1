# PowerShell script to add missing columns to HeritagePress database

Write-Host "Adding missing columns to HeritagePress database..." -ForegroundColor Green

# MySQL connection details for MAMP
$mysqlHost = "localhost"
$mysqlUser = "root"
$mysqlPass = "root"
$mysqlDb = "wordpress"

# Try to find MAMP MySQL executable
$possiblePaths = @(
    "C:\MAMP\bin\mysql\mysql8.0.33\bin\mysql.exe",
    "C:\MAMP\bin\mysql\mysql8.0.30\bin\mysql.exe",
    "C:\MAMP\bin\mysql\mysql8.1.0\bin\mysql.exe"
)

$mysqlPath = $null
foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $mysqlPath = $path
        break
    }
}

if (-not $mysqlPath) {
    Write-Host "MySQL executable not found. Please check MAMP installation." -ForegroundColor Red
    exit 1
}

Write-Host "Using MySQL at: $mysqlPath" -ForegroundColor Yellow

# SQL commands to add missing columns
$sqlCommands = @(
    "ALTER TABLE wp_hp_people ADD COLUMN IF NOT EXISTS person_id VARCHAR(50) NOT NULL AFTER gedcom;",
    "ALTER TABLE wp_hp_families ADD COLUMN IF NOT EXISTS family_id VARCHAR(50) NOT NULL AFTER gedcom;",
    "ALTER TABLE wp_hp_sources ADD COLUMN IF NOT EXISTS source_id VARCHAR(50) NOT NULL AFTER gedcom;",
    "ALTER TABLE wp_hp_repositories ADD COLUMN IF NOT EXISTS name VARCHAR(255) NOT NULL AFTER repo_id;",
    "ALTER TABLE wp_hp_media ADD COLUMN IF NOT EXISTS media_id VARCHAR(50) NOT NULL AFTER gedcom;"
)

foreach ($sql in $sqlCommands) {
    Write-Host "Executing: $sql" -ForegroundColor Cyan
    $arguments = "-h$mysqlHost", "-u$mysqlUser", "-p$mysqlPass", $mysqlDb, "-e", $sql
    
    try {
        $result = & $mysqlPath $arguments 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Success" -ForegroundColor Green
        }
        else {
            Write-Host "❌ Error: $result" -ForegroundColor Red
        }
    }
    catch {
        Write-Host "❌ Failed to execute: $_" -ForegroundColor Red
    }
}

Write-Host "`nColumn fixes completed!" -ForegroundColor Green
Write-Host "Testing if person_id column was added..." -ForegroundColor Yellow

# Test if the column was added
$testSql = "SHOW COLUMNS FROM wp_hp_people LIKE 'person_id';"
$testArgs = "-h$mysqlHost", "-u$mysqlUser", "-p$mysqlPass", $mysqlDb, "-e", $testSql

try {
    $testResult = & $mysqlPath $testArgs 2>&1
    if ($testResult -match "person_id") {
        Write-Host "✅ person_id column successfully added!" -ForegroundColor Green
    }
    else {
        Write-Host "⚠️ person_id column may not have been added" -ForegroundColor Yellow
    }
}
catch {
    Write-Host "⚠️ Could not verify column addition" -ForegroundColor Yellow
}

Read-Host "Press Enter to continue..."
