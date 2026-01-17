Write-Host "Starting Study is Funny Educational Platform..." -ForegroundColor Green
Write-Host ""
Write-Host "Opening browser to http://localhost:8000" -ForegroundColor Yellow
Start-Process "http://localhost:8000"
Write-Host ""
Write-Host "Server starting on http://localhost:8000" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""
python -m http.server 8000