# Guide d'installation et configuration - Conteneur Apache

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Dockerfile Apache](#2-dockerfile-apache)
3. [Configuration VirtualHost](#3-configuration-virtualhost)
4. [Modules Apache](#4-modules-apache)
5. [Construction de l'image](#5-construction-de-limage)
6. [Démarrage du conteneur](#6-démarrage-du-conteneur)
7. [Gestion du conteneur](#7-gestion-du-conteneur)
8. [Logs Apache](#8-logs-apache)
9. [Dépannage](#9-dépannage)
10. [Optimisation](#10-optimisation)

---

## 1. Vue d'ensemble

### 1.1. Rôle du conteneur Apache

Le conteneur Apache agit comme **serveur web frontal** dans l'architecture Drupal :

```
Utilisateur (Browser) → Apache (:8080) → PHP-FPM (:9000) → Drupal
```

**Responsabilités** :
- Recevoir les requêtes HTTP/HTTPS
- Servir les fichiers statiques (CSS, JS, images)
- Transférer les requêtes PHP vers PHP-FPM
- Gérer les logs d'accès et d'erreur

### 1.2. Caractéristiques techniques

- **Image de base** : `httpd:2.4` (Apache HTTP Server officiel)
- **Port exposé** : `8080` (hôte) → `80` (conteneur)
- **Protocole avec PHP-FPM** : FastCGI (via `mod_proxy_fcgi`)
- **DocumentRoot** : `/var/www/html/web` (racine Drupal)

### 1.3. Emplacement des fichiers

```
docker/apache/
├── Dockerfile        # Image personnalisée Apache
└── vhost.conf        # Configuration VirtualHost
```

---

## 2. Dockerfile Apache

### 2.1. Contenu complet

**Emplacement** : `docker/apache/Dockerfile`

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

### 2.2. Explication des étapes

#### Étape 1 : Image de base

```dockerfile
FROM httpd:2.4
```

Utilise l'image officielle Apache 2.4 depuis Docker Hub.

#### Étape 2 : Installation des utilitaires

```dockerfile
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2-utils curl && rm -rf /var/lib/apt/lists/* || true
```

- `apache2-utils` : Outils de gestion Apache (htpasswd, ab, etc.)
- `curl` : Pour les healthchecks
- `rm -rf /var/lib/apt/lists/*` : Nettoyage du cache apt

#### Étape 3 : Copie du VirtualHost

```dockerfile
COPY vhost.conf /usr/local/apache2/conf/sites-enabled/vhost.conf
```

Copie la configuration du VirtualHost depuis l'hôte vers le conteneur.

#### Étape 4 : ServerName global

```dockerfile
RUN echo "ServerName localhost" >> /usr/local/apache2/conf/httpd.conf || true
```

Évite les avertissements "Could not reliably determine the server's fully qualified domain name".

#### Étape 5 : Chargement des modules

```dockerfile
RUN printf '%s\n' \
    'LoadModule proxy_module modules/mod_proxy.so' \
    'LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so' \
    ...
```

Active les modules Apache nécessaires (voir section [Modules Apache](#4-modules-apache)).

#### Étape 6 : Inclusion des VirtualHosts

```dockerfile
RUN mkdir -p /usr/local/apache2/conf/sites-enabled \
    && echo 'IncludeOptional conf/sites-enabled/*.conf' \
    >> /usr/local/apache2/conf/httpd.conf
```

Configure Apache pour charger tous les fichiers `.conf` dans `sites-enabled/`.

#### Étape 7 : Dossier de logs

```dockerfile
RUN mkdir -p /var/log/apache2
```

Crée le répertoire pour les logs (monté depuis l'hôte).

---

## 3. Configuration VirtualHost

### 3.1. Contenu complet

**Emplacement** : `docker/apache/vhost.conf`

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

### 3.2. Explication des directives

#### ServerName

```apacheconf
ServerName localhost
```

Nom du serveur pour les requêtes HTTP/1.1.

#### DocumentRoot

```apacheconf
DocumentRoot /var/www/html/web
```

**Racine web Drupal**. Toutes les URLs sont relatives à ce dossier.

- `/var/www/html` : Volume partagé (correspond à `src/` sur l'hôte)
- `/web` : Sous-dossier créé par Composer (structure Drupal 11)

#### Directory

```apacheconf
<Directory /var/www/html/web>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

- `Options Indexes` : Liste les fichiers si pas d'index
- `FollowSymLinks` : Suit les liens symboliques
- **`AllowOverride All`** : **CRITIQUE** - Permet au `.htaccess` de Drupal de fonctionner
- `Require all granted` : Autorise l'accès à tous

#### FilesMatch (PHP-FPM)

```apacheconf
<FilesMatch \.php$>
    SetHandler "proxy:fcgi://php:9000"
</FilesMatch>
```

**Configuration FastCGI** :
- Tous les fichiers `.php` sont transférés à PHP-FPM
- `php` : Nom du conteneur PHP (résolution DNS via réseau Podman)
- `9000` : Port PHP-FPM

#### Logs

```apacheconf
ErrorLog /var/log/apache2/error.log
CustomLog /var/log/apache2/access.log combined
```

- `error.log` : Erreurs Apache (404, 500, etc.)
- `access.log` : Requêtes HTTP (format `combined`)

---

## 4. Modules Apache

### 4.1. Modules obligatoires

| Module | Description | Raison |
|--------|-------------|--------|
| `mod_proxy` | Proxy inverse | Transfert vers PHP-FPM |
| `mod_proxy_fcgi` | Interface FastCGI | Communication avec PHP-FPM |
| `mod_rewrite` | Réécriture d'URL | Clean URLs Drupal (`.htaccess`) |

**⚠️ Sans ces modules, PHP ne fonctionnera pas !**

### 4.2. Modules recommandés

| Module | Description | Bénéfice |
|--------|-------------|----------|
| `mod_deflate` | Compression gzip | Réduction bande passante ~70% |
| `mod_headers` | Manipulation en-têtes HTTP | Sécurité (CORS, CSP, HSTS) |
| `mod_expires` | Contrôle du cache | Performance (cache navigateur) |

### 4.3. Vérifier les modules chargés

```bash
# Liste complète des modules
podman exec web httpd -M

# Vérifier les modules critiques
podman exec web httpd -M | grep -E "proxy|fcgi|rewrite"

# Sortie attendue :
#  proxy_module (shared)
#  proxy_fcgi_module (shared)
#  rewrite_module (shared)
```

### 4.4. Activer/Désactiver des modules

#### Via script (recommandé)

```bash
# Voir l'état des modules
./scripts/manage-apache-modules.sh status

# Activer des modules
./scripts/manage-apache-modules.sh enable headers expires deflate

# Désactiver un module
./scripts/manage-apache-modules.sh disable ssl

# Activer et redémarrer Apache
./scripts/manage-apache-modules.sh enable headers --restart
```

#### Manuellement

Modifier `docker/apache/Dockerfile` et ajouter :

```dockerfile
RUN printf '%s\n' \
    'LoadModule ssl_module modules/mod_ssl.so' \
    >> /usr/local/apache2/conf/httpd.conf
```

Puis reconstruire l'image :

```bash
podman compose build --no-cache web
podman compose up -d
```

---

## 5. Construction de l'image

### 5.1. Construction initiale

```bash
# Depuis la racine du projet
podman compose build web

# Ou avec Podman directement
podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache/
```

**Durée** : 2-5 minutes (première fois)

### 5.2. Reconstruire après modification

```bash
# Sans cache (recommandé)
podman compose build --no-cache web

# Avec le script
./scripts/start-containers.sh --rebuild
```

### 5.3. Vérifier l'image créée

```bash
# Lister les images
podman images

# Sortie attendue :
# REPOSITORY              TAG         IMAGE ID      CREATED       SIZE
# localhost/myapache      latest      abc123def456  2 hours ago   180 MB

# Inspecter l'image
podman inspect localhost/myapache:latest
```

---

## 6. Démarrage du conteneur

### 6.1. Via Podman Compose (recommandé)

```bash
# Démarrer tous les services (Apache, PHP, PostgreSQL)
podman compose up -d

# Démarrer uniquement Apache
podman compose up -d web
```

### 6.2. Via Podman directement

```bash
podman run -d \
  --name web \
  -p 8080:80 \
  -v ./src:/var/www/html \
  -v ./logs/apache:/var/log/apache2 \
  --network podman_drupal_net \
  localhost/myapache:latest
```

### 6.3. Vérifier le démarrage

```bash
# État du conteneur
podman ps | grep web

# Sortie attendue :
# CONTAINER ID  IMAGE                      STATUS
# abc123def456  localhost/myapache:latest  Up 2 minutes (healthy)

# Logs de démarrage
podman logs web
```

### 6.4. Tester l'accès

```bash
# Depuis le navigateur
http://localhost:8080

# En ligne de commande
curl -I http://localhost:8080
# Attendu: HTTP/1.1 200 OK ou 302 Found (redirection Drupal)
```

---

## 7. Gestion du conteneur

### 7.1. Commandes de base

```bash
# Arrêter Apache
podman stop web

# Démarrer Apache
podman start web

# Redémarrer Apache
podman restart web

# Voir les processus dans le conteneur
podman top web
```

### 7.2. Accès shell

```bash
# Shell interactif
podman exec -it web bash

# Commandes utiles dans le conteneur :
httpd -v                    # Version Apache
httpd -M                    # Modules chargés
httpd -S                    # VirtualHosts configurés
apachectl configtest        # Vérifier la configuration
apachectl -k graceful       # Rechargement sans coupure
```

### 7.3. Recharger la configuration

```bash
# Recharger Apache sans redémarrer le conteneur
podman exec web apachectl graceful

# Ou via script
./scripts/reload-apache.sh
```

### 7.4. Healthcheck

Le conteneur inclut un healthcheck automatique :

```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

**Vérifier l'état** :

```bash
# État de santé
podman inspect web --format='{{.State.Health.Status}}'
# Attendu: healthy

# Détails du healthcheck
podman inspect web --format='{{json .State.Health}}' | jq
```

---

## 8. Logs Apache

### 8.1. Logs en temps réel

```bash
# Tous les logs Apache
podman logs -f web

# Logs d'erreur uniquement
tail -f logs/apache/error.log

# Logs d'accès uniquement
tail -f logs/apache/access.log
```

### 8.2. Logs historiques

```bash
# Dernières 50 lignes
podman logs --tail 50 web

# Depuis une date
podman logs --since 2025-11-25T10:00:00 web

# Avec timestamps
podman logs -t web
```

### 8.3. Collecter les logs

```bash
# Script de collecte automatique
./scripts/logs-collect.sh

# Les logs sont copiés dans logs/apache/
ls -lh logs/apache/
# error.log
# access.log
```

### 8.4. Analyser les logs

```bash
# Compter les codes HTTP
cat logs/apache/access.log | awk '{print $9}' | sort | uniq -c | sort -rn

# Top 10 des IP
cat logs/apache/access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -10

# Erreurs 404
grep ' 404 ' logs/apache/access.log

# Erreurs 500
grep ' 500 ' logs/apache/access.log
tail -f logs/apache/error.log
```

---

## 9. Dépannage

### 9.1. PHP affiche le code source

#### Symptôme

Le navigateur affiche le code PHP au lieu de l'exécuter.

#### Diagnostic

```bash
# Vérifier les modules proxy et fcgi
podman exec web httpd -M | grep -E "proxy|fcgi"
```

**Attendu** :

```
 proxy_module (shared)
 proxy_fcgi_module (shared)
```

#### Solution

```bash
# Si les modules sont manquants, reconstruire l'image
podman compose build --no-cache web
podman compose up -d

# Ou activer manuellement
./scripts/manage-apache-modules.sh enable proxy proxy_fcgi --restart
```

### 9.2. Erreur 403 Forbidden

#### Symptôme

```
Forbidden: You don't have permission to access / on this server.
```

#### Diagnostic

```bash
# Vérifier la directive AllowOverride dans vhost.conf
podman exec web cat /usr/local/apache2/conf/sites-enabled/vhost.conf | grep AllowOverride
```

**Attendu** : `AllowOverride All`

#### Solution

Modifier `docker/apache/vhost.conf` :

```apacheconf
<Directory /var/www/html/web>
    AllowOverride All
    Require all granted
</Directory>
```

Reconstruire :

```bash
podman compose build --no-cache web
podman compose up -d
```

### 9.3. Erreur 404 Not Found

#### Symptôme

Toutes les URLs retournent 404.

#### Diagnostic

```bash
# Vérifier le DocumentRoot
podman exec web cat /usr/local/apache2/conf/sites-enabled/vhost.conf | grep DocumentRoot

# Vérifier que les fichiers existent
podman exec web ls -la /var/www/html/web/
```

**Attendu** : `DocumentRoot /var/www/html/web`

#### Solution

Vérifier que Drupal est installé :

```bash
ls src/web/index.php
# Si absent, installer Drupal (voir DRUPAL_INSTALLATION.md)
```

### 9.4. mod_rewrite ne fonctionne pas

#### Symptôme

Les clean URLs ne fonctionnent pas (`/user/login` → 404).

#### Diagnostic

```bash
# Vérifier que mod_rewrite est chargé
podman exec web httpd -M | grep rewrite
```

**Attendu** : `rewrite_module (shared)`

#### Solution

```bash
# Activer mod_rewrite
./scripts/manage-apache-modules.sh enable rewrite --restart
```

### 9.5. Apache ne démarre pas

#### Diagnostic

```bash
# Voir les logs d'erreur
podman logs web

# Vérifier la configuration
podman exec web apachectl configtest
```

**Erreurs courantes** :

- `Syntax error` : Erreur dans `vhost.conf`
- `Port 80 already in use` : Port déjà utilisé (impossible dans un conteneur)

#### Solution

```bash
# Corriger la configuration
nano docker/apache/vhost.conf

# Reconstruire
podman compose build --no-cache web
podman compose up -d
```

### 9.6. Healthcheck "unhealthy"

#### Diagnostic

```bash
# Détails du healthcheck
podman inspect web --format='{{json .State.Health}}' | jq

# Tester manuellement le healthcheck
podman exec web curl -f http://localhost/
```

#### Solution

```bash
# Si curl échoue, vérifier les logs
podman logs web

# Redémarrer Apache
podman restart web
```

---

## 10. Optimisation

### 10.1. Compression gzip (mod_deflate)

Activer la compression pour réduire la bande passante :

```apacheconf
# Dans vhost.conf
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
    AddOutputFilterByType DEFLATE application/javascript application/json
    AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>
```

### 10.2. Cache navigateur (mod_expires)

Configurer le cache pour les fichiers statiques :

```apacheconf
# Dans vhost.conf
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
```

### 10.3. Sécurité (mod_headers)

Ajouter des en-têtes de sécurité :

```apacheconf
# Dans vhost.conf
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### 10.4. HTTP/2

Pour activer HTTP/2 (nécessite HTTPS) :

```dockerfile
# Dans Dockerfile
RUN printf '%s\n' \
    'LoadModule http2_module modules/mod_http2.so' \
    >> /usr/local/apache2/conf/httpd.conf
```

```apacheconf
# Dans vhost.conf
Protocols h2 http/1.1
```

### 10.5. Limiter les ressources

Dans `podman-compose.yml` :

```yaml
web:
  deploy:
    resources:
      limits:
        cpus: '1.0'
        memory: 512M
      reservations:
        cpus: '0.5'
        memory: 256M
```

---

## Aide-mémoire Apache

| Action | Commande |
|--------|----------|
| **Build image** | `podman compose build web` |
| **Démarrer** | `podman compose up -d web` |
| **Arrêter** | `podman stop web` |
| **Redémarrer** | `podman restart web` |
| **Shell** | `podman exec -it web bash` |
| **Logs temps réel** | `podman logs -f web` |
| **Vérifier modules** | `podman exec web httpd -M` |
| **Tester config** | `podman exec web apachectl configtest` |
| **Recharger config** | `podman exec web apachectl graceful` |
| **Healthcheck** | `podman inspect web --format='{{.State.Health.Status}}'` |

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2

**Voir aussi** :
- [Installation Podman](PODMAN_INSTALL.md) - Installation et configuration de Podman/WSL2
- [Configuration PHP](CONTAINER_PHP_INSTALL.md) - Installation et configuration du conteneur PHP-FPM
- [Configuration PostgreSQL](CONTAINER_POSTGRESQL_INSTALL.md) - Installation et configuration du conteneur PostgreSQL
- [Installation Drupal](DRUPAL_INSTALLATION.md) - Installation et gestion de Drupal
- [README.md](../README.md) - Guide de démarrage rapide
