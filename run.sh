#!/bin/bash

echo "Starting Study is Funny Educational Platform..."
echo ""
echo "Opening browser to http://localhost:8000"
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000
elif command -v open &> /dev/null; then
    open http://localhost:8000
fi
echo ""
echo "Server starting on http://localhost:8000"
echo "Press Ctrl+C to stop the server"
echo ""
python3 -m http.server 8000