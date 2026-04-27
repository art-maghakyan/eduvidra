#!/usr/bin/env bash
# Run on the EC2 host from /opt/eduvidra as a sudo-capable user.
# Installs Amazon CloudWatch Agent and applies ops/cloudwatch config.

set -euo pipefail

CONFIG_SOURCE="${CONFIG_SOURCE:-ops/cloudwatch/amazon-cloudwatch-agent.json}"
CONFIG_TARGET="/opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json"
DEB_URL="https://s3.amazonaws.com/amazoncloudwatch-agent/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb"
TMP_DEB="/tmp/amazon-cloudwatch-agent.deb"

if [[ ! -f "$CONFIG_SOURCE" ]]; then
  echo "Missing CloudWatch Agent config: $CONFIG_SOURCE" >&2
  exit 1
fi

sudo apt-get update
sudo apt-get install -y --no-install-recommends ca-certificates curl

curl -fsSL "$DEB_URL" -o "$TMP_DEB"
sudo dpkg -i "$TMP_DEB"
rm -f "$TMP_DEB"

sudo install -D -m 0644 "$CONFIG_SOURCE" "$CONFIG_TARGET"

sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl \
  -a fetch-config \
  -m ec2 \
  -s \
  -c "file:$CONFIG_TARGET"

sudo systemctl enable amazon-cloudwatch-agent
sudo systemctl status amazon-cloudwatch-agent --no-pager
