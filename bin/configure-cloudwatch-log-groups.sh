#!/usr/bin/env bash
# Run from a machine with AWS CLI credentials that can manage CloudWatch Logs.
# Creates Eduvidra log groups if missing and applies retention.

set -euo pipefail

AWS_REGION="${AWS_REGION:-eu-central-1}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

LOG_GROUPS=(
  "/eduvidra/prod/app"
  "/eduvidra/prod/nginx"
  "/eduvidra/prod/queue"
  "/eduvidra/prod/scheduler"
  "/eduvidra/prod/redis"
  "/eduvidra/prod/host/syslog"
  "/eduvidra/prod/host/auth"
)

for group in "${LOG_GROUPS[@]}"; do
  aws logs create-log-group \
    --region "$AWS_REGION" \
    --log-group-name "$group" 2>/dev/null || true

  aws logs put-retention-policy \
    --region "$AWS_REGION" \
    --log-group-name "$group" \
    --retention-in-days "$RETENTION_DAYS"

  echo "Configured $group retention: ${RETENTION_DAYS} days"
done
