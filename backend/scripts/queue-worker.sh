#!/usr/bin/env sh

set -e

php artisan queue:restart

echo "Queue workers instructed to restart gracefully."


exec php artisan queue:work \
    --queue=default \
    --sleep=3 \
    --tries=3 \
    --timeout=60 \
    --backoff=10 \
    --max-jobs=1000 \
    --max-time=3600 \
    --memory=256 \
    --no-interaction