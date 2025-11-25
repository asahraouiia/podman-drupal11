# CHANGELOG

Keep a short, chronological list of noteworthy changes to this project.

Format suggestion:

- YYYY-MM-DD — Author — Short one-line summary. Files/paths: `path1`, `path2`. Notes: ...

## Historique

- 2025-01-28 — abdel — Documentation complète + scripts de monitoring/logs. Files: `docs/INSTALLATION.md`, `scripts/health-check.*`, `scripts/logs-collect.*`, `scripts/stop-containers.*`, `podman-compose.yml`, `Makefile`, `README.md`. Notes: Guide d'installation complet (9 sections) avec prérequis WSL2/Podman, structure projet, configuration complète, commandes, tests de vérification, dépannage, sécurité. Ajout healthchecks et volumes logs dans podman-compose.yml. Scripts Bash + PowerShell pour vérifier santé conteneurs et collecter logs.

- 2025-01-27 — abdel — Correction modules Apache critiques + upgrade PostgreSQL 16. Files: `docker/apache/Dockerfile`, `podman-compose.yml`, `docs/PODMAN_DRUPAL11_SETUP.md`. Notes: Les modules `proxy`, `proxy_fcgi` et `rewrite` sont OBLIGATOIRES pour que PHP fonctionne. PostgreSQL mis à jour vers version 16 (requis par Drupal 11). Configuration base de données ajoutée dans `settings.php`.

- 2025-01-27 — abdel — Ajout d'un script de gestion des modules Apache. Files: `scripts/manage-apache-modules.sh`, `scripts/manage-apache-modules.ps1`. Notes: Permet d'activer/désactiver facilement les modules Apache (headers, expires, deflate, ssl, etc.). Usage: `./scripts/manage-apache-modules.sh enable headers expires` ou `.\scripts\manage-apache-modules.ps1 enable headers`.

- 2025-01-27 — abdel — Activation des clean URLs Drupal (mod_rewrite Apache) + scripts d'initialisation Podman/WSL. Files: `docker/apache/Dockerfile`, `scripts/init-podman.sh`, `scripts/init-podman.ps1`, `scripts/start-containers.sh`, `scripts/start-containers.ps1`. Notes: Utiliser `podman compose` au lieu de `podman-compose`. Commande de démarrage: `./scripts/start-containers.sh` ou `.\scripts\start-containers.ps1`.

- 2025-01-27 — abdel — Add Podman development stack, PHP & Apache Dockerfiles, helper scripts. Files: `docker/php/Dockerfile`, `docker/apache/Dockerfile`, `podman-compose.yml`, `scripts/*`.
