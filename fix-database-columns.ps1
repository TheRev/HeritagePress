# HeritagePress Database Column Fix Script
# This script adds the missing columns directly via MySQL command line

Write-Host "🔧 HeritagePress Database Column Fix"
Write-Host "Adding missing columns for GEDCOM import..."

# MySQL connection details (adjust these if needed)
$mysqlPath = "C:\MAMP\bin\mysql\bin\mysql.exe"
$host = "localhost"
$port = "8889"
$database = "wordpress"
$username = "root"
$password = "root"

# SQL commands to add missing columns
$sqlCommands = @(
    "ALTER TABLE wp_hp_sources ADD COLUMN type VARCHAR(20) NULL AFTER callnum;",
    "ALTER TABLE wp_hp_repositories ADD COLUMN addressID INT NOT NULL DEFAULT 0 AFTER reponame;",
    "ALTER TABLE wp_hp_media ADD COLUMN mediakey VARCHAR(255) NOT NULL DEFAULT '' AFTER mediatypeID;"
)

Write-Host ""
Write-Host "🔗 Connecting to MySQL..."

foreach ($sql in $sqlCommands) {
    Write-Host ""
    Write-Host "⚡ Executing: $sql"
    
    # Execute the SQL command
    $result = & $mysqlPath -h $host -P $port -u $username -p$password $database -e $sql 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
    }
    else {
        Write-Host "❌ ERROR: $result" -ForegroundColor Red
        
        # Check if it's a "duplicate column" error (which is OK)
        if ($result -like "*Duplicate column name*") {
            Write-Host "⚠️  Column already exists - this is OK" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "📋 Verifying table structures..."

# Check final table structures
$tables = @("wp_hp_sources", "wp_hp_repositories", "wp_hp_media")

foreach ($table in $tables) {
    Write-Host ""
    Write-Host "🔍 $table structure:"
    & $mysqlPath -h $host -P $port -u $username -p$password $database -e "DESCRIBE $table;" 2>&1
}

Write-Host ""
Write-Host "🎉 Database fix complete!"
Write-Host "Now try the GEDCOM import again: http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import-export&tab=import"
