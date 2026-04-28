#!/usr/bin/env bash
# Blue/green production deploy script. Runs on EC2 as the `deploy` user.
# Keeps Nginx stable, starts the inactive PHP-FPM color, health-checks it,
# then switches traffic with a graceful Nginx reload.

set -euo pipefail

export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

APP_DIR="${APP_DIR:-/opt/eduvidra}"
DEPLOY_DIR="$APP_DIR/.deploy"
ACTIVE_COLOR_FILE="$DEPLOY_DIR/active-color"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"
GRACE_SECONDS="${GRACE_SECONDS:-10}"

active_color() {
  if [[ -f "$ACTIVE_COLOR_FILE" ]]; then
    tr -d '[:space:]' < "$ACTIVE_COLOR_FILE"
  else
    echo "blue"
  fi
}

other_color() {
  case "$1" in
    blue) echo "green" ;;
    green) echo "blue" ;;
    *)
      echo "Invalid active color: $1" >&2
      exit 1
      ;;
  esac
}

wait_for_php() {
  local service="$1"

  for _ in {1..30}; do
    if $COMPOSE exec -T "$service" php -v >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done

  echo "PHP did not become ready in $service" >&2
  return 1
}

wait_for_nginx() {
  for _ in {1..30}; do
    if $COMPOSE exec -T nginx nginx -t >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done

  echo "Nginx did not become ready for exec" >&2
  return 1
}

switch_nginx_to() {
  local color="$1"
  local target="app-$color"

  $COMPOSE exec -T nginx sh -c "printf '%s\n' 'set \$active_app ${target};' > /etc/nginx/conf.d/upstream-active.conf && nginx -t && nginx -s reload"
}

echo "==> cd $APP_DIR"
cd "$APP_DIR"
mkdir -p "$DEPLOY_DIR"

echo "==> git pull"
git pull --ff-only

export VITE_APP_VERSION="$(git rev-parse --short HEAD)"
echo "==> version: $VITE_APP_VERSION"

ACTIVE_COLOR="$(active_color)"
INACTIVE_COLOR="$(other_color "$ACTIVE_COLOR")"
ACTIVE_SERVICE="app-$ACTIVE_COLOR"
INACTIVE_SERVICE="app-$INACTIVE_COLOR"

echo "==> active color: $ACTIVE_COLOR"
echo "==> deploying inactive color: $INACTIVE_COLOR"

echo "==> ensure redis is running"
$COMPOSE up -d redis

echo "==> build images"
$COMPOSE build "$INACTIVE_SERVICE" nginx

echo "==> start $INACTIVE_SERVICE"
$COMPOSE up -d --no-deps "$INACTIVE_SERVICE"
wait_for_php "$INACTIVE_SERVICE"

echo "==> health check $INACTIVE_SERVICE"
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan health:check

echo "==> migrate"
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan migrate --force

echo "==> clear caches on $INACTIVE_SERVICE"
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan optimize:clear
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan permission:cache-reset || true

echo "==> warm caches on $INACTIVE_SERVICE"
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan config:cache
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan route:cache
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan view:cache

echo "==> health check after cache warm"
$COMPOSE exec -T "$INACTIVE_SERVICE" php artisan health:check

echo "==> ensure nginx is running with current config"
$COMPOSE up -d --no-deps nginx
wait_for_nginx

echo "==> switch nginx to $INACTIVE_SERVICE"
switch_nginx_to "$INACTIVE_COLOR"
printf '%s\n' "$INACTIVE_COLOR" > "$ACTIVE_COLOR_FILE"

echo "==> restart workers on new image"
$COMPOSE up -d --no-deps queue scheduler

echo "==> stop old $ACTIVE_SERVICE after ${GRACE_SECONDS}s"
sleep "$GRACE_SECONDS"
$COMPOSE stop "$ACTIVE_SERVICE" >/dev/null 2>&1 || true

echo "==> prune old images"
docker image prune -f >/dev/null || true

echo "==> done; active color is $INACTIVE_COLOR"
