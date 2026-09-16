#!/usr/bin/env bash
set -e

# Start PHP built-in server for Behat tests
php -S localhost:8531 -t app &
SERVER_PID=$!

# Give server a moment to start
sleep 1

# Run tests
./vendor/bin/phpunit -c tests
./vendor/bin/behat

# Stop server
kill $SERVER_PID
