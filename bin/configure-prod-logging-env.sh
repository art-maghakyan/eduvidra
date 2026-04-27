#!/usr/bin/env bash
# Run on EC2 from /opt/eduvidra to switch Laravel production logging to stderr.
# This makes application exceptions visible in Docker logs and CloudWatch Logs.

set -euo pipefail

ENV_FILE="${ENV_FILE:-/opt/eduvidra/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing env file: $ENV_FILE" >&2
  exit 1
fi

set_env() {
  local key="$1"
  local value="$2"

  if grep -qE "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$value" >> "$ENV_FILE"
  fi
}

set_env "LOG_CHANNEL" "stack"
set_env "LOG_STACK" "stderr"
set_env "LOG_LEVEL" "warning"

echo "Updated Laravel logging in $ENV_FILE:"
grep -E '^LOG_(CHANNEL|STACK|LEVEL)=' "$ENV_FILE"
