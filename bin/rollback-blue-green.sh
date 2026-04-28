#!/usr/bin/env bash
# Switch production traffic back to the previously inactive color.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/eduvidra}"
DEPLOY_DIR="$APP_DIR/.deploy"
ACTIVE_COLOR_FILE="$DEPLOY_DIR/active-color"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

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

switch_nginx_to() {
  local color="$1"
  local target="app-$color"

  $COMPOSE exec -T nginx sh -c "printf '%s\n' 'set \$active_app ${target};' > /etc/nginx/conf.d/upstream-active.conf && nginx -t && nginx -s reload"
}

wait_for_nginx() {
  sleep 2

  for _ in {1..60}; do
    if $COMPOSE exec -T nginx sh -c 'true' >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done

  echo "Nginx did not become ready for exec" >&2
  echo "---- nginx ps ----" >&2
  $COMPOSE ps nginx >&2 || true
  echo "---- nginx logs ----" >&2
  $COMPOSE logs --tail=50 nginx >&2 || true
  return 1
}

echo "==> cd $APP_DIR"
cd "$APP_DIR"

ACTIVE_COLOR="$(active_color)"
ROLLBACK_COLOR="$(other_color "$ACTIVE_COLOR")"
ROLLBACK_SERVICE="app-$ROLLBACK_COLOR"

echo "==> active color: $ACTIVE_COLOR"
echo "==> rollback color: $ROLLBACK_COLOR"

echo "==> start $ROLLBACK_SERVICE"
$COMPOSE up -d --no-deps "$ROLLBACK_SERVICE"

echo "==> health check $ROLLBACK_SERVICE"
$COMPOSE exec -T "$ROLLBACK_SERVICE" php artisan health:check

echo "==> switch nginx to $ROLLBACK_SERVICE"
wait_for_nginx
switch_nginx_to "$ROLLBACK_COLOR"
printf '%s\n' "$ROLLBACK_COLOR" > "$ACTIVE_COLOR_FILE"

echo "==> done; active color is $ROLLBACK_COLOR"
