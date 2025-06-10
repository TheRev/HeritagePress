# HeritagePress Cleanup Script
# Run this to clean up development/test files after successful deployment

Write-Host "HeritagePress Workspace Cleanup" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

$pluginDir = "c:\MAMP\htdocs\wordpress\wp-content\plugins\heritagepress\HeritagePress"
Set-Location $pluginDir

Write-Host "Current directory: $pluginDir" -ForegroundColor Yellow
Write-Host ""

# List of test/debug files that can be safely removed after successful deployment
$testFiles = @(
    "debug-activation.php",
    "debug-tables.php", 
    "table-status.php",
    "fix-missing-table.php",
    "sql-debug.php",
    "test-activation.php",
    "test-table-creation.php",
    "test-tables.php",
    "simple-test.php",
    "sql-test.php",
    "php-test.php",
    "create-database.php",
    "db-test.php",
    "check-tables.php",
    "table-check.php",
    "reactivate-plugin.php",
    "activate-test.php",
    "clear-menu-cache.php",
    "manual-activation-test.php",
    "test-plugin.bat",
    "test-plugin.ps1"
)

# Files to keep for future verification
$keepFiles = @(
    "verify-tables.php",
    "direct-db-check.php",
    "simple-table-check.php",
    "SUCCESS-REPORT.md",
    "ACTIVATION-TEST-CHECKLIST.md"
)

Write-Host "Files that can be removed:" -ForegroundColor Red
foreach ($file in $testFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Files to keep for future use:" -ForegroundColor Green
foreach ($file in $keepFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Green
    }
}

Write-Host ""
$response = Read-Host "Do you want to remove the test files? (y/N)"

if ($response -eq 'y' -or $response -eq 'Y') {
    Write-Host ""
    Write-Host "Removing test files..." -ForegroundColor Yellow
    
    $removedCount = 0
    foreach ($file in $testFiles) {
        if (Test-Path $file) {
            try {
                Remove-Item $file -Force
                Write-Host "  ✓ Removed: $file" -ForegroundColor Green
                $removedCount++
            }
            catch {
                Write-Host "  ✗ Failed to remove: $file" -ForegroundColor Red
            }
        }
    }
    
    Write-Host ""
    Write-Host "Cleanup complete! Removed $removedCount files." -ForegroundColor Green
    
    # Show remaining files
    Write-Host ""
    Write-Host "Remaining plugin files:" -ForegroundColor Yellow
    Get-ChildItem -Name "*.php" | Where-Object { $_ -notin $testFiles } | Sort-Object | ForEach-Object {
        Write-Host "  $_" -ForegroundColor White
    }
    
}
else {
    Write-Host ""
    Write-Host "Cleanup cancelled. All files retained." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Plugin is ready for production use!" -ForegroundColor Green
