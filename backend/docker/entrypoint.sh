#!/bin/sh

set +e

SERVICE_TYPE=${SERVICE_TYPE:-app}

case "$SERVICE_TYPE" in
  init-db)
    echo "======================================"
    echo "Solar Energy Database Initialization"
    echo "======================================"

    echo "Waiting for MySQL..."
    until php artisan db:show --database=mysql >/dev/null 2>&1; do
        sleep 2
    done

    echo "MySQL is ready."
    echo "Dropping and recreating MySQL database..."
    php artisan migrate:fresh --database=mysql --force --seed

    echo "MySQL migrations and seeding completed."

    echo "Waiting for PostgreSQL..."
    until php artisan db:show --database=pgsql_telemetry >/dev/null 2>&1; do
        sleep 2
    done

    echo "PostgreSQL is ready."
    echo "Dropping all PostgreSQL telemetry tables..."
    php artisan db:wipe --database=pgsql_telemetry --force

    echo "PostgreSQL tables dropped."
    echo "Running PostgreSQL migrations..."
    php artisan migrate --database=pgsql_telemetry --force
    
    echo "PostgreSQL migrations completed."
    echo "Seeding PostgreSQL telemetry data..."
    php artisan db:seed --database=pgsql_telemetry --force
    
    echo "PostgreSQL seeding completed."

    echo "Database initialization completed."
    ;;

  queue-worker)
    echo "Starting queue worker..."
    php artisan queue:work \
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
    php artisan schedule:work
    ;;

  app)
    echo "Starting PHP-FPM..."
    php-fpm
    ;;

  *)
    echo "Unknown service type: $SERVICE_TYPE"
    exit 1
    ;;
esac
