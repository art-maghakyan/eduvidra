#!/usr/bin/env bash
# Run from a machine with AWS CLI credentials that can manage CloudWatch alarms.
#
# Required:
#   INSTANCE_ID=i-xxxxxxxxxxxxxxxxx
#   DB_INSTANCE_IDENTIFIER=eduvidra-pg
#
# Optional:
#   AWS_REGION=eu-central-1
#   SNS_TOPIC_ARN=arn:aws:sns:...
#   DISK_FSTYPE=ext4

set -euo pipefail

AWS_REGION="${AWS_REGION:-eu-central-1}"
INSTANCE_ID="${INSTANCE_ID:?Set INSTANCE_ID to the EC2 instance id}"
DB_INSTANCE_IDENTIFIER="${DB_INSTANCE_IDENTIFIER:?Set DB_INSTANCE_IDENTIFIER to the RDS DB identifier}"
DISK_FSTYPE="${DISK_FSTYPE:-ext4}"
SNS_TOPIC_ARN="${SNS_TOPIC_ARN:-}"

alarm_actions=()
if [[ -n "$SNS_TOPIC_ARN" ]]; then
  alarm_actions=(--alarm-actions "$SNS_TOPIC_ARN")
fi

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-ec2-status-check-failed" \
  --namespace "AWS/EC2" \
  --metric-name "StatusCheckFailed" \
  --dimensions "Name=InstanceId,Value=$INSTANCE_ID" \
  --statistic Maximum \
  --period 60 \
  --evaluation-periods 2 \
  --threshold 1 \
  --comparison-operator GreaterThanOrEqualToThreshold \
  --treat-missing-data notBreaching \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-ec2-cpu-high" \
  --namespace "AWS/EC2" \
  --metric-name "CPUUtilization" \
  --dimensions "Name=InstanceId,Value=$INSTANCE_ID" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --treat-missing-data notBreaching \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-ec2-memory-high" \
  --namespace "Eduvidra/EC2" \
  --metric-name "mem_used_percent" \
  --dimensions "Name=InstanceId,Value=$INSTANCE_ID" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --treat-missing-data missing \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-ec2-disk-root-high" \
  --namespace "Eduvidra/EC2" \
  --metric-name "disk_used_percent" \
  --dimensions "Name=InstanceId,Value=$INSTANCE_ID" "Name=path,Value=/" "Name=fstype,Value=$DISK_FSTYPE" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --treat-missing-data missing \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-rds-cpu-high" \
  --namespace "AWS/RDS" \
  --metric-name "CPUUtilization" \
  --dimensions "Name=DBInstanceIdentifier,Value=$DB_INSTANCE_IDENTIFIER" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --treat-missing-data notBreaching \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-rds-free-storage-low" \
  --namespace "AWS/RDS" \
  --metric-name "FreeStorageSpace" \
  --dimensions "Name=DBInstanceIdentifier,Value=$DB_INSTANCE_IDENTIFIER" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 2147483648 \
  --comparison-operator LessThanThreshold \
  --treat-missing-data notBreaching \
  "${alarm_actions[@]}"

aws cloudwatch put-metric-alarm \
  --region "$AWS_REGION" \
  --alarm-name "eduvidra-rds-connections-high" \
  --namespace "AWS/RDS" \
  --metric-name "DatabaseConnections" \
  --dimensions "Name=DBInstanceIdentifier,Value=$DB_INSTANCE_IDENTIFIER" \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --treat-missing-data notBreaching \
  "${alarm_actions[@]}"

echo "CloudWatch alarms created in $AWS_REGION"
