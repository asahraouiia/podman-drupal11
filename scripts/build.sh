#!/usr/bin/env bash
set -euo pipefail

echo "Building images: myphp:8.3-dev and myapache:latest"
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache

echo "Build complete."
