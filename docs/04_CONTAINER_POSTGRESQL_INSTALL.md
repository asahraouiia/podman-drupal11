# Guide d'installation et configuration - Conteneur PostgreSQL

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Configuration du conteneur](#2-configuration-du-conteneur)
3. [Variables d'environnement](#3-variables-denvironnement)
4. [Volumes et persistance](#4-volumes-et-persistance)
5. [Démarrage du conteneur](#5-démarrage-du-conteneur)
6. [Connexion à PostgreSQL](#6-connexion-à-postgresql)
7. [Gestion de la base de données](#7-gestion-de-la-base-de-données)
8. [Logs PostgreSQL](#8-logs-postgresql)
9. [Sauvegarde et restauration](#9-sauvegarde-et-restauration)
10. [Dépannage](#10-dépannage)
11. [Optimisation](#11-optimisation)

---

## 1. Vue d'ensemble

### 1.1. Rôle du conteneur PostgreSQL

Le conteneur PostgreSQL est le **système de gestion de base de données** dans l'architecture Drupal :

```
Apache → PHP-FPM → Drupal → PostgreSQL (:5432)
                              ↓
                      Volume persistant
                      (drupal_db_data)
```

**Responsabilités** :
- Stocker les données Drupal (contenus, utilisateurs, configuration)
- Gérer les transactions SQL
- Assurer la persistance des données
- Optimiser les requêtes

### 1.2. Caractéristiques techniques

- **Image** : `postgres:16` (PostgreSQL officiel)
- **Version** : PostgreSQL 16.11 (**requis par Drupal 11**)
- **Port interne** : `5432` (non exposé sur l'hôte)
- **Utilisateur par défaut** : `drupal`
- **Base de données** : `drupal`
- **Mot de passe** : `drupal` (⚠️ **développement uniquement**)

### 1.3. Pourquoi PostgreSQL 16 ?

Drupal 11 nécessite PostgreSQL 16 minimum pour :
- Support des nouvelles fonctionnalités JSON
- Amélioration des performances
- Nouvelles fonctions de fenêtrage
- Meilleure gestion des index

---

## 2. Configuration du conteneur

### 2.1. Dans podman-compose.yml

**Emplacement** : `/podman-compose.yml`

```yaml
services:
  db:
    image: postgres:16               # Version minimale requise: PostgreSQL 16
    container_name: db
    ports:
      - "5432:5432"                  # Exposition pour outils externes (VSCode, DBeaver, etc.)
    environment:
      POSTGRES_USER: drupal          # Utilisateur base de données
      POSTGRES_PASSWORD: drupal      # Mot de passe (à changer en production!)
      POSTGRES_DB: drupal            # Nom de la base
    volumes:
      - drupal_db_data:/var/lib/postgresql/data  # Données persistantes
      - ./logs/postgres:/var/log/postgresql      # Logs PostgreSQL
    networks:
      - drupal_net
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U drupal"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

volumes:
  drupal_db_data:                    # Volume nommé pour PostgreSQL
    driver: local
```

### 2.2. Explication de la configuration

#### Image

```yaml
image: postgres:16
```

Utilise l'image officielle PostgreSQL 16 depuis Docker Hub.

**⚠️ Ne pas utiliser `postgres:latest`** pour garantir la compatibilité.

#### Container name

```yaml
container_name: db
```

Nom du conteneur. Utilisé pour la résolution DNS dans le réseau Podman (`db:5432`).

#### Ports

```yaml
ports:
  - "5432:5432"
```

Expose le port PostgreSQL sur `localhost:5432` pour permettre la connexion depuis :
- **VSCode** (extensions PostgreSQL/MySQL)
- **DBeaver**, **pgAdmin**, **Navicat**
- Tout autre outil de gestion de base de données

**Configuration pour outils externes :**
- Host: `localhost`
- Port: `5432`
- Database: `drupal`
- User: `drupal`
- Password: `drupal`

**Note :** Les containers PHP/Apache continuent d'utiliser `db:5432` via le réseau interne.

#### Volumes

```yaml
volumes:
  - drupal_db_data:/var/lib/postgresql/data
  - ./logs/postgres:/var/log/postgresql
```

- **drupal_db_data** : Volume nommé pour la persistance des données
- **./logs/postgres** : Bind mount pour les logs accessibles depuis l'hôte

#### Réseau

```yaml
networks:
  - drupal_net
```

Réseau privé pour la communication avec Apache et PHP.

#### Restart policy

```yaml
restart: unless-stopped
```

Redémarre automatiquement sauf si arrêté manuellement.

#### Healthcheck

```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U drupal"]
  interval: 10s
  timeout: 5s
  retries: 5
  start_period: 30s
```

Vérifie que PostgreSQL est prêt à accepter des connexions.

---

## 3. Variables d'environnement

### 3.1. Variables obligatoires

| Variable | Valeur | Description |
|----------|--------|-------------|
| `POSTGRES_USER` | `drupal` | Nom d'utilisateur PostgreSQL |
| `POSTGRES_PASSWORD` | `drupal` | Mot de passe de l'utilisateur |
| `POSTGRES_DB` | `drupal` | Nom de la base de données à créer |

### 3.2. Variables optionnelles

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `POSTGRES_INITDB_ARGS` | - | Arguments pour `initdb` (ex: `--encoding=UTF8`) |
| `POSTGRES_HOST_AUTH_METHOD` | - | Méthode d'authentification (ex: `trust`, `md5`) |
| `PGDATA` | `/var/lib/postgresql/data` | Emplacement des données |

### 3.3. Utiliser des variables d'environnement (.env)

**Créer un fichier `.env`** :

```bash
# .env
POSTGRES_USER=drupal
POSTGRES_PASSWORD=VotreMot2PasseFort!
POSTGRES_DB=drupal
```

**Modifier `podman-compose.yml`** :

```yaml
db:
  environment:
    POSTGRES_USER: ${POSTGRES_USER}
    POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    POSTGRES_DB: ${POSTGRES_DB}
```

**⚠️ Ne jamais versionner le fichier `.env` !**

### 3.4. Utiliser des secrets (production)

**Créer un fichier de secret** :

```bash
# secrets/db_password.txt
VotreMot2PasseFort!
```

**Modifier `podman-compose.yml`** :

```yaml
db:
  environment:
    POSTGRES_USER: drupal
    POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    POSTGRES_DB: drupal
  secrets:
    - db_password

secrets:
  db_password:
    file: ./secrets/db_password.txt
```

---

## 4. Volumes et persistance

### 4.1. Volume nommé (recommandé)

```yaml
volumes:
  drupal_db_data:
    driver: local
```

**Avantages** :
- Géré par Podman
- Meilleures performances sur Windows/WSL
- Facile à sauvegarder/restaurer

**Emplacement sur l'hôte** :

```bash
# Voir les détails du volume
podman volume inspect drupal_db_data

# Sortie (excerpt) :
# "Mountpoint": "/var/lib/containers/storage/volumes/drupal_db_data/_data"
```

### 4.2. Bind mount (déconseillé sur Windows)

```yaml
volumes:
  - ./data/postgres:/var/lib/postgresql/data
```

**Inconvénients** :
- Performances dégradées sur Windows/WSL
- Problèmes de permissions

### 4.3. Gérer les volumes

```bash
# Lister les volumes
podman volume ls

# Inspecter un volume
podman volume inspect drupal_db_data

# Sauvegarder un volume
podman volume export drupal_db_data > drupal_db_volume.tar

# Restaurer un volume
podman volume import drupal_db_data < drupal_db_volume.tar

# Supprimer un volume (⚠️ perte de données)
podman volume rm drupal_db_data
```

---

## 5. Démarrage du conteneur

### 5.1. Via Podman Compose (recommandé)

```bash
# Démarrer tous les services
podman compose up -d

# Démarrer uniquement PostgreSQL
podman compose up -d db
```

### 5.2. Via Podman directement

```bash
podman run -d \
  --name db \
  -e POSTGRES_USER=drupal \
  -e POSTGRES_PASSWORD=drupal \
  -e POSTGRES_DB=drupal \
  -v drupal_db_data:/var/lib/postgresql/data \
  --network podman_drupal_net \
  postgres:16
```

### 5.3. Vérifier le démarrage

```bash
# État du conteneur
podman ps | grep db

# Sortie attendue :
# CONTAINER ID  IMAGE         STATUS
# abc123def456  postgres:16   Up 2 minutes (healthy)

# Logs de démarrage
podman logs db

# Sortie attendue :
# PostgreSQL init process complete; ready for start up.
# ...
# database system is ready to accept connections
```

### 5.4. Tester la connexion

```bash
# Via pg_isready
podman exec db pg_isready -U drupal

# Sortie attendue :
# /var/run/postgresql:5432 - accepting connections

# Via psql
podman exec db psql -U drupal -c "SELECT version();"

# Sortie attendue :
# PostgreSQL 16.11 (Debian 16.11-1.pgdg120+1) on x86_64-pc-linux-gnu...
```

---

## 6. Connexion à PostgreSQL

### 6.1. Depuis le conteneur PostgreSQL (psql)

```bash
# Connexion interactive
podman exec -it db psql -U drupal -d drupal

# Commandes psql utiles :
# \dt         - Lister les tables
# \l          - Lister les bases de données
# \du         - Lister les utilisateurs
# \c drupal   - Se connecter à la base drupal
# \q          - Quitter
```

### 6.2. Depuis le conteneur PHP

```bash
# Tester la connexion depuis PHP
podman exec php php -r "new PDO('pgsql:host=db;port=5432;dbname=drupal', 'drupal', 'drupal');"

# Si succès : aucune sortie
# Si échec : Exception
```

### 6.3. Depuis l'hôte (si psql installé)

```bash
# Installer psql sur Windows
# Télécharger depuis : https://www.postgresql.org/download/windows/

# Se connecter (le port n'est pas exposé, ne fonctionnera pas par défaut)
psql -h localhost -p 5432 -U drupal -d drupal
# Password: drupal
```

**Note** : Le port 5432 n'est pas exposé sur l'hôte par défaut pour des raisons de sécurité.

### 6.4. Depuis un client GUI

#### pgAdmin

1. Télécharger pgAdmin : [https://www.pgadmin.org/download/](https://www.pgadmin.org/download/)
2. Créer un nouveau serveur
3. **⚠️ Le port n'est pas exposé par défaut**

#### DBeaver

1. Télécharger DBeaver : [https://dbeaver.io/download/](https://dbeaver.io/download/)
2. Créer une nouvelle connexion PostgreSQL
3. **⚠️ Le port n'est pas exposé par défaut**

#### Exposer le port (développement uniquement)

Modifier `podman-compose.yml` :

```yaml
db:
  ports:
    - "5432:5432"
```

Redémarrer :

```bash
podman compose down
podman compose up -d
```

Maintenant accessible sur `localhost:5432`.

---

## 7. Gestion de la base de données

### 7.1. Créer une base de données

```bash
# Depuis psql
podman exec -it db psql -U drupal -c "CREATE DATABASE ma_nouvelle_base;"

# Lister les bases
podman exec db psql -U drupal -c "\l"
```

### 7.2. Supprimer une base de données

```bash
# ⚠️ Attention, cette action est irréversible
podman exec db psql -U drupal -c "DROP DATABASE ma_nouvelle_base;"
```

### 7.3. Créer un utilisateur

```bash
# Créer un utilisateur avec mot de passe
podman exec db psql -U drupal -c "CREATE USER nouvel_user WITH PASSWORD 'mot_de_passe';"

# Donner les privilèges
podman exec db psql -U drupal -c "GRANT ALL PRIVILEGES ON DATABASE drupal TO nouvel_user;"
```

### 7.4. Voir la taille de la base

```bash
# Taille de toutes les bases
podman exec db psql -U drupal -c "SELECT pg_database.datname, pg_size_pretty(pg_database_size(pg_database.datname)) FROM pg_database;"

# Taille de la base drupal
podman exec db psql -U drupal -d drupal -c "SELECT pg_size_pretty(pg_database_size('drupal'));"
```

### 7.5. Lister les tables

```bash
# Liste des tables Drupal
podman exec db psql -U drupal -d drupal -c "\dt"

# Nombre de tables
podman exec db psql -U drupal -d drupal -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public';"
```

### 7.6. Exécuter une requête SQL

```bash
# Requête SELECT
podman exec db psql -U drupal -d drupal -c "SELECT uid, name FROM users_field_data LIMIT 5;"

# Requête UPDATE
podman exec db psql -U drupal -d drupal -c "UPDATE users SET status=1 WHERE uid=1;"
```

---

## 8. Logs PostgreSQL

### 8.1. Logs en temps réel

```bash
# Tous les logs PostgreSQL
podman logs -f db

# Logs depuis l'hôte
tail -f logs/postgres/postgresql.log
```

### 8.2. Logs historiques

```bash
# Dernières 50 lignes
podman logs --tail 50 db

# Depuis une date
podman logs --since 2025-11-25T10:00:00 db

# Avec timestamps
podman logs -t db
```

### 8.3. Collecter les logs

```bash
# Script de collecte automatique
./scripts/logs-collect.sh

# Les logs sont copiés dans logs/postgres/
ls -lh logs/postgres/
```

### 8.4. Activer les logs de requêtes (développement)

```bash
# Se connecter à psql
podman exec -it db psql -U drupal

# Activer les logs de toutes les requêtes
ALTER SYSTEM SET log_statement = 'all';

# Recharger la configuration
SELECT pg_reload_conf();

# Quitter
\q
```

**⚠️ Ne pas activer en production (impact sur les performances)**

---

## 9. Sauvegarde et restauration

### 9.1. Sauvegarder la base de données

#### Dump SQL complet

```bash
# Dump de la base drupal
podman exec db pg_dump -U drupal drupal > backup_$(date +%Y%m%d_%H%M).sql

# Dump compressé
podman exec db pg_dump -U drupal drupal | gzip > backup_$(date +%Y%m%d_%H%M).sql.gz
```

#### Dump de toutes les bases

```bash
# Dump de toutes les bases (y compris les utilisateurs)
podman exec db pg_dumpall -U drupal > backup_all_$(date +%Y%m%d_%H%M).sql
```

#### Dump via Drush (si installé)

```bash
podman exec php bash -c "cd /var/www/html && vendor/bin/drush sql:dump > /tmp/backup.sql"
```

### 9.2. Restaurer la base de données

#### Depuis un dump SQL

```bash
# Restaurer
cat backup_20251125_1430.sql | podman exec -i db psql -U drupal drupal

# Depuis un dump compressé
gunzip -c backup_20251125_1430.sql.gz | podman exec -i db psql -U drupal drupal
```

#### Restaurer en recréant la base

```bash
# Supprimer la base existante
podman exec db psql -U drupal -c "DROP DATABASE drupal;"

# Recréer la base
podman exec db psql -U drupal -c "CREATE DATABASE drupal;"

# Restaurer
cat backup_20251125_1430.sql | podman exec -i db psql -U drupal drupal
```

### 9.3. Automatiser les sauvegardes

#### Script de sauvegarde quotidienne

```bash
# Créer un script backup-db.sh
cat > backup-db.sh <<'EOF'
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M)
podman exec db pg_dump -U drupal drupal | gzip > "$BACKUP_DIR/drupal_$DATE.sql.gz"
# Supprimer les sauvegardes de plus de 7 jours
find "$BACKUP_DIR" -name "drupal_*.sql.gz" -mtime +7 -delete
EOF

chmod +x backup-db.sh
```

#### Cron (Linux/WSL)

```bash
# Ajouter au crontab
crontab -e

# Sauvegarde quotidienne à 2h00
0 2 * * * /path/to/backup-db.sh
```

#### Task Scheduler (Windows)

Créer une tâche planifiée pour exécuter `backup-db.sh` via WSL.

---

## 10. Dépannage

### 10.1. Le conteneur ne démarre pas

#### Diagnostic

```bash
# Voir les logs
podman logs db

# Erreurs courantes :
# - "data directory has wrong ownership"
# - "database system was interrupted"
```

#### Solution 1 : Problème de permissions

```bash
# Supprimer le volume et recréer
podman volume rm drupal_db_data
podman compose up -d db
```

#### Solution 2 : Corruption des données

```bash
# Restaurer depuis une sauvegarde
cat backup_20251125_1430.sql | podman exec -i db psql -U drupal drupal
```

### 10.2. Erreur "Connection refused"

#### Symptôme

```
could not connect to server: Connection refused
```

#### Diagnostic

```bash
# Vérifier que le conteneur est démarré
podman ps | grep db

# Vérifier que PostgreSQL écoute
podman exec db netstat -tuln | grep 5432

# Tester la connexion
podman exec db pg_isready -U drupal
```

#### Solution

```bash
# Redémarrer PostgreSQL
podman restart db

# Vérifier les logs
podman logs db
```

### 10.3. Erreur "FATAL: password authentication failed"

#### Symptôme

```
FATAL: password authentication failed for user "drupal"
```

#### Solution

Vérifier les variables d'environnement :

```bash
# Inspecter le conteneur
podman inspect db | grep -A 5 Env

# Sortie attendue :
# "POSTGRES_USER=drupal"
# "POSTGRES_PASSWORD=drupal"
# "POSTGRES_DB=drupal"
```

Si les variables sont incorrectes, recréer le conteneur :

```bash
podman compose down
podman volume rm drupal_db_data
podman compose up -d
```

### 10.4. Erreur "database does not exist"

#### Symptôme

```
FATAL: database "drupal" does not exist
```

#### Solution

```bash
# Créer la base
podman exec db psql -U drupal -c "CREATE DATABASE drupal;"
```

### 10.5. Healthcheck "unhealthy"

#### Diagnostic

```bash
# Détails du healthcheck
podman inspect db --format='{{json .State.Health}}' | jq

# Tester manuellement
podman exec db pg_isready -U drupal
```

#### Solution

```bash
# Si pg_isready échoue, vérifier les logs
podman logs db

# Redémarrer PostgreSQL
podman restart db
```

### 10.6. Manque d'espace disque

#### Symptôme

```
ERROR: could not extend file
```

#### Diagnostic

```bash
# Voir l'espace utilisé
podman exec db df -h

# Taille de la base
podman exec db psql -U drupal -d drupal -c "SELECT pg_size_pretty(pg_database_size('drupal'));"
```

#### Solution

```bash
# Nettoyer les logs
podman exec db psql -U drupal -d drupal -c "VACUUM FULL;"

# Supprimer les anciennes sauvegardes
rm -rf logs/postgres/*.log.old
```

---

## 11. Optimisation

### 11.1. Configuration PostgreSQL (production)

Créer un fichier `postgresql.conf` personnalisé :

```ini
# postgresql.conf

# Mémoire
shared_buffers = 256MB              # 25% de la RAM
effective_cache_size = 1GB          # 50-75% de la RAM
work_mem = 16MB                     # Par opération de tri
maintenance_work_mem = 128MB        # Pour VACUUM, CREATE INDEX

# Connexions
max_connections = 100

# Logs
log_min_duration_statement = 1000   # Log des requêtes > 1s
log_line_prefix = '%t [%p]: '       # Timestamp et PID

# Checkpoint
checkpoint_completion_target = 0.9
wal_buffers = 16MB

# Autovacuum
autovacuum = on
autovacuum_max_workers = 3
```

Monter dans `podman-compose.yml` :

```yaml
db:
  volumes:
    - ./config/postgresql.conf:/etc/postgresql/postgresql.conf
  command: postgres -c config_file=/etc/postgresql/postgresql.conf
```

### 11.2. Index Drupal

```bash
# Créer des index pour améliorer les performances
podman exec db psql -U drupal -d drupal -c "
CREATE INDEX idx_node_status ON node_field_data (status);
CREATE INDEX idx_node_created ON node_field_data (created);
"
```

### 11.3. Maintenance régulière

```bash
# Analyser les tables (statistiques pour l'optimiseur)
podman exec db psql -U drupal -d drupal -c "ANALYZE;"

# Nettoyer et analyser
podman exec db psql -U drupal -d drupal -c "VACUUM ANALYZE;"

# Vacuum complet (plus lent, libère de l'espace)
podman exec db psql -U drupal -d drupal -c "VACUUM FULL;"
```

### 11.4. Limiter les ressources

Dans `podman-compose.yml` :

```yaml
db:
  deploy:
    resources:
      limits:
        cpus: '2.0'
        memory: 2G
      reservations:
        cpus: '1.0'
        memory: 1G
```

### 11.5. Monitoring

```bash
# Voir les connexions actives
podman exec db psql -U drupal -c "SELECT count(*) FROM pg_stat_activity;"

# Voir les requêtes en cours
podman exec db psql -U drupal -c "SELECT pid, query, state FROM pg_stat_activity WHERE state='active';"

# Statistiques des tables
podman exec db psql -U drupal -d drupal -c "SELECT schemaname,relname,n_tup_ins,n_tup_upd,n_tup_del FROM pg_stat_user_tables;"
```

---

## Aide-mémoire PostgreSQL

| Action | Commande |
|--------|----------|
| **Démarrer** | `podman compose up -d db` |
| **Arrêter** | `podman stop db` |
| **Redémarrer** | `podman restart db` |
| **Shell psql** | `podman exec -it db psql -U drupal -d drupal` |
| **Logs temps réel** | `podman logs -f db` |
| **Version** | `podman exec db psql -U drupal -c "SELECT version();"` |
| **Tester connexion** | `podman exec db pg_isready -U drupal` |
| **Dump base** | `podman exec db pg_dump -U drupal drupal > backup.sql` |
| **Restaurer base** | `cat backup.sql \| podman exec -i db psql -U drupal drupal` |
| **Taille base** | `podman exec db psql -U drupal -d drupal -c "SELECT pg_size_pretty(pg_database_size('drupal'));"` |

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2

**Voir aussi** :
- [Installation Podman](PODMAN_INSTALL.md) - Installation et configuration de Podman/WSL2
- [Configuration Apache](CONTAINER_APACHE_INSTALL.md) - Installation et configuration du conteneur Apache
- [Configuration PHP](CONTAINER_PHP_INSTALL.md) - Installation et configuration du conteneur PHP-FPM
- [Installation Drupal](DRUPAL_INSTALLATION.md) - Installation et gestion de Drupal
- [README.md](../README.md) - Guide de démarrage rapide
