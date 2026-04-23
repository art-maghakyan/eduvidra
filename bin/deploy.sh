#!/usr/bin/env bash
# Production deploy script. Runs on EC2 as the `deploy` user.
# Called by GitLab CI over SSH, or manually from /opt/eduvidra.

set -euo pipefail

APP_DIR="/opt/eduvidra"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> cd $APP_DIR"
cd "$APP_DIR"

echo "==> git pull"
git pull --ff-only

echo "==> build + up"
$COMPOSE up -d --build --remove-orphans

echo "==> wait for app container"
for i in {1..30}; do
  if $COMPOSE exec -T app php -v >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

echo "==> migrate"
$COMPOSE exec -T app php artisan migrate --force

echo "==> clear caches"
$COMPOSE exec -T app php artisan optimize:clear
$COMPOSE exec -T app php artisan permission:cache-reset || true

echo "==> warm caches"
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

echo "==> prune old images"
docker image prune -f >/dev/null || true

echo "==> done"
