FROM php:8.3-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache $PHPIZE_DEPS yaml-dev \
    && docker-php-ext-install pcntl pdo posix \
    && pecl install yaml \
    && docker-php-ext-enable yaml

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy source and configuration files
COPY src /app/src
COPY test /app/test
COPY composer.json /app/composer.json
COPY phpunit.xml /app/phpunit.xml

# Copy entrypoint script
COPY docker/php83/run-tests.sh /usr/local/bin/run-tests.sh
RUN chmod +x /usr/local/bin/run-tests.sh

ENTRYPOINT ["/usr/local/bin/run-tests.sh"]
