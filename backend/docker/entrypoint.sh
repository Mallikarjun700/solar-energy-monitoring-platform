#!/bin/sh

set -e

SERVICE_TYPE=${SERVICE_TYPE:-app}

case "$SERVICE_TYPE" in

  migration)
    echo "Running MySQL migrations..."
    php artisan migrate --database=mysql --force

    echo "Running PostgreSQL telemetry migrations..."
    php artisan migrate --database=pgsql_telemetry --force

    echo "Database migrations completed successfully."
    ;;

  queue-worker)
    echo "Starting queue worker..."

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
    ;;

  scheduler)
    echo "Starting scheduler..."

    exec php artisan schedule:work
    ;;

  app)
    echo "Starting PHP-FPM..."

    exec php-fpm
    ;;

  *)
    echo "Unknown service type: $SERVICE_TYPE"
    exit 1
    ;;

esac
