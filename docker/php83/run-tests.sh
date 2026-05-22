#!/bin/sh
set -e

# Install dependencies
composer update --no-interaction --prefer-dist --no-progress

# Run tests
./vendor/bin/phpunit --display-all-issues
