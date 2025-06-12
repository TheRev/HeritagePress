@echo off
echo Adding missing columns to HeritagePress database...

:: MySQL connection details for MAMP
set MYSQL_HOST=localhost
set MYSQL_USER=root
set MYSQL_PASS=root
set MYSQL_DB=wordpress

:: Path to MAMP MySQL executable (adjust if different)
set MYSQL_PATH="C:\MAMP\bin\mysql\mysql8.0.33\bin\mysql.exe"

:: Execute SQL commands
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "ALTER TABLE wp_hp_people ADD COLUMN IF NOT EXISTS person_id VARCHAR(50) NOT NULL AFTER gedcom;"
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "ALTER TABLE wp_hp_families ADD COLUMN IF NOT EXISTS family_id VARCHAR(50) NOT NULL AFTER gedcom;"
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "ALTER TABLE wp_hp_sources ADD COLUMN IF NOT EXISTS source_id VARCHAR(50) NOT NULL AFTER gedcom;"
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "ALTER TABLE wp_hp_repositories ADD COLUMN IF NOT EXISTS name VARCHAR(255) NOT NULL AFTER repo_id;"
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "ALTER TABLE wp_hp_media ADD COLUMN IF NOT EXISTS media_id VARCHAR(50) NOT NULL AFTER gedcom;"

echo Column fixes completed!
echo Testing database connection...
%MYSQL_PATH% -h%MYSQL_HOST% -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% -e "SHOW COLUMNS FROM wp_hp_people LIKE 'person_id';"

pause
