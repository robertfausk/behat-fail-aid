#!/usr/bin/env bash
set -e

# Start PHP built-in server for Behat tests
php -S localhost:8531 -t app &
SERVER_PID=$!

# Give server a moment to start
sleep 1

# Run tests
./vendor/bin/phpunit -c tests

# fail.feature contains 1 intentionally failing scenario run across 3 suites = 3 expected failures
BEHAT_OUTPUT=$(./vendor/bin/behat --no-colors 2>&1) || true
echo "$BEHAT_OUTPUT"
echo "$BEHAT_OUTPUT" | grep -q "9 scenarios (6 passed, 3 failed)" \
  || { echo "Unexpected behat scenario result!"; kill $SERVER_PID; exit 1; }

# Stop server
kill $SERVER_PID
