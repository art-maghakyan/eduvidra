#!/usr/bin/env bash
# Run from a machine with AWS CLI credentials that can update IAM roles.
# Attaches the managed policy needed by the EC2 instance role for
# CloudWatch Agent and Docker's awslogs logging driver.

set -euo pipefail

ROLE_NAME="${ROLE_NAME:-eduvidra-ec2-role}"
POLICY_ARN="arn:aws:iam::aws:policy/CloudWatchAgentServerPolicy"

aws iam attach-role-policy \
  --role-name "$ROLE_NAME" \
  --policy-arn "$POLICY_ARN"

echo "Attached $POLICY_ARN to role $ROLE_NAME"
