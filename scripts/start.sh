#!/usr/bin/env bash
set -euo pipefail

# Start script for the Podman Drupal stack.
# If podman-compose exists we prefer it; otherwise run explicit podman commands.

if command -v podman-compose >/dev/null 2>&1; then
  podman-compose -f podman-compose.yml build
  podman-compose -f podman-compose.yml up -d
  podman-compose -f podman-compose.yml ps -a
  exit 0
fi

# Ensure network and volume exist
podman network inspect drupal_net >/dev/null 2>&1 || podman network create drupal_net
podman volume inspect drupal_db_data >/dev/null 2>&1 || podman volume create drupal_db_data

# Resolve host path for bind mount (handles Git Bash / MSYS / WSL / Windows)
# Preferred: try pwd -W (Git for Windows), then cygpath, otherwise plain pwd
HOST_PWD="$(pwd)"
if HOST_W=$(pwd -W 2>/dev/null); then
  HOST_PWD="$HOST_W"
elif command -v cygpath >/dev/null 2>&1; then
  HOST_PWD="$(cygpath -w "$(pwd)")"
fi

# For Windows paths ensure backslashes are not interpreted by podman on Windows CLI
# We will use HOST_SRC as the host-side path to ./src
HOST_SRC="$HOST_PWD/src"

# Build images
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache

# Run Postgres
if ! podman ps -a --format '{{.Names}}' | grep -q '^db$'; then
  podman run -d --name db --network drupal_net \
    -e POSTGRES_USER=drupal -e POSTGRES_PASSWORD=drupal -e POSTGRES_DB=drupal \
    -v drupal_db_data:/var/lib/postgresql/data postgres:15
else
  echo "Container 'db' already exists"
fi

# Run PHP
if ! podman ps -a --format '{{.Names}}' | grep -q '^php$'; then
  podman run -d --name php --network drupal_net -v "${HOST_SRC}:/var/www/html" myphp:8.3-dev
else
  echo "Container 'php' already exists"
fi

# Run web
if ! podman ps -a --format '{{.Names}}' | grep -q '^web$'; then
  podman run -d --name web --network drupal_net -p 8080:80 -v "${HOST_SRC}:/var/www/html" myapache:latest
else
  echo "Container 'web' already exists"
fi

podman ps -a
