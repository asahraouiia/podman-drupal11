.PHONY: build start stop logs clean apache-modules-status apache-modules-enable apache-modules-enable-restart health-check logs-collect

build:
	podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
	podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache

start:
	@bash ./scripts/start.sh

stop:
	@bash ./scripts/stop.sh

restart-apache:
	@echo "Restarting Apache container 'web'..."
	@podman restart web || podman start web || true

reload-apache:
	@echo "Reloading Apache configuration in container 'web' (graceful) via ./scripts/reload-apache.sh..."
	@bash ./scripts/reload-apache.sh

apache-modules-status:
	@echo "Checking Apache modules status..."
	@bash ./scripts/manage-apache-modules.sh status

apache-modules-enable:
	@echo "Enabling Apache modules: headers, expires, deflate..."
	@bash ./scripts/manage-apache-modules.sh enable headers expires deflate

apache-modules-enable-restart:
	@echo "Enabling Apache modules and restarting container..."
	@bash ./scripts/manage-apache-modules.sh enable headers expires deflate --restart

logs:
	podman logs -f web

logs-collect:
	@echo "Collecte des logs de tous les conteneurs..."
	@bash ./scripts/logs-collect.sh

health-check:
	@echo "Vérification de la santé des conteneurs..."
	@bash ./scripts/health-check.sh

clean:
	podman rm -f web php db || true

check-docs:
	@bash ./scripts/check-docs-updated.sh

php/bash:
	podman exec -it php bash

php/shell:
	podman exec -it php sh

drupal-install:
	@bash ./scripts/drupal-install.sh

drupal-fix-perms:
	podman exec -it php bash -lc "chown -R www-data:www-data /var/www/html"
