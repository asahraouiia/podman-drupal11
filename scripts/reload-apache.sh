#!/usr/bin/env bash
set -euo pipefail

# Host helper: reload-apache.sh
# Attempts a graceful Apache reload inside the 'web' container,
# falls back to restart/start the container if reload is unavailable or fails.

CONTAINER=web

echo "Attempting graceful Apache reload in container '${CONTAINER}'..."

podman exec "${CONTAINER}" sh -lc '
  if command -v apachectl >/dev/null 2>&1; then
    apachectl -k graceful
  elif [ -x /usr/local/apache2/bin/apachectl ]; then
    /usr/local/apache2/bin/apachectl -k graceful
  else
    echo "apachectl not found in container"
    exit 2
  fi
' || {
  echo "Graceful reload failed or apachectl missing — restarting container '${CONTAINER}'..."
  podman restart "${CONTAINER}" || podman start "${CONTAINER}" || true
}

echo "Done."
