# 🚀 Running Study is Funny

This project has **ONE single way to run it** - the `run.ps1` PowerShell script that works on all platforms.

## Quick Start

### Windows, macOS, or Linux:
```powershell
./run.ps1
```

The server will start on **http://localhost:8000** and automatically open in your browser.

---

## Usage Options

### 1. Basic Run (Default)
```powershell
./run.ps1
```
- Starts server on port 8000
- Opens main app in browser

### 2. Run with Custom Port
```powershell
./run.ps1 8080
```
- Starts server on port 8080 instead of 8000

### 3. Run and Open Specific Page
```powershell
./run.ps1 admin          # Opens Admin Dashboard
./run.ps1 student        # Opens Student Portal
./run.ps1 qr             # Opens QR Scanner
./run.ps1 test-mongo     # Opens MongoDB Test
./run.ps1 tests          # Opens Tests Directory
```

### 4. Run Without Opening Browser
```powershell
./run.ps1 -NoOpen
```

### 5. Combine Options
```powershell
./run.ps1 8080 -NoOpen   # Custom port, no browser
```

---

## Requirements

- **Python 3.6+** installed and in your PATH
  - Windows: Download from https://www.python.org
  - macOS: `brew install python3`
  - Linux: `apt-get install python3`

## Troubleshooting

### "Port 8000 is already in use"
Use a different port:
```powershell
./run.ps1 8080
```

### "Python is not installed"
1. Install Python from https://www.python.org
2. Make sure to add Python to PATH during installation
3. Restart your terminal

### PowerShell Execution Policy Error
If you get an error about execution policies, run:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

---

## Available URLs

Once running, access:
- 🏠 **Main App**: http://localhost:8000
- 📊 **Admin Dashboard**: http://localhost:8000/admin/dashboard.html
- 👨‍🎓 **Student Portal**: http://localhost:8000/student/index.html
- 📱 **QR Scanner**: http://localhost:8000/qr-scanner.html
- 🧪 **Tests**: http://localhost:8000/tests

---

## What This Script Does

1. ✅ Validates Python installation
2. ✅ Checks if port is available
3. ✅ Displays server configuration
4. ✅ Shows all available URLs
5. ✅ Automatically opens browser (unless disabled)
6. ✅ Starts Python HTTP server
7. ✅ Gracefully handles Ctrl+C shutdown

---

## Stop the Server

Press **Ctrl+C** in the terminal to stop the server.

