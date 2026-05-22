#!/bin/sh
set -e

# Install dependencies
# PHP 8.5 might need --ignore-platform-reqs if some packages are not yet tagged for it
composer update --no-interaction --prefer-dist --no-progress --ignore-platform-reqs

# Run tests
./vendor/bin/phpunit --display-all-issues
