#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
: "${PHP_BINARY:=php}"
# Run inside a prepared release directory. Back up the database before invoking.
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$PHP_BINARY" artisan starter:publish-migrations --no-interaction
"$PHP_BINARY" artisan migrate --force --no-interaction
"$PHP_BINARY" artisan migrate --path=database/settings --force --no-interaction
"$PHP_BINARY" artisan filament:assets --no-interaction
"$PHP_BINARY" artisan capell:package-cache --no-interaction
"$PHP_BINARY" artisan config:cache --no-interaction
"$PHP_BINARY" artisan route:cache --no-interaction
"$PHP_BINARY" artisan view:cache --no-interaction
"$PHP_BINARY" artisan queue:restart --no-interaction
