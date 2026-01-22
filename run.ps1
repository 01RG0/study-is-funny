# Study is Funny - Complete Project Runner
# Works on Windows, macOS, and Linux
# Usage: ./run.ps1 or ./run.ps1 8080 or ./run.ps1 admin

param(
    [Parameter(Position = 0)]
    [string]$Argument = "",
    
    [switch]$NoOpen
)

function Start-ProjectServer {
    param(
        [string]$Port = "8000",
        [string]$UrlPath = "",
        [bool]$OpenBrowser = $true
    )

    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Study is Funny - Educational Platform" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""

    # Detect OS and use appropriate Python server
    $isPython3 = $false
    $pythonCmd = ""
    
    # Try python3 first, then python
    try {
        $pythonCmd = (Get-Command python3 -ErrorAction Stop).Source
        $isPython3 = $true
    }
    catch {
        try {
            $pythonCmd = (Get-Command python -ErrorAction Stop).Source
        }
        catch {
            Write-Host "ERROR: Python is not installed!" -ForegroundColor Red
            Write-Host "Please install Python 3 from https://www.python.org" -ForegroundColor Yellow
            Read-Host "Press Enter to exit"
            exit 1
        }
    }

    # Check if port is already in use
    $portInUse = $false
    try {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, [int]$Port)
        $listener.Start()
        $listener.Stop()
    }
    catch {
        $portInUse = $true
        Write-Host "ERROR: Port $Port is already in use!" -ForegroundColor Red
        Write-Host "Try using a different port:" -ForegroundColor Yellow
        Write-Host "   ./run.ps1 8080" -ForegroundColor White
        Read-Host "Press Enter to exit"
        exit 1
    }

    Write-Host "Server Configuration:" -ForegroundColor Green
    Write-Host "  Python: $pythonCmd" -ForegroundColor White
    Write-Host "  Port: $Port" -ForegroundColor White
    Write-Host "  Directory: $(Get-Location)" -ForegroundColor White
    
    if ($UrlPath) {
        Write-Host "  Opening: $UrlPath" -ForegroundColor White
    }
    Write-Host ""
    Write-Host "Available URLs:" -ForegroundColor Green
    Write-Host "  * Main App: http://localhost:$Port" -ForegroundColor White
    Write-Host "  * Admin Dashboard: http://localhost:$Port/admin/dashboard.html" -ForegroundColor White
    Write-Host "  * Student Portal: http://localhost:$Port/student/index.html" -ForegroundColor White
    Write-Host "  * QR Scanner: http://localhost:$Port/qr-scanner.html" -ForegroundColor White
    Write-Host "  * Tests: http://localhost:$Port/tests" -ForegroundColor White
    Write-Host ""
    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
    Write-Host ""

    # Open browser if not disabled
    if ($OpenBrowser) {
        $urls = @{
            "admin"       = "/admin/dashboard.html"
            "student"     = "/student/index.html"
            "qr"          = "/qr-scanner.html"
            "test-qr"     = "/tests/test-grade-qr.html"
            "test-mongo"  = "/tests/test-mongodb.html"
            "tests"       = "/tests"
            ""            = ""
        }
        
        $openUrl = "http://localhost:$Port"
        if ($urls.ContainsKey($UrlPath)) {
            $openUrl += $urls[$UrlPath]
        }

        # Open browser asynchronously
        Start-Job -ScriptBlock {
            param($Url)
            Start-Sleep -Seconds 1
            Start-Process $Url
        } -ArgumentList $openUrl | Out-Null

        Write-Host "Opening browser to $openUrl..." -ForegroundColor Cyan
    }

    Write-Host ""

    # Start Python server
    $env:PYTHONUNBUFFERED = 1
    
    if ($isPython3) {
        & python3 -m http.server $Port
    }
    else {
        & python -m http.server $Port
    }
}

# Main execution
try {
    $Port = "8000"
    $UrlPath = ""
    $OpenBrowser = -not $NoOpen

    # Parse arguments
    if ($Argument) {
        if ($Argument -match '^\d+$') {
            # It's a port number
            $Port = $Argument
        }
        else {
            # It's a URL path (admin, student, qr, etc.)
            $UrlPath = $Argument
        }
    }

    Start-ProjectServer -Port $Port -UrlPath $UrlPath -OpenBrowser $OpenBrowser
}
catch {
    Write-Host "An error occurred: $_" -ForegroundColor Red
    exit 1
}
finally {
    Write-Host ""
    Write-Host "Server stopped." -ForegroundColor Yellow
}