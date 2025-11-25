# Guide d'installation et de configuration - Podman Drupal 11

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Prérequis et installation](#1-prérequis-et-installation)
2. [Structure du projet](#2-structure-du-projet)
3. [Fichiers de configuration](#3-fichiers-de-configuration)
4. [Commandes d'utilisation](#4-commandes-dutilisation)
5. [Vérifications et tests](#5-vérifications-et-tests)
6. [Dépannage](#6-dépannage)
7. [Sécurité et bonnes pratiques](#7-sécurité-et-bonnes-pratiques)

---

## 1. Prérequis et installation

### 1.1. Système d'exploitation

**Windows 11 Home** avec WSL2 (Windows Subsystem for Linux version 2).

### 1.2. Installation de WSL2

#### 1.2.1. Vérifier si WSL2 est installé

```bash
wsl --status
```

Si la commande retourne une erreur, WSL n'est pas installé.

#### 1.2.2. Installer WSL2 et une distribution Linux

```powershell
# Ouvrir PowerShell en tant qu'administrateur
wsl --install

# Choisir Ubuntu (recommandé)
wsl --install -d Ubuntu

# Redémarrer l'ordinateur
```

#### 1.2.3. Configurer la distribution Ubuntu

Après le redémarrage :

1. Ubuntu s'ouvrira automatiquement
2. Créer un nom d'utilisateur (ex: `drupaldev`)
3. Créer un mot de passe

#### 1.2.4. Vérifier la version WSL

```bash
wsl --list --verbose
```

Assurez-vous que la colonne VERSION indique `2`.

Si la distribution est en version 1 :

```powershell
wsl --set-version Ubuntu 2
```

### 1.3. Installation de Podman Desktop

#### 1.3.1. Télécharger Podman Desktop

1. Aller sur [https://podman-desktop.io/downloads](https://podman-desktop.io/downloads)
2. Télécharger la version Windows
3. Exécuter l'installateur

#### 1.3.2. Initialiser Podman Machine

Après installation :

1. Ouvrir Podman Desktop
2. Cliquer sur "Initialize" pour créer la machine Podman
3. Attendre la fin de l'initialisation (2-3 minutes)

#### 1.3.3. Vérifier l'installation (ligne de commande)

```bash
# Ouvrir Git Bash ou WSL

# Vérifier la version Podman
podman --version

# Sortie attendue: podman version 4.x.x ou supérieur

# Vérifier l'état de la machine
podman machine list

# Sortie attendue:
# NAME                     VM TYPE     CREATED      LAST UP            CPUS        MEMORY      DISK SIZE
# podman-machine-default*  wsl         2 hours ago  Currently running  2           2GiB        100GiB
```

#### 1.3.4. Test de fonctionnement

```bash
# Tester Podman avec une image simple
podman run --rm hello-world

# Sortie attendue:
# Hello from Docker!
# This message shows that your installation appears to be working correctly.
```

Si le test échoue, exécuter :

```bash
# Redémarrer la machine Podman
wsl --shutdown
podman machine start
```

### 1.4. Installation de Git (si nécessaire)

```bash
# Vérifier si Git est installé
git --version

# Si non installé, télécharger depuis:
# https://git-scm.com/download/win
```

### 1.5. Cloner le projet

```bash
# Créer un dossier pour vos projets
mkdir -p ~/projects
cd ~/projects

# Cloner le dépôt
git clone https://github.com/asahraouiia/podman-drupal11.git
cd podman-drupal11
```

---

## 2. Structure du projet

### 2.1. Arborescence complète

```
podman-drupal11/
│
├── docker/                          # Fichiers Docker/Podman
│   ├── apache/                      # Configuration Apache
│   │   ├── Dockerfile               # Image Apache personnalisée
│   │   └── vhost.conf               # VirtualHost Apache
│   └── php/                         # Configuration PHP
│       ├── Dockerfile               # Image PHP-FPM personnalisée
│       └── php.ini                  # Configuration PHP
│
├── src/                             # Code source Drupal
│   ├── composer.json                # Dépendances Composer (versionné)
│   ├── composer.lock                # Versions exactes (versionné)
│   ├── web/                         # Racine web Drupal (ignoré par Git)
│   │   ├── index.php
│   │   ├── core/                    # Core Drupal
│   │   ├── modules/                 # Modules contrib/custom
│   │   ├── themes/                  # Thèmes
│   │   └── sites/
│   │       └── default/
│   │           ├── files/           # Fichiers uploadés (persistant)
│   │           └── settings.php     # Configuration DB
│   ├── vendor/                      # Dépendances PHP (ignoré par Git)
│   └── recipes/                     # Recettes Drupal (ignoré par Git)
│
├── logs/                            # Logs des conteneurs
│   ├── apache/
│   │   ├── access.log
│   │   └── error.log
│   ├── php/
│   │   └── php-fpm.log
│   └── postgres/
│       └── postgresql.log
│
├── config/                          # Configurations exportées Drupal (optionnel)
│   └── sync/                        # Configuration Drupal exportée
│
├── scripts/                         # Scripts d'automatisation
│   ├── init-podman.sh               # Initialise Podman/WSL
│   ├── start-containers.sh          # Démarre tous les conteneurs
│   ├── stop-containers.sh           # Arrête tous les conteneurs
│   ├── drupal-install.sh            # Installe Drupal via Composer
│   ├── health-check.sh              # Vérifie la santé des conteneurs
│   ├── logs-collect.sh              # Collecte les logs
│   └── manage-apache-modules.sh     # Gère les modules Apache
│
├── docs/                            # Documentation
│   └── INSTALLATION.md              # Ce fichier
│
├── podman-compose.yml               # Orchestration des conteneurs
├── Makefile                         # Raccourcis de commandes
├── .gitignore                       # Fichiers ignorés par Git
├── .gitattributes                   # Gestion des fins de ligne
├── CHANGELOG.md                     # Historique des changements
└── README.md                        # Documentation principale
```

### 2.2. Description des dossiers importants

#### 2.2.1. `/docker`
Contient les Dockerfiles et configurations pour construire les images personnalisées Apache et PHP.

#### 2.2.2. `/src`
**Racine du code Drupal**. Seuls `composer.json` et `composer.lock` sont versionnés. Le reste (`web/`, `vendor/`, `recipes/`) est généré par Composer et ignoré par Git.

#### 2.2.3. `/src/web`
**Racine web publique** (DocumentRoot). C'est ici que se trouve le code Drupal installé.

#### 2.2.4. `/src/web/sites/default/files`
**Dossier des fichiers uploadés**. Volume persistant monté depuis le conteneur. Les images, documents uploadés par les utilisateurs sont stockés ici.

#### 2.2.5. `/logs`
**Logs de tous les conteneurs**. Organisés par service (apache/, php/, postgres/).

#### 2.2.6. `/config/sync`
(Optionnel) Dossier pour exporter/importer la configuration Drupal entre environnements.

#### 2.2.7. `/scripts`
Scripts Bash pour automatiser les tâches courantes (démarrage, arrêt, installation, maintenance).

---

## 3. Fichiers de configuration

### 3.1. Fichier `podman-compose.yml`

**Emplacement** : `/podman-compose.yml`  
**Description** : Fichier d'orchestration qui définit les 3 conteneurs (Apache, PHP-FPM, PostgreSQL) et leurs relations.

```yaml
version: '3.8'

services:
  # ============================================
  # Service Apache (Serveur web)
  # ============================================
  web:
    build:
      context: ./docker/apache
      dockerfile: Dockerfile
    image: myapache:latest
    container_name: web
    ports:
      - "8080:80"                    # Accès externe:interne
    volumes:
      - ./src:/var/www/html          # Code Drupal
      - ./logs/apache:/var/log/apache2  # Logs Apache
    depends_on:
      - php                          # Démarre après PHP
    networks:
      - drupal_net
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  # ============================================
  # Service PHP-FPM (Traitement PHP)
  # ============================================
  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    image: myphp:8.3-dev
    container_name: php
    volumes:
      - ./src:/var/www/html          # Code Drupal (partagé avec Apache)
      - ./logs/php:/var/log/php      # Logs PHP-FPM
    networks:
      - drupal_net
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "php-fpm -t || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  # ============================================
  # Service PostgreSQL (Base de données)
  # ============================================
  db:
    image: postgres:16               # Version minimale requise: PostgreSQL 16
    container_name: db
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

# ============================================
# Volumes (Données persistantes)
# ============================================
volumes:
  drupal_db_data:                    # Volume pour PostgreSQL
    driver: local

# ============================================
# Réseaux (Communication inter-conteneurs)
# ============================================
networks:
  drupal_net:                        # Réseau privé pour les 3 services
    driver: bridge
```

**Points importants** :

- **Port 8080** : Apache est accessible sur `http://localhost:8080`
- **PostgreSQL 16** : Version minimale requise par Drupal 11
- **Volumes partagés** : `/src` est monté sur Apache ET PHP
- **Healthchecks** : Surveillance automatique de l'état des conteneurs
- **Restart policy** : Les conteneurs redémarrent automatiquement sauf si arrêtés manuellement

### 3.2. Dockerfile Apache

**Emplacement** : `/docker/apache/Dockerfile`  
**Description** : Image Apache personnalisée avec mod_rewrite, mod_proxy pour PHP-FPM.

```dockerfile
FROM httpd:2.4

# Installation des utilitaires Apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2-utils curl && rm -rf /var/lib/apt/lists/* || true

# Copie de la configuration VirtualHost
COPY vhost.conf /usr/local/apache2/conf/sites-enabled/vhost.conf

# Activation des VirtualHosts
RUN sed -i '/^#IncludeOptional conf\/extra\/httpd-vhosts.conf/ s/^#//' \
    /usr/local/apache2/conf/httpd.conf || true

# Configuration ServerName global
RUN echo "ServerName localhost" >> /usr/local/apache2/conf/httpd.conf || true

# Chargement des modules requis et recommandés
RUN printf '%s\n' \
    'LoadModule proxy_module modules/mod_proxy.so' \
    'LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so' \
    'LoadModule rewrite_module modules/mod_rewrite.so' \
    'LoadModule deflate_module modules/mod_deflate.so' \
    'LoadModule headers_module modules/mod_headers.so' \
    'LoadModule expires_module modules/mod_expires.so' \
    >> /usr/local/apache2/conf/httpd.conf || true

# Inclusion des VirtualHosts
RUN mkdir -p /usr/local/apache2/conf/sites-enabled \
    && echo 'IncludeOptional conf/sites-enabled/*.conf' \
    >> /usr/local/apache2/conf/httpd.conf

# Création du dossier de logs
RUN mkdir -p /var/log/apache2

EXPOSE 80

CMD ["httpd-foreground"]
```

**Modules chargés** :

- `mod_proxy` + `mod_proxy_fcgi` : **OBLIGATOIRES** pour communiquer avec PHP-FPM
- `mod_rewrite` : **OBLIGATOIRE** pour les clean URLs Drupal
- `mod_deflate`, `mod_headers`, `mod_expires` : **RECOMMANDÉS** pour la performance

### 3.3. VirtualHost Apache

**Emplacement** : `/docker/apache/vhost.conf`  
**Description** : Configuration du VirtualHost pointant vers Drupal.

```apacheconf
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/web

    <Directory /var/www/html/web>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Transfert des requêtes PHP vers PHP-FPM
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://php:9000"
    </FilesMatch>

    # Logs Apache (accessibles dans /logs/apache)
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
```

**Points importants** :

- `DocumentRoot` : `/var/www/html/web` (racine web Drupal)
- `AllowOverride All` : Permet au `.htaccess` de Drupal de fonctionner
- `proxy:fcgi://php:9000` : Transfère PHP vers le conteneur `php` sur le port 9000

### 3.4. Dockerfile PHP

**Emplacement** : `/docker/php/Dockerfile`  
**Description** : Image PHP-FPM avec extensions Drupal et Composer.

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

**Extensions installées** :

- `pdo`, `pdo_pgsql`, `pgsql` : Connexion PostgreSQL
- `gd` : Manipulation d'images
- `xml`, `zip` : Manipulation fichiers
- `intl`, `opcache`, `bcmath` : Requis par Drupal
- `apcu` : Cache utilisateur (performance)

### 3.5. Configuration PHP

**Emplacement** : `/docker/php/php.ini`  
**Description** : Paramètres PHP optimisés pour Drupal.

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

**⚠️ ATTENTION** : `display_errors = On` est pour le développement uniquement. Désactiver en production.

### 3.6. Fichier `.gitignore`

**Emplacement** : `/.gitignore`  
**Description** : Fichiers à ignorer par Git.

```gitignore
# Code Drupal généré
src/web/
src/vendor/
src/recipes/

# Fichiers Drupal sensibles
src/web/sites/*/settings.php
src/web/sites/*/settings.local.php
src/web/sites/*/services.yml
src/web/sites/*/files/
src/web/sites/*/private/

# Logs
logs/
*.log

# OS
.DS_Store
Thumbs.db
desktop.ini

# IDE
.idea/
.vscode/
*.swp
*.swo

# Docker/Podman
*.bak

# Temporaires
*.tmp
*.temp
```

---

## 4. Commandes d'utilisation

### 4.1. Installation initiale (première fois)

#### 4.1.1. Initialiser Podman/WSL

```bash
# Exécuter le script d'initialisation
chmod +x scripts/*.sh
./scripts/init-podman.sh
```

**Ce que fait ce script** :
- Vérifie que WSL est actif
- Démarre la machine Podman si nécessaire
- Vérifie la connectivité Podman

#### 4.1.2. Créer les dossiers de logs

```bash
# Créer la structure de logs
mkdir -p logs/{apache,php,postgres}
```

#### 4.1.3. Démarrer les conteneurs

```bash
# Démarrer tous les conteneurs (construit les images si nécessaire)
./scripts/start-containers.sh --rebuild

# Ou via Makefile
make start
```

**Durée estimée** : 5-10 minutes (téléchargement et construction des images)

#### 4.1.4. Installer Drupal

```bash
# Installer Drupal 11 via Composer
./scripts/drupal-install.sh

# Ou via Makefile
make drupal-install
```

**Durée estimée** : 5-15 minutes (selon la connexion internet)

#### 4.1.5. Configurer la base de données

1. Ouvrir le navigateur : `http://localhost:8080`
2. Suivre l'assistant d'installation Drupal
3. Paramètres de base de données :
   - **Type** : PostgreSQL
   - **Nom** : `drupal`
   - **Utilisateur** : `drupal`
   - **Mot de passe** : `drupal`
   - **Hôte** : `db`
   - **Port** : `5432`

### 4.2. Démarrage (projet déjà installé)

#### 4.2.1. Démarrage rapide

```bash
# Méthode 1 : Script complet (vérifie Podman + démarre)
./scripts/start-containers.sh

# Méthode 2 : Makefile
make start

# Méthode 3 : Podman Compose directement
podman compose -f podman-compose.yml up -d
```

**Durée** : 10-30 secondes

#### 4.2.2. Vérifier que tout fonctionne

```bash
# Vérifier l'état des conteneurs
podman ps

# Sortie attendue :
# CONTAINER ID  IMAGE                      COMMAND           STATUS
# abc123def456  localhost/myapache:latest  httpd-foreground  Up 2 minutes
# def456ghi789  localhost/myphp:8.3-dev    php-fpm          Up 2 minutes
# ghi789jkl012  postgres:16                postgres         Up 2 minutes
```

### 4.3. Arrêt des conteneurs

```bash
# Méthode 1 : Script
./scripts/stop-containers.sh

# Méthode 2 : Makefile
make stop

# Méthode 3 : Podman Compose
podman compose -f podman-compose.yml down
```

**Note** : Les données de la base sont préservées (volume `drupal_db_data`).

### 4.4. Suppression complète (reset)

```bash
# Arrêter et supprimer les conteneurs + volumes
podman compose -f podman-compose.yml down -v

# Supprimer les images
podman rmi myapache:latest myphp:8.3-dev

# Nettoyer les logs
rm -rf logs/*/*.log

# Nettoyer le code Drupal
rm -rf src/web src/vendor src/recipes
```

**⚠️ ATTENTION** : Cette opération supprime la base de données et tout le code installé !

### 4.5. Gestion des logs

#### 4.5.1. Collecter tous les logs

```bash
# Script de collecte automatique
./scripts/logs-collect.sh

# Les logs sont copiés dans logs/
```

#### 4.5.2. Consulter les logs en temps réel

```bash
# Logs Apache
tail -f logs/apache/error.log
tail -f logs/apache/access.log

# Logs PHP
tail -f logs/php/php-fpm.log

# Logs PostgreSQL
tail -f logs/postgres/postgresql.log

# Logs de tous les conteneurs via Podman
podman compose -f podman-compose.yml logs -f
```

#### 4.5.3. Logs d'un conteneur spécifique

```bash
# Apache
podman logs -f web

# PHP
podman logs -f php

# PostgreSQL
podman logs -f db
```

### 4.6. Vérification de santé

#### 4.6.1. Script de vérification automatique

```bash
# Vérifier la santé de tous les conteneurs
./scripts/health-check.sh

# Via Makefile
make health-check
```

#### 4.6.2. Vérification manuelle

```bash
# État des healthchecks
podman ps --format "table {{.Names}}\t{{.Status}}"

# Sortie attendue :
# NAMES  STATUS
# web    Up 5 minutes (healthy)
# php    Up 5 minutes (healthy)
# db     Up 5 minutes (healthy)

# Détails d'un conteneur
podman inspect web | grep -A 10 Health
```

### 4.7. Connexion à la base de données

#### 4.7.1. CLI (depuis le conteneur PostgreSQL)

```bash
# Se connecter au conteneur
podman exec -it db psql -U drupal -d drupal

# Commandes PostgreSQL utiles :
# \dt         - Lister les tables
# \l          - Lister les bases de données
# \q          - Quitter
```

#### 4.7.2. CLI (depuis l'hôte avec psql)

Si `psql` est installé sur Windows :

```bash
psql -h localhost -p 5432 -U drupal -d drupal
# Password: drupal
```

#### 4.7.3. GUI (pgAdmin, DBeaver, etc.)

Paramètres de connexion :

- **Hôte** : `localhost`
- **Port** : `5432`
- **Base de données** : `drupal`
- **Utilisateur** : `drupal`
- **Mot de passe** : `drupal`

### 4.8. Gestion des modules Apache

```bash
# Voir les modules actifs
./scripts/manage-apache-modules.sh status

# Activer des modules
./scripts/manage-apache-modules.sh enable headers expires

# Désactiver des modules
./scripts/manage-apache-modules.sh disable ssl

# Via Makefile
make apache-modules-status
make apache-modules-enable        # Active headers, expires, deflate
```

### 4.9. Accès shell aux conteneurs

```bash
# Shell Apache
podman exec -it web bash

# Shell PHP
podman exec -it php bash

# Shell PostgreSQL
podman exec -it db bash
```

### 4.10. Rebuild complet

```bash
# Reconstruire toutes les images et redémarrer
./scripts/start-containers.sh --rebuild

# Ou étape par étape
podman compose -f podman-compose.yml down
podman compose -f podman-compose.yml build --no-cache
podman compose -f podman-compose.yml up -d
```

---

## 5. Vérifications et tests

### 5.1. Vérifier Apache et PHP

#### 5.1.1. Test Apache seul

```bash
# Dans le navigateur
http://localhost:8080/

# Ou en ligne de commande
curl http://localhost:8080/
```

**Résultat attendu** : Page d'accueil Drupal ou page d'installation.

#### 5.1.2. Créer une page PHP de test

```bash
# Créer un fichier phpinfo.php
cat > src/web/phpinfo.php <<'EOF'
<?php
phpinfo();
EOF

# Tester dans le navigateur
http://localhost:8080/phpinfo.php
```

**Résultat attendu** : Page phpinfo() avec toutes les extensions PHP.

**⚠️ Supprimer après test** :

```bash
rm src/web/phpinfo.php
```

#### 5.1.3. Vérifier les modules Apache

```bash
podman exec web httpd -M | grep -E "proxy|fcgi|rewrite"

# Sortie attendue :
#  proxy_module (shared)
#  proxy_fcgi_module (shared)
#  rewrite_module (shared)
```

### 5.2. Vérifier la connexion PHP ↔ PostgreSQL

#### 5.2.1. Test de connexion PHP

```bash
# Créer un fichier de test
cat > src/web/dbtest.php <<'EOF'
<?php
try {
    $conn = new PDO('pgsql:host=db;port=5432;dbname=drupal', 'drupal', 'drupal');
    echo "✓ Connexion PostgreSQL réussie !<br>";
    echo "Version: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch(PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
EOF

# Tester dans le navigateur
http://localhost:8080/dbtest.php
```

**Résultat attendu** :
```
✓ Connexion PostgreSQL réussie !
Version: 16.11
```

**⚠️ Supprimer après test** :

```bash
rm src/web/dbtest.php
```

#### 5.2.2. Test depuis le conteneur PHP

```bash
podman exec php php -r "\$conn = new PDO('pgsql:host=db;dbname=drupal', 'drupal', 'drupal'); echo 'OK';"

# Sortie attendue : OK
```

### 5.3. Vérifier les volumes et permissions

```bash
# Vérifier que src/ est bien monté
podman exec web ls -la /var/www/html/web/

# Vérifier les permissions
podman exec php bash -c "touch /var/www/html/test.txt && rm /var/www/html/test.txt && echo 'Permissions OK'"

# Vérifier le volume PostgreSQL
podman volume inspect drupal_db_data
```

### 5.4. Vérifier les logs

```bash
# Vérifier que les logs sont bien écrits
ls -lh logs/apache/
ls -lh logs/php/
ls -lh logs/postgres/

# Tester l'écriture des logs Apache
curl http://localhost:8080/
cat logs/apache/access.log | tail -1
```

### 5.5. Tests de performance basiques

```bash
# Test de charge Apache (avec ab si installé)
ab -n 100 -c 10 http://localhost:8080/

# Test de temps de réponse
time curl -o /dev/null -s http://localhost:8080/
```

---

## 6. Dépannage

### 6.1. Les conteneurs ne démarrent pas

#### Problème : Podman Machine arrêtée

```bash
# Symptôme
podman ps
# Error: cannot connect to Podman socket

# Solution
wsl --shutdown
sleep 3
podman machine start
```

#### Problème : Port 8080 déjà utilisé

```bash
# Vérifier quel processus utilise le port
netstat -ano | findstr :8080

# Changer le port dans podman-compose.yml
# Remplacer "8080:80" par "8081:80"
```

#### Problème : Conteneurs en erreur

```bash
# Voir les logs d'erreur
podman logs web
podman logs php
podman logs db

# Redémarrer un conteneur spécifique
podman restart web
```

### 6.2. PHP renvoie le code source au lieu de l'exécuter

#### Symptôme
Le navigateur affiche le code PHP brut.

#### Cause
Les modules `mod_proxy` et `mod_proxy_fcgi` ne sont pas chargés.

#### Solution

```bash
# Vérifier les modules
./scripts/manage-apache-modules.sh status

# Réactiver les modules requis
./scripts/manage-apache-modules.sh enable proxy proxy_fcgi rewrite --restart
```

### 6.3. Erreur de connexion PostgreSQL

#### Symptôme
```
SQLSTATE[08006] connection refused
```

#### Solutions

```bash
# 1. Vérifier que le conteneur db est démarré
podman ps | grep db

# 2. Vérifier la santé du conteneur
podman inspect db | grep -A 5 Health

# 3. Tester la connexion depuis PHP
podman exec php ping -c 2 db

# 4. Redémarrer PostgreSQL
podman restart db
```

### 6.4. Permission denied sur src/web/sites/default/files

#### Symptôme
Drupal ne peut pas écrire dans le dossier files/.

#### Solution

```bash
# Créer et donner les permissions
mkdir -p src/web/sites/default/files
chmod -R 775 src/web/sites/default/files

# Ou depuis le conteneur PHP
podman exec php bash -c "chown -R www-data:www-data /var/www/html"
```

### 6.5. Composer échoue avec erreur mémoire

#### Symptôme
```
Fatal error: Allowed memory size exhausted
```

#### Solution

```bash
# Utiliser la variable COMPOSER_MEMORY_LIMIT
podman exec php bash -lc "COMPOSER_MEMORY_LIMIT=-1 composer install"
```

### 6.6. Logs non visibles dans logs/

#### Cause
Les volumes de logs ne sont pas correctement montés.

#### Solution

```bash
# Recréer les dossiers
mkdir -p logs/{apache,php,postgres}

# Redémarrer les conteneurs
podman compose -f podman-compose.yml down
podman compose -f podman-compose.yml up -d

# Vérifier les montages
podman inspect web | grep -A 10 Mounts
```

### 6.7. Healthcheck en "unhealthy"

```bash
# Voir les détails du healthcheck
podman inspect web --format='{{json .State.Health}}' | jq

# Tester manuellement le healthcheck
podman exec web curl -f http://localhost/

# Si le test échoue, vérifier les logs
podman logs web
```

---

## 7. Sécurité et bonnes pratiques

### 7.1. Sécurité (environnement de développement)

#### 7.1.1. Mots de passe par défaut

**⚠️ IMPORTANT** : Les identifiants par défaut (`drupal`/`drupal`) sont pour le développement uniquement.

**En production** :

```yaml
# Utiliser des secrets
db:
  environment:
    POSTGRES_USER: ${DB_USER}
    POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    POSTGRES_DB: ${DB_NAME}
  secrets:
    - db_password

secrets:
  db_password:
    file: ./secrets/db_password.txt
```

#### 7.1.2. Exposition des ports

- **Développement** : Port 8080 accessible depuis l'hôte uniquement
- **Production** : Utiliser un reverse proxy (Nginx, Traefik) avec HTTPS

#### 7.1.3. Fichiers sensibles

Ne jamais versionner :

- `settings.php` (contient les identifiants DB)
- `settings.local.php`
- Clés API, tokens
- Certificats SSL

#### 7.1.4. Mise à jour régulière

```bash
# Mettre à jour les images de base
podman pull httpd:2.4
podman pull php:8.3-fpm
podman pull postgres:16

# Reconstruire
./scripts/start-containers.sh --rebuild
```

### 7.2. Bonnes pratiques de développement

#### 7.2.1. Utiliser des volumes nommés pour la base

✅ **BON** :
```yaml
volumes:
  drupal_db_data:
```

❌ **MAUVAIS** : Bind mount direct (perte de performances sur Windows)
```yaml
volumes:
  - ./data/postgres:/var/lib/postgresql/data
```

#### 7.2.2. Sauvegardes régulières

```bash
# Sauvegarder la base de données
podman exec db pg_dump -U drupal drupal > backup_$(date +%Y%m%d).sql

# Restaurer
cat backup_20251125.sql | podman exec -i db psql -U drupal drupal
```

#### 7.2.3. Export/Import de configuration Drupal

```bash
# Exporter la config Drupal
podman exec php bash -c "cd /var/www/html && vendor/bin/drush config:export -y"

# Les fichiers sont dans config/sync/ (à versionner)

# Importer sur un autre environnement
podman exec php bash -c "cd /var/www/html && vendor/bin/drush config:import -y"
```

#### 7.2.4. Utiliser Drush pour les tâches

```bash
# Installer Drush
podman exec php bash -c "cd /var/www/html && composer require drush/drush"

# Vider les caches
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cr"

# Mettre à jour la base
podman exec php bash -c "cd /var/www/html && vendor/bin/drush updb -y"

# Lancer le cron
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cron"
```

#### 7.2.5. Logs rotatifs

Sur un système de développement actif, les logs peuvent grossir. Utiliser `logrotate` ou nettoyer manuellement :

```bash
# Archiver les vieux logs
tar -czf logs_archive_$(date +%Y%m%d).tar.gz logs/
rm logs/*/*.log

# Ou via un script cron
```

#### 7.2.6. Monitoring basique

```bash
# Surveiller l'utilisation CPU/Mémoire
podman stats

# Espace disque utilisé par les volumes
podman system df

# Nettoyer les ressources inutilisées
podman system prune -a
```

#### 7.2.7. Versionnement Git

**À versionner** :
- `docker/` (Dockerfiles)
- `scripts/` (scripts automation)
- `podman-compose.yml`
- `src/composer.json` et `src/composer.lock`
- `config/sync/` (configuration Drupal exportée)
- `.gitignore`, `.gitattributes`

**À NE PAS versionner** :
- `src/web/`, `src/vendor/`, `src/recipes/`
- `logs/`
- `settings.php` (secrets)
- Fichiers uploadés (`sites/default/files`)

#### 7.2.8. Performance PHP

Le fichier `php.ini` fourni est optimisé pour le développement. Vérifier :

```bash
# Vérifier OPcache
podman exec php php -i | grep opcache

# Vérifier APCu
podman exec php php -i | grep apcu

# Vérifier memory_limit
podman exec php php -i | grep memory_limit
```

---

## 8. Aide-mémoire des commandes

### Commandes essentielles

| Action | Commande |
|--------|----------|
| **Démarrer** | `./scripts/start-containers.sh` ou `make start` |
| **Arrêter** | `./scripts/stop-containers.sh` ou `make stop` |
| **Logs temps réel** | `podman compose logs -f` |
| **État conteneurs** | `podman ps` |
| **Santé** | `./scripts/health-check.sh` |
| **Shell PHP** | `podman exec -it php bash` |
| **Shell DB** | `podman exec -it db psql -U drupal drupal` |
| **Rebuild complet** | `./scripts/start-containers.sh --rebuild` |
| **Drupal cache clear** | `podman exec php bash -c "cd /var/www/html && vendor/bin/drush cr"` |

### Troubleshooting rapide

| Problème | Solution |
|----------|----------|
| Podman ne répond pas | `wsl --shutdown && podman machine start` |
| Port occupé | Changer `8080:80` dans `podman-compose.yml` |
| PHP affiche le code | `./scripts/manage-apache-modules.sh enable proxy proxy_fcgi` |
| Erreur DB | `podman restart db` |
| Permissions fichiers | `podman exec php chown -R www-data:www-data /var/www/html` |

---

## 9. Ressources et documentation

### Documentation officielle

- [Drupal 11](https://www.drupal.org/docs/getting-started)
- [Podman](https://docs.podman.io/)
- [PHP-FPM](https://www.php.net/manual/en/install.fpm.php)
- [PostgreSQL 16](https://www.postgresql.org/docs/16/)
- [Apache HTTPD](https://httpd.apache.org/docs/2.4/)

### Dépôt GitHub

- [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

### Support

Pour toute question ou problème :

1. Consulter la section [Dépannage](#6-dépannage)
2. Vérifier les [issues GitHub](https://github.com/asahraouiia/podman-drupal11/issues)
3. Créer une nouvelle issue avec les logs

---

**Auteur** : asahraoui.ia  
**Dernière révision** : 2025-11-25  
**Version** : 0.2
