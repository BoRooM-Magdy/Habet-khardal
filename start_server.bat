@echo off
echo Starting XAMPP MySQL Server...
start "" /B C:\xampp\mysql\bin\mysqld.exe --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone

echo Starting PHP Server on port 8000...
if exist "C:\xampp\php\php.exe" (
    C:\xampp\php\php.exe -c php.ini -d upload_max_filesize=500M -d post_max_size=500M -S localhost:8000 router.php
) else (
    php -c php.ini -d upload_max_filesize=500M -d post_max_size=500M -S localhost:8000 router.php
)
