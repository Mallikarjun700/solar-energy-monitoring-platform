#!/bin/sh
set -eu

: "${PORT:=10000}"
: "${BACKEND_HOSTPORT:?BACKEND_HOSTPORT is required}"

envsubst '\$PORT \$BACKEND_HOSTPORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

exec nginx -g 'daemon off;'
