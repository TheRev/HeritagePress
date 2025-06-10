#!/usr/bin/env pwsh
# HeritagePress Plugin Test Script for PowerShell

Write-Host "=== HeritagePress Plugin Test Script ===" -ForegroundColor Green
Write-Host ""

# Check if MAMP is running
Write-Host "Testing WordPress connection..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/wordpress/" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✓ WordPress is accessible (Status: $($response.StatusCode))" -ForegroundColor Green
}
catch {
    Write-Host "✗ WordPress is not accessible. Please ensure MAMP is running." -ForegroundColor Red
    Write-Host "Expected URL: http://localhost/wordpress/" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "Running table verification..." -ForegroundColor Yellow

# Change to plugin directory and run PHP script
Set-Location "c:\MAMP\htdocs\wordpress\wp-content\plugins\heritagepress\HeritagePress"

try {
    $phpPath = "C:\MAMP\bin\php\php8.1.0\php.exe"
    if (Test-Path $phpPath) {
        & $phpPath "simple-table-check.php"
    }
    else {
        Write-Host "✗ PHP not found at: $phpPath" -ForegroundColor Red
        Write-Host "Please check your MAMP installation." -ForegroundColor Yellow
    }
}
catch {
    Write-Host "✗ Error running PHP script: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Go to http://localhost/wordpress/wp-admin/plugins.php" -ForegroundColor White
Write-Host "2. Find 'HeritagePress' and click 'Activate'" -ForegroundColor White
Write-Host "3. Check for any error messages during activation" -ForegroundColor White

Read-Host "Press Enter to continue"
