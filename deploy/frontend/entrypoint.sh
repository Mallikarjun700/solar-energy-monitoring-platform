#!/bin/sh
set -eu

: "${PORT:=10000}"
: "${BACKEND_BASE_URL:?BACKEND_BASE_URL is required}"

envsubst '\$PORT \$BACKEND_BASE_URL' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

exec nginx -g 'daemon off;'
