@echo off
echo Starting Study is Funny Educational Platform...
echo.
echo Opening browser to http://localhost:8000
start http://localhost:8000
echo.
echo Server starting on http://localhost:8000
echo Press Ctrl+C to stop the server
echo.
python -m http.server 8000