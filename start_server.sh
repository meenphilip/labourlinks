#!/bin/bash
# Stop any existing servers
pkill -f "php -S" || true

# Start PHP server
echo "Starting PHP server on port 3306..."
php -S localhost:3306 -t ./ &

# Wait for server to start
sleep 2

# Open browser
if which xdg-open > /dev/null; then
    xdg-open "http://localhost:3306"
elif which open > /dev/null; then
    open "http://localhost:3306"
else
    echo "Please open manually: http://localhost:3306"
fi

echo "Server is running at http://localhost:3306"
wait