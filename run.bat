@echo off
echo Starting Study is Funny Educational Platform...
echo.
echo Opening browser to http://localhost:8000
start http://localhost:8000
echo.
echo Server starting on http://localhost:8000
echo Press Ctrl+C to stop the server
echo.
C:\php\php.exe -d extension=fileinfo -d post_max_size=2G -d upload_max_filesize=2G -S localhost:8000