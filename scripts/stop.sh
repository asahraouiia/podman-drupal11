#!/usr/bin/env bash
set -euo pipefail

# Stop and remove the development containers
echo "Stopping containers: web, php, db"
podman stop web php db 2>/dev/null || true
podman rm -f web php db 2>/dev/null || true

echo "Stopped and removed containers (if they existed)."
