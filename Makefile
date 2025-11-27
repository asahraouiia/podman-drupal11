.PHONY: build start stop logs clean apache-modules-status apache-modules-enable apache-modules-enable-restart health-check logs-collect composer composer/install composer/update composer/require drush drush/status drush/cr drush/cex drush/cim drush/uli php/check-extensions help

# Variables
DRUSH_CMD = podman exec php vendor/bin/drush
COMPOSER_CMD = podman exec php composer

# Afficher l'aide avec les commandes disponibles
help:
	@echo "=== Commandes Makefile disponibles ==="
	@echo ""
	@echo "Build & Containers:"
	@echo "  make build                    - Construire les images PHP et Apache"
	@echo "  make start                    - Démarrer les containers"
	@echo "  make stop                     - Arrêter les containers"
	@echo "  make clean                    - Supprimer les containers"
	@echo ""
	@echo "Apache:"
	@echo "  make restart-apache           - Redémarrer le container Apache"
	@echo "  make reload-apache            - Recharger la config Apache (graceful)"
	@echo "  make apache-modules-status    - Vérifier les modules Apache"
	@echo "  make apache-modules-enable    - Activer headers, expires, deflate"
	@echo "  make logs                     - Suivre les logs Apache"
	@echo ""
	@echo "PHP & Shell:"
	@echo "  make php/bash                 - Ouvrir bash dans le container PHP"
	@echo "  make php/shell                - Ouvrir sh dans le container PHP"
	@echo "  make php/check-extensions     - Vérifier les extensions PHP installées"
	@echo ""
	@echo "Composer:"
	@echo "  make composer/install         - Installer les dépendances"
	@echo "  make composer/update          - Mettre à jour les packages"
	@echo "  make composer/require PKG     - Ajouter un package (ex: drupal/admin_toolbar)"
	@echo "  make composer show            - Lister les packages installés"
	@echo "  make composer outdated        - Voir les packages obsolètes"
	@echo ""
	@echo "  Exemples commandes Composer directes:"
	@echo "    podman exec php composer install"
	@echo "    podman exec php composer require drupal/admin_toolbar"
	@echo "    podman exec php composer update drupal/core --with-dependencies"
	@echo "    podman exec php composer show --tree"
	@echo ""
	@echo "Drush:"
	@echo "  make drush/status             - Vérifier le status Drupal"
	@echo "  make drush/cr                 - Vider le cache (cache-rebuild)"
	@echo "  make drush/cex                - Exporter la configuration"
	@echo "  make drush/cim                - Importer la configuration"
	@echo "  make drush/uli                - Générer un lien de connexion admin"
	@echo "  make drush pm:list            - Lister les modules"
	@echo "  make drush ws                 - Surveiller les logs Drupal"
	@echo ""
	@echo "  Exemples commandes Drush directes:"
	@echo "    podman exec php vendor/bin/drush status"
	@echo "    podman exec php vendor/bin/drush cr"
	@echo "    podman exec php vendor/bin/drush pm:enable admin_toolbar"
	@echo "    podman exec php vendor/bin/drush user:login"
	@echo "    podman exec php vendor/bin/drush sql:query 'SELECT * FROM users LIMIT 5'"
	@echo ""
	@echo "Drupal:"
	@echo "  make drupal-install           - Installer Drupal"
	@echo "  make drupal-fix-perms         - Corriger les permissions"
	@echo ""
	@echo "Monitoring:"
	@echo "  make health-check             - Vérifier la santé des containers"
	@echo "  make logs-collect             - Collecter tous les logs"
	@echo ""
	@echo "Documentation:"
	@echo "  make check-docs               - Vérifier la documentation"
	@echo ""
	@echo "=== Commandes Podman utiles ==="
	@echo ""
	@echo "Containers:"
	@echo "  podman ps                     - Liste des containers actifs"
	@echo "  podman ps -a                  - Tous les containers"
	@echo "  podman start db php web       - Démarrer les containers"
	@echo "  podman stop db php web        - Arrêter les containers"
	@echo "  podman restart web            - Redémarrer un container"
	@echo "  podman logs -f web            - Suivre les logs"
	@echo "  podman exec -it php bash      - Shell dans container"
	@echo ""
	@echo "Images:"
	@echo "  podman images                 - Liste des images"
	@echo "  podman rmi IMAGE_ID           - Supprimer une image"
	@echo ""
	@echo "Réseaux & Volumes:"
	@echo "  podman network ls             - Liste des réseaux"
	@echo "  podman volume ls              - Liste des volumes"
	@echo ""

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

php/check-extensions:
	@bash ./scripts/check-php-extensions.sh

composer:
	$(COMPOSER_CMD) $(filter-out $@,$(MAKECMDGOALS))

composer/install:
	$(COMPOSER_CMD) install --no-progress --optimize-autoloader

composer/update:
	$(COMPOSER_CMD) update --no-progress

composer/require:
	$(COMPOSER_CMD) require $(filter-out $@,$(MAKECMDGOALS))

drush:
	$(DRUSH_CMD) $(filter-out $@,$(MAKECMDGOALS))

drush/status:
	@echo "Vérification du status Drupal..."
	$(DRUSH_CMD) status

drush/cr:
	@echo "Vidage du cache Drupal..."
	$(DRUSH_CMD) cache:rebuild

drush/cex:
	@echo "Export de la configuration Drupal..."
	$(DRUSH_CMD) config:export -y

drush/cim:
	@echo "Import de la configuration Drupal..."
	$(DRUSH_CMD) config:import -y

drush/uli:
	@echo "Génération du lien de connexion admin..."
	$(DRUSH_CMD) user:login

drupal-install:
	@bash ./scripts/drupal-install.sh

drupal-fix-perms:
	podman exec -it php bash -lc "chown -R www-data:www-data /var/www/html"
