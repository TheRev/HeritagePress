@echo off
echo Starting MAMP and running HeritagePress table verification...
echo.

REM Wait a moment for user to start MAMP if needed
echo Please ensure MAMP is running with Apache and MySQL started.
echo Press any key when MAMP is ready...
pause > nul

echo.
echo Testing WordPress connection...
curl -s -o nul -w "HTTP Status: %%{http_code}" http://localhost/wordpress/
echo.

echo.
echo Running table verification...
cd /d "c:\MAMP\htdocs\wordpress\wp-content\plugins\heritagepress\HeritagePress"
"C:\MAMP\bin\php\php8.1.0\php.exe" simple-table-check.php

echo.
echo Done! Check the output above for table status.
pause
