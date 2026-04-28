#!/usr/bin/env bash
# Production deploy entrypoint. GitLab CI calls this file over SSH.
# The production deploy strategy is now blue/green; keep this wrapper so
# existing CI configuration does not need to know the internal script name.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$SCRIPT_DIR/deploy-blue-green.sh"
