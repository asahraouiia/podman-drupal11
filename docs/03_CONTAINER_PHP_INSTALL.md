# Guide d'installation et configuration - Conteneur PHP-FPM

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Dockerfile PHP-FPM](#2-dockerfile-php-fpm)
3. [Configuration PHP](#3-configuration-php)
4. [Extensions PHP](#4-extensions-php)
5. [Composer](#5-composer)
6. [Construction de l'image](#6-construction-de-limage)
7. [Démarrage du conteneur](#7-démarrage-du-conteneur)
8. [Gestion du conteneur](#8-gestion-du-conteneur)
9. [Logs PHP](#9-logs-php)
10. [Dépannage](#10-dépannage)
11. [Optimisation](#11-optimisation)

---

## 1. Vue d'ensemble

### 1.1. Rôle du conteneur PHP-FPM

Le conteneur PHP-FPM est le **moteur d'exécution PHP** dans l'architecture Drupal :

```
Apache (:8080) → PHP-FPM (:9000) → Drupal → PostgreSQL (:5432)
                     ↓
                 Composer
```

**Responsabilités** :
- Exécuter le code PHP de Drupal
- Gérer les dépendances via Composer
- Communiquer avec PostgreSQL
- Traiter les images (extension GD)
- Gérer le cache (OPcache, APCu)

### 1.2. Caractéristiques techniques

- **Image de base** : `php:8.3-fpm` (PHP officiel)
- **Port interne** : `9000` (FastCGI)
- **Protocole** : FastCGI Process Manager (FPM)
- **Utilisateur** : `www-data` (UID 1000)
- **Version PHP** : 8.3.x (requis par Drupal 11)

### 1.3. Emplacement des fichiers

```
docker/php/
├── Dockerfile        # Image personnalisée PHP-FPM
└── php.ini           # Configuration PHP
```

---

## 2. Dockerfile PHP-FPM

### 2.1. Contenu complet

**Emplacement** : `docker/php/Dockerfile`

```dockerfile
FROM php:8.3-fpm

# Installation des dépendances système et extensions PHP requises
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
    libzip-dev zlib1g-dev libicu-dev libonig-dev libpq-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd xml zip intl opcache bcmath

# Installation APCu (cache utilisateur)
RUN pecl install apcu && docker-php-ext-enable apcu

# Installation de Composer (gestionnaire de dépendances PHP)
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configuration utilisateur www-data
RUN usermod -u 1000 www-data || true

# Copie de la configuration PHP personnalisée
COPY php.ini /usr/local/etc/php/conf.d/zz-custom.ini

# Création du dossier de logs
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
```

### 2.2. Explication des étapes

#### Étape 1 : Image de base

```dockerfile
FROM php:8.3-fpm
```

Utilise l'image officielle PHP 8.3 avec PHP-FPM depuis Docker Hub.

#### Étape 2 : Dépendances système

```dockerfile
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
    libzip-dev zlib1g-dev libicu-dev libonig-dev libpq-dev
```

**Bibliothèques installées** :

| Paquet | Utilisation |
|--------|-------------|
| `git` | Composer (téléchargement de packages) |
| `curl`, `unzip` | Composer (téléchargement et extraction) |
| `libpng-dev`, `libjpeg-dev`, `libfreetype6-dev` | Extension GD (images) |
| `libxml2-dev` | Extension XML |
| `libzip-dev`, `zlib1g-dev` | Extension ZIP |
| `libicu-dev` | Extension Intl (internationalisation) |
| `libonig-dev` | Extension mbstring (chaînes multi-octets) |
| `libpq-dev` | Extension PostgreSQL |

#### Étape 3 : Extensions PHP

```dockerfile
docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd xml zip intl opcache bcmath
```

- `docker-php-ext-configure gd` : Configure GD avec support JPEG et FreeType
- `docker-php-ext-install -j$(nproc)` : Compile en parallèle (utilise tous les CPU)

#### Étape 4 : APCu (PECL)

```dockerfile
RUN pecl install apcu && docker-php-ext-enable apcu
```

APCu est un cache utilisateur pour améliorer les performances.

#### Étape 5 : Composer

```dockerfile
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer
```

Installe Composer globalement dans `/usr/local/bin/composer`.

#### Étape 6 : Utilisateur www-data

```dockerfile
RUN usermod -u 1000 www-data || true
```

Change l'UID de `www-data` pour correspondre à l'utilisateur hôte (évite les problèmes de permissions).

#### Étape 7 : Configuration PHP

```dockerfile
COPY php.ini /usr/local/etc/php/conf.d/zz-custom.ini
```

Copie la configuration personnalisée. Le préfixe `zz-` assure qu'elle est chargée en dernier.

#### Étape 8 : Dossier de logs

```dockerfile
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php
```

Crée le répertoire pour les logs PHP-FPM.

---

## 3. Configuration PHP

### 3.1. Contenu complet

**Emplacement** : `docker/php/php.ini`

```ini
; ====================================
; Configuration PHP pour Drupal 11
; ====================================

[PHP]
; Mémoire
memory_limit = 512M
max_execution_time = 300

; Upload de fichiers
upload_max_filesize = 64M
post_max_size = 64M
file_uploads = On

; Affichage des erreurs (développement uniquement)
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php/php-fpm.log

; OPCache (performance)
opcache.enable = 1
opcache.memory_consumption = 192
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 50000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2

; APCu (cache utilisateur)
apc.enabled = 1
apc.shm_size = 64M
apc.enable_cli = 1

; Realpath cache (performance)
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; PCRE (expressions régulières)
pcre.backtrack_limit = 1000000
pcre.recursion_limit = 100000

; Logs PHP-FPM
[www]
catch_workers_output = yes
php_admin_value[error_log] = /var/log/php/php-fpm.log
php_admin_flag[log_errors] = on
```

### 3.2. Explication des directives

#### Mémoire

```ini
memory_limit = 512M
max_execution_time = 300
```

- `memory_limit` : Mémoire max par script (Drupal peut être gourmand)
- `max_execution_time` : Temps max d'exécution (5 minutes pour les migrations, etc.)

#### Upload de fichiers

```ini
upload_max_filesize = 64M
post_max_size = 64M
```

Permet l'upload de fichiers jusqu'à 64 MB (images, documents).

#### Affichage des erreurs

```ini
display_errors = On
error_reporting = E_ALL
```

**⚠️ DÉVELOPPEMENT UNIQUEMENT**

En production, mettre :
```ini
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

#### OPcache

```ini
opcache.enable = 1
opcache.memory_consumption = 192
opcache.max_accelerated_files = 50000
```

**Cache d'opcodes** :
- Stocke le code PHP compilé en mémoire
- Évite la recompilation à chaque requête
- **Gain de performance : +70%**

#### APCu

```ini
apc.enabled = 1
apc.shm_size = 64M
```

**Cache utilisateur** :
- Stockage clé-valeur en mémoire partagée
- Utilisé par Drupal pour le cache des entités, configurations, etc.

#### Realpath cache

```ini
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

Cache les chemins résolus pour améliorer les performances I/O.

---

## 4. Extensions PHP

### 4.1. Extensions installées

| Extension | Description | Drupal |
|-----------|-------------|--------|
| `pdo` | Interface base de données | **Obligatoire** |
| `pdo_pgsql` | Driver PostgreSQL | **Obligatoire** |
| `pgsql` | Fonctions PostgreSQL natives | Recommandé |
| `gd` | Manipulation d'images (JPEG, PNG, GIF) | **Obligatoire** |
| `xml` | Traitement XML | **Obligatoire** |
| `zip` | Compression/décompression ZIP | Recommandé |
| `intl` | Internationalisation (i18n) | Recommandé |
| `opcache` | Cache d'opcodes | **Recommandé** |
| `bcmath` | Mathématiques de précision | Recommandé |
| `apcu` | Cache utilisateur | **Recommandé** |

### 4.2. Vérifier les extensions chargées

**Script automatique de vérification :**

```bash
# Vérifier toutes les extensions requises
./scripts/check-php-extensions.sh

# Sortie attendue :
# ✅ Toutes les extensions requises sont installées
# Détails : pdo, pdo_pgsql, pgsql, gd (bundled 2.1.0), xml, zip, intl, opcache, bcmath, apcu
```

**Vérifications manuelles :**

```bash
# Liste complète des extensions
podman exec php php -m

# Vérifier une extension spécifique
podman exec php php -m | grep pdo

# Voir les détails d'une extension
podman exec php php -i | grep -A 10 "^gd$"

# Vérifier GD spécifiquement
podman exec php php -r "print_r(gd_info());"
```

**Vérification GD (important pour images Drupal) :**

```bash
podman exec php php -i | grep -A 15 "^gd$"

# Sortie attendue :
# GD Support => enabled
# GD Version => bundled (2.1.0 compatible)
# FreeType Support => enabled
# JPEG Support => enabled
# PNG Support => enabled
# GIF Read Support => enabled
# GIF Create Support => enabled
```

### 4.3. Vérifier la version PHP

```bash
# Version PHP
podman exec php php -v

# Sortie attendue :
# PHP 8.3.x (cli) (built: ...)
# Copyright (c) The PHP Group
# Zend Engine v4.3.x, Copyright (c) Zend Technologies
#     with Zend OPcache v8.3.x, Copyright (c), by Zend Technologies
```

### 4.4. Ajouter une extension

#### Via PECL (exemple : Redis)

```dockerfile
# Dans Dockerfile
RUN pecl install redis && docker-php-ext-enable redis
```

#### Via docker-php-ext-install (exemple : sockets)

```dockerfile
# Dans Dockerfile
RUN docker-php-ext-install sockets
```

Puis reconstruire l'image :

```bash
podman compose build --no-cache php
podman compose up -d
```

---

## 5. Composer

### 5.1. Version installée

```bash
# Vérifier la version Composer
podman exec php composer --version

# Sortie attendue :
# Composer version 2.x.x
```

### 5.2. Utilisation de Composer

#### Installer les dépendances Drupal

```bash
# Installer Drupal (première fois)
podman exec php bash -c "cd /var/www/html && COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 . --no-interaction"

# Installer les dépendances existantes
podman exec php bash -c "cd /var/www/html && composer install"
```

#### Ajouter un module

```bash
# Ajouter un module Drupal
podman exec php bash -c "cd /var/www/html && composer require drupal/admin_toolbar"

# Ajouter plusieurs modules
podman exec php bash -c "cd /var/www/html && composer require drupal/pathauto drupal/token drupal/metatag"
```

#### Mettre à jour les dépendances

```bash
# Mettre à jour tous les packages
podman exec php bash -c "cd /var/www/html && composer update"

# Mettre à jour un package spécifique
podman exec php bash -c "cd /var/www/html && composer update drupal/core 'drupal/core-*' --with-all-dependencies"
```

#### Supprimer un package

```bash
podman exec php bash -c "cd /var/www/html && composer remove drupal/admin_toolbar"
```

### 5.3. Résoudre les erreurs mémoire

Si Composer échoue avec une erreur mémoire :

```bash
# Utiliser COMPOSER_MEMORY_LIMIT=-1
podman exec php bash -c "cd /var/www/html && COMPOSER_MEMORY_LIMIT=-1 composer install"
```

Ou augmenter `memory_limit` dans `php.ini` :

```ini
memory_limit = 1G
```

### 5.4. Cache Composer

```bash
# Vider le cache Composer
podman exec php composer clear-cache

# Voir l'emplacement du cache
podman exec php composer config cache-dir
```

---

## 6. Construction de l'image

### 6.1. Construction initiale

```bash
# Depuis la racine du projet
podman compose build php

# Ou avec Podman directement
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php/
```

**Durée** : 5-10 minutes (première fois, dépend de la connexion)

### 6.2. Reconstruire après modification

```bash
# Sans cache (recommandé)
podman compose build --no-cache php

# Avec le script
./scripts/start-containers.sh --rebuild
```

### 6.3. Vérifier l'image créée

```bash
# Lister les images
podman images

# Sortie attendue :
# REPOSITORY              TAG         IMAGE ID      CREATED       SIZE
# localhost/myphp         8.3-dev     abc123def456  2 hours ago   520 MB

# Inspecter l'image
podman inspect localhost/myphp:8.3-dev
```

---

## 7. Démarrage du conteneur

### 7.1. Via Podman Compose (recommandé)

```bash
# Démarrer tous les services
podman compose up -d

# Démarrer uniquement PHP
podman compose up -d php
```

### 7.2. Via Podman directement

```bash
podman run -d \
  --name php \
  -v ./src:/var/www/html \
  -v ./logs/php:/var/log/php \
  --network podman_drupal_net \
  localhost/myphp:8.3-dev
```

### 7.3. Vérifier le démarrage

```bash
# État du conteneur
podman ps | grep php

# Sortie attendue :
# CONTAINER ID  IMAGE                        STATUS
# abc123def456  localhost/myphp:8.3-dev      Up 2 minutes (healthy)

# Logs de démarrage
podman logs php
```

### 7.4. Tester PHP-FPM

```bash
# Vérifier que PHP-FPM écoute sur le port 9000
podman exec php netstat -tuln | grep 9000

# Sortie attendue :
# tcp   0   0 0.0.0.0:9000   0.0.0.0:*   LISTEN

# Tester la configuration PHP-FPM
podman exec php php-fpm -t

# Sortie attendue :
# NOTICE: configuration file /usr/local/etc/php-fpm.conf test is successful
```

---

## 8. Gestion du conteneur

### 8.1. Commandes de base

```bash
# Arrêter PHP
podman stop php

# Démarrer PHP
podman start php

# Redémarrer PHP
podman restart php

# Voir les processus dans le conteneur
podman top php
```

### 8.2. Accès shell

```bash
# Shell interactif
podman exec -it php bash

# Commandes utiles dans le conteneur :
php -v                          # Version PHP
php -m                          # Extensions chargées
php -i                          # phpinfo() en CLI
composer --version              # Version Composer
php-fpm -v                      # Version PHP-FPM
php-fpm -t                      # Tester la configuration
```

### 8.3. Exécuter des commandes PHP

```bash
# Exécuter un script PHP
podman exec php php /var/www/html/web/index.php

# Exécuter du code PHP inline
podman exec php php -r "echo 'Hello World';"

# Tester la connexion PostgreSQL
podman exec php php -r "new PDO('pgsql:host=db;dbname=drupal', 'drupal', 'drupal');"
```

### 8.4. Drush (si installé)

```bash
# Vider les caches Drupal
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cache:rebuild"

# Mettre à jour la base de données
podman exec php bash -c "cd /var/www/html && vendor/bin/drush updatedb"

# Statut Drupal
podman exec php bash -c "cd /var/www/html && vendor/bin/drush status"
```

### 8.5. Healthcheck

Le conteneur inclut un healthcheck automatique :

```yaml
healthcheck:
  test: ["CMD-SHELL", "php-fpm -t || exit 1"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

**Vérifier l'état** :

```bash
# État de santé
podman inspect php --format='{{.State.Health.Status}}'
# Attendu: healthy

# Détails du healthcheck
podman inspect php --format='{{json .State.Health}}' | jq
```

---

## 9. Logs PHP

### 9.1. Logs en temps réel

```bash
# Tous les logs PHP
podman logs -f php

# Logs PHP-FPM uniquement
tail -f logs/php/php-fpm.log
```

### 9.2. Logs historiques

```bash
# Dernières 50 lignes
podman logs --tail 50 php

# Depuis une date
podman logs --since 2025-11-25T10:00:00 php

# Avec timestamps
podman logs -t php
```

### 9.3. Collecter les logs

```bash
# Script de collecte automatique
./scripts/logs-collect.sh

# Les logs sont copiés dans logs/php/
ls -lh logs/php/
# php-fpm.log
```

### 9.4. Analyser les logs

```bash
# Erreurs PHP
grep -i "error" logs/php/php-fpm.log

# Warnings PHP
grep -i "warning" logs/php/php-fpm.log

# Erreurs fatales
grep -i "fatal" logs/php/php-fpm.log

# Erreurs de mémoire
grep -i "memory" logs/php/php-fpm.log
```

---

## 10. Dépannage

### 10.1. Erreur "Unable to connect to PostgreSQL"

#### Symptôme

```
SQLSTATE[08006] [7] could not connect to server
```

#### Diagnostic

```bash
# Vérifier que PostgreSQL est démarré
podman ps | grep db

# Tester la connexion depuis PHP
podman exec php ping -c 2 db

# Tester la connexion PostgreSQL
podman exec php php -r "new PDO('pgsql:host=db;dbname=drupal', 'drupal', 'drupal');"
```

#### Solution

```bash
# Redémarrer PostgreSQL
podman restart db

# Vérifier les paramètres de connexion dans settings.php
podman exec php cat /var/www/html/web/sites/default/settings.php | grep -A 10 databases
```

### 10.2. Extension PHP manquante

#### Symptôme

```
Fatal error: Uncaught Error: Call to undefined function gd_info()
```

#### Diagnostic

```bash
# Vérifier si l'extension est chargée
podman exec php php -m | grep gd
```

#### Solution

```bash
# Si l'extension est manquante, reconstruire l'image
podman compose build --no-cache php
podman compose up -d
```

### 10.3. Composer échoue avec erreur mémoire

#### Symptôme

```
Fatal error: Allowed memory size of 134217728 bytes exhausted
```

#### Solution

```bash
# Utiliser COMPOSER_MEMORY_LIMIT=-1
podman exec php bash -c "cd /var/www/html && COMPOSER_MEMORY_LIMIT=-1 composer install"

# Ou augmenter memory_limit dans php.ini
# memory_limit = 1G
```

### 10.4. PHP-FPM ne démarre pas

#### Diagnostic

```bash
# Voir les logs d'erreur
podman logs php

# Tester la configuration
podman exec php php-fpm -t
```

**Erreurs courantes** :

- `Syntax error` : Erreur dans `php.ini`
- `Port 9000 already in use` : Port déjà utilisé (impossible dans un conteneur)

#### Solution

```bash
# Corriger la configuration
nano docker/php/php.ini

# Reconstruire
podman compose build --no-cache php
podman compose up -d
```

### 10.5. Permission denied sur /var/www/html

#### Symptôme

```
Warning: file_put_contents(/var/www/html/test.txt): failed to open stream: Permission denied
```

#### Diagnostic

```bash
# Vérifier les permissions
podman exec php ls -la /var/www/html
```

#### Solution

```bash
# Donner les permissions à www-data
podman exec php chown -R www-data:www-data /var/www/html

# Ou depuis l'hôte
sudo chown -R 1000:1000 src/
```

### 10.6. OPcache ne fonctionne pas

#### Diagnostic

```bash
# Vérifier si OPcache est activé
podman exec php php -i | grep opcache.enable

# Sortie attendue :
# opcache.enable => On => On
```

#### Solution

Si OPcache est désactivé, vérifier `php.ini` :

```ini
opcache.enable = 1
```

Reconstruire :

```bash
podman compose build --no-cache php
podman compose up -d
```

---

## 11. Optimisation

### 11.1. OPcache (production)

Pour la production, optimiser OPcache :

```ini
; Production OPcache settings
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 100000
opcache.validate_timestamps = 0       ; Ne pas vérifier les changements (plus rapide)
opcache.revalidate_freq = 0
opcache.fast_shutdown = 1
```

**⚠️ En développement, laisser `validate_timestamps = 1`**

### 11.2. APCu (production)

```ini
; Production APCu settings
apc.enabled = 1
apc.shm_size = 128M                   ; Augmenter la taille
apc.ttl = 7200                        ; TTL par défaut
apc.gc_ttl = 3600                     ; Garbage collection
```

### 11.3. Réduire la taille de l'image

#### Utiliser Alpine Linux (image plus petite)

```dockerfile
FROM php:8.3-fpm-alpine

# Alpine utilise apk au lieu de apt-get
RUN apk add --no-cache \
    git curl unzip libpng-dev libjpeg-turbo-dev freetype-dev ...
```

**Taille** : ~150 MB au lieu de ~520 MB

### 11.4. Limiter les ressources

Dans `podman-compose.yml` :

```yaml
php:
  deploy:
    resources:
      limits:
        cpus: '2.0'
        memory: 1G
      reservations:
        cpus: '1.0'
        memory: 512M
```

### 11.5. Multi-stage build (réduire la taille)

```dockerfile
# Stage 1: Builder
FROM php:8.3-fpm AS builder
RUN apt-get update && apt-get install -y ...
RUN docker-php-ext-install ...

# Stage 2: Runtime
FROM php:8.3-fpm
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php /usr/local/etc/php
...
```

---

## Aide-mémoire PHP

| Action | Commande |
|--------|----------|
| **Build image** | `podman compose build php` |
| **Démarrer** | `podman compose up -d php` |
| **Arrêter** | `podman stop php` |
| **Redémarrer** | `podman restart php` |
| **Shell** | `podman exec -it php bash` |
| **Logs temps réel** | `podman logs -f php` |
| **Version PHP** | `podman exec php php -v` |
| **Extensions** | `podman exec php php -m` |
| **Tester config** | `podman exec php php-fpm -t` |
| **Composer** | `podman exec php composer --version` |
| **Drush** | `podman exec php bash -c "cd /var/www/html && vendor/bin/drush"` |

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2

**Voir aussi** :
- [Installation Podman](PODMAN_INSTALL.md) - Installation et configuration de Podman/WSL2
- [Configuration Apache](CONTAINER_APACHE_INSTALL.md) - Installation et configuration du conteneur Apache
- [Configuration PostgreSQL](CONTAINER_POSTGRESQL_INSTALL.md) - Installation et configuration du conteneur PostgreSQL
- [Installation Drupal](DRUPAL_INSTALLATION.md) - Installation et gestion de Drupal
- [README.md](../README.md) - Guide de démarrage rapide
