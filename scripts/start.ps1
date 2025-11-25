param()

# PowerShell start script: uses podman-compose if available, otherwise falls back to podman commands.

if (Get-Command podman-compose -ErrorAction SilentlyContinue) {
    podman-compose -f podman-compose.yml build
    podman-compose -f podman-compose.yml up -d
    podman-compose -f podman-compose.yml ps -a
    return
}

# create network if missing
$network = podman network inspect drupal_net 2>$null
if ($LASTEXITCODE -ne 0) { podman network create drupal_net }

# create volume if missing
podman volume inspect drupal_db_data 2>$null
if ($LASTEXITCODE -ne 0) { podman volume create drupal_db_data }

# build images
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache

# refresh names list
$names = podman ps -a --format "{{.Names}}"

# run db if not exists
if (-not ($names -match '^db$')) {
    podman run -d --name db --network drupal_net -e POSTGRES_USER=drupal -e POSTGRES_PASSWORD=drupal -e POSTGRES_DB=drupal -v drupal_db_data:/var/lib/postgresql/data postgres:15
} else { Write-Host "Container 'db' already exists" }

# run php
if (-not ($names -match '^php$')) {
    podman run -d --name php --network drupal_net -v "${PWD}\src:/var/www/html" myphp:8.3-dev
} else { Write-Host "Container 'php' already exists" }

# run web
if (-not ($names -match '^web$')) {
    podman run -d --name web --network drupal_net -p 8080:80 -v "${PWD}\src:/var/www/html" myapache:latest
} else { Write-Host "Container 'web' already exists" }

podman ps -a
