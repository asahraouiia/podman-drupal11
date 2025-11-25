#!/usr/bin/env bash
set -euo pipefail

# Script: scripts/drupal-install.sh
# Purpose: Run Composer create-project for Drupal inside the 'php' container
# with an extended timeout and log output to /tmp/composer.log inside the container.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Starting Drupal installation inside container 'php' (timeout 1800s)..."

podman exec -it php bash -lc \
  "COMPOSER_PROCESS_TIMEOUT=1800 COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 /var/www/html --no-interaction 2>&1 | tee /tmp/composer.log" || {
  echo "\nComposer command failed. Showing last 200 lines of /tmp/composer.log inside container (if available):\n"
  podman exec -it php bash -lc "tail -n 200 /tmp/composer.log" || true
  echo "Check 'podman logs php' for additional details."
  exit 1
}

echo "Composer finished — fixing ownership of /var/www/html..."
podman exec -it php bash -lc "chown -R www-data:www-data /var/www/html"

echo "Drupal project created in the bind-mounted './src' directory."
echo "Open http://localhost:8080 to finish the web installer."

exit 0
