#!/usr/bin/env bash
# Production deploy script. Runs on EC2 as the `deploy` user.
# Called by GitLab CI over SSH, or manually from /opt/eduvidra.

set -euo pipefail

# Force BuildKit so `--mount=type=cache` in the Dockerfile actually persists
# composer/npm download caches between deploys.
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

APP_DIR="/opt/eduvidra"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> cd $APP_DIR"
cd "$APP_DIR"

echo "==> git pull"
git pull --ff-only

# Stamp the frontend with the short commit SHA so the UI footer shows
# which revision is currently deployed. Picked up by Vite via
# import.meta.env.VITE_APP_VERSION in resources/js/Components/AppVersion.vue.
export VITE_APP_VERSION="$(git rev-parse --short HEAD)"
echo "==> version: $VITE_APP_VERSION"

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
