#!/usr/bin/env python3
"""
Simple HTTP Server with better error handling for Study is Funny project
"""

import http.server
import socketserver
import sys
import os
import webbrowser
import signal
import threading
import time

DEFAULT_PORT = 8000
DIRECTORY = "."
PORT = DEFAULT_PORT

class QuietHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    """Custom request handler that suppresses SSL/TLS error messages"""

    def log_error(self, format, *args):
        # Suppress SSL/TLS handshake errors
        if "Bad request version" in format or "Bad HTTP/0.9" in format:
            return
        super().log_error(format, *args)

    def log_message(self, format, *args):
        # Only log successful requests, not errors
        if "%s" in format and len(args) >= 3:
            status_code = args[2] if len(args) > 2 else ""
            if status_code in ["200", "304"]:  # Only log successful responses
                super().log_message(format, *args)

def signal_handler(signum, frame):
    """Handle Ctrl+C gracefully"""
    print("\nShutting down server...")
    sys.exit(0)

def open_browser(url_path=""):
    """Open browser after server starts"""
    time.sleep(1)  # Wait for server to start
    urls = {
        "admin": "/admin/dashboard.html",
        "student": "/student/index.html",
        "qr": "/qr-scanner.html",
        "test-qr": "/tests/test-grade-qr.html",
        "test-mongo": "/tests/test-mongodb.html"
    }
    full_url = f"http://localhost:{PORT}{urls.get(url_path, '')}"
    webbrowser.open(full_url)
    print(f"Opened {full_url} in browser")

def main():
    signal.signal(signal.SIGINT, signal_handler)

    # Parse command line arguments
    url_path = ""
    port = DEFAULT_PORT
    if len(sys.argv) > 1:
        arg = sys.argv[1].lower()
        if arg.isdigit():
            port = int(arg)
        else:
            url_path = arg
    global PORT
    PORT = port

    # Change to the script directory
    script_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(script_dir)

    # Create server
    try:
        with socketserver.TCPServer(("", PORT), QuietHTTPRequestHandler) as httpd:
            print("Study is Funny Server Starting...")
            print(f"Serving from: {os.getcwd()}")
            print(f"Server running at: http://localhost:{PORT}")
            print("Available URLs:")
            print(f"   * Main App: http://localhost:{PORT}")
            print(f"   * Admin: http://localhost:{PORT}/admin/dashboard.html")
            print(f"   * Student: http://localhost:{PORT}/student/index.html")
            print(f"   * QR Scanner: http://localhost:{PORT}/qr-scanner.html")
            print("Press Ctrl+C to stop the server")
            print()

            # Open browser in a separate thread
            browser_thread = threading.Thread(target=lambda: open_browser(url_path), daemon=True)
            browser_thread.start()

            # Serve forever
            httpd.serve_forever()

    except OSError as e:
        if e.errno == 10048:  # Address already in use
            print(f"Port {PORT} is already in use. Try a different port:")
            print(f"   python server.py 8080")
        else:
            print(f"Error starting server: {e}")
        sys.exit(1)
    except KeyboardInterrupt:
        print("\nServer stopped by user")
        sys.exit(0)

if __name__ == "__main__":
    main()