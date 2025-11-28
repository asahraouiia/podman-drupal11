**Podman + Podman-Compose — Environnement de développement pour Drupal 11**

Dernière mise à jour : 2025-11-25

Ce guide décrit comment créer un environnement de développement local pour Drupal 11 en utilisant Podman et podman-compose. Il fournit des exemples de `Dockerfile`, un fichier de stack `podman-compose.yml`, les commandes de build/démarrage, ainsi que des instructions pas-à-pas pour installer Drupal 11 avec Composer (Apache en front, PHP-FPM en back, PostgreSQL comme base).

**Portée** : environnement de développement (pas de configuration production). Le guide cible des environnements reproductibles sous Windows (WSL ou Podman Desktop) et Linux.

**Architecture**
- **web** : conteneur Apache HTTPD servant le site et transférant les requêtes PHP vers PHP-FPM. Inclut mod_rewrite pour les clean URLs.
- **php** : conteneur PHP-FPM contenant Composer et les extensions PHP nécessaires à Drupal 11.
- **db** : conteneur PostgreSQL pour la base de données Drupal.
- **volume** : volume ou bind mount partagé pour le code de l'application (`/var/www/html`).

**Prérequis**
- Podman installé (ou Podman Desktop sous Windows). Sous Windows, il est recommandé d'utiliser WSL2 ou Podman Desktop.
- **Commande recommandée** : `podman compose` (intégré à Podman 4.0+) au lieu de `podman-compose`.
- Git. Composer est installé dans l'image PHP fournie par les `Dockerfile` ci-dessous.
- Un terminal Bash (sous Windows, utilisez WSL ou Git Bash pour la meilleure compatibilité).

Note sur la version PHP : en date du 2025-11-25, Drupal 11 demande PHP 8.1 ou supérieur. Utilisez PHP 8.2/8.3 pour plus de compatibilité et vérifiez toujours les exigences actuelles sur https://www.drupal.org/docs.

**Installation rapide** :

```bash
# Cloner le dépôt
git clone https://github.com/asahraouiia/podman-drupal11.git
cd podman-drupal11

# Démarrer l'environnement (Bash/WSL)
chmod +x scripts/*.sh
./scripts/start-containers.sh

# Ou sur PowerShell (Windows)
.\scripts\start-containers.ps1

# Installer Drupal
./scripts/drupal-install.sh

# Accéder au site
# http://localhost:8080
```

-------------------------
**1) Sample `Dockerfile` for PHP-FPM (with Composer and PHP extensions)**

Save this as `docker/php/Dockerfile` or adapt your existing `docker/php/Dockerfile`.

```Dockerfile
FROM php:8.3-fpm

# Install system dependencies and PHP extensions required by Drupal
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
    libzip-dev zlib1g-dev libicu-dev libonig-dev libpq-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd xml zip intl opcache bcmath

**Podman + Podman-Compose — Environnement de développement pour Drupal 11**

Dernière mise à jour : 2025-11-25

Ce guide décrit comment créer un environnement de développement local pour Drupal 11 en utilisant Podman et podman-compose. Il fournit des exemples de `Dockerfile`, un fichier de stack `podman-compose.yml`, les commandes de build/démarrage, ainsi que des instructions pas-à-pas pour installer Drupal 11 avec Composer (Apache en front, PHP-FPM en back, PostgreSQL comme base).

Portée : environnement de développement (pas de configuration production). Le guide cible des environnements reproductibles sous Windows (WSL ou Podman Desktop) et Linux.

Architecture
- `web` : conteneur Apache HTTPD servant le site et transférant les requêtes PHP vers PHP-FPM.
- `php` : conteneur PHP-FPM contenant Composer et les extensions PHP nécessaires à Drupal 11.
- `db` : conteneur PostgreSQL pour la base de données Drupal.
- volume : volume ou bind mount partagé pour le code de l'application (`/var/www/html`).

Prérequis
- Podman installé (ou Podman Desktop sous Windows). Sous Windows, il est recommandé d'utiliser WSL2 ou Podman Desktop.
- `podman-compose` (package pip `podman-compose`) ou un équivalent `docker-compose` compatible avec Podman.
- Git. Composer est installé dans l'image PHP fournie par les `Dockerfile` ci-dessous.
- Un terminal Bash (sous Windows, utilisez WSL ou Git Bash pour la meilleure compatibilité).

Note sur la version PHP : en date du 2025-11-24, Drupal 11 demande PHP 8.1 ou supérieur. Utilisez PHP 8.2/8.3 pour plus de compatibilité et vérifiez toujours les exigences actuelles sur https://www.drupal.org/docs.

-------------------------
1) Exemple de `Dockerfile` pour PHP-FPM (Composer + extensions PHP)

Enregistrez ce fichier sous `docker/php/Dockerfile` ou adaptez votre fichier existant.

```Dockerfile
FROM php:8.3-fpm

# Install system dependencies and PHP extensions required by Drupal
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
    libzip-dev zlib1g-dev libicu-dev libonig-dev libpq-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd xml zip intl opcache bcmath

# Optional: install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create www-data directory and ensure permissions
RUN usermod -u 1000 www-data || true
WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
```

Remarques :
- L'image installe `pdo_pgsql` et `pgsql` pour supporter PostgreSQL.
- Composer est installé globalement en `/usr/local/bin/composer`.
- Modifiez le tag de base `php:8.3-fpm` si vous préférez une autre version mineure.

**php.ini personnalisé (développement Drupal)**

Un fichier `docker/php/php.ini` est fourni et copié dans l'image en tant que `/usr/local/etc/php/conf.d/zz-custom.ini` lors du build. Il définit des paramètres adaptés au développement Drupal :

- `memory_limit=512M` — évite les erreurs mémoire lors des opérations Composer / Drush.
- `upload_max_filesize=64M`, `post_max_size=64M` — taille confortable pour médias.
- OPCache activé avec `opcache.memory_consumption=192`, `opcache.max_accelerated_files=50000` — améliore les performances.
- `display_errors=On` et `error_reporting=E_ALL` — visibilité maximale en dev (désactiver en prod).
- `apc.enabled=1`, `apc.shm_size=64M` — cache utilisateur si APCu activé.
- `realpath_cache_size=4096K`, `realpath_cache_ttl=600` — accélère la résolution des chemins.
- `pcre.backtrack_limit` / `pcre.recursion_limit` augmentés — réduit les risques d'erreurs sur des regex complexes.

Pour ajuster ces valeurs, modifiez `docker/php/php.ini`, puis reconstruisez l'image :

```bash
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
podman rm -f php && podman run -d --name php --network drupal_net -v "$(pwd)/src:/var/www/html" myphp:8.3-dev
```

Vérification rapide des paramètres appliqués dans le conteneur :

```bash
podman exec -it php php -i | grep -E "memory_limit|upload_max_filesize|post_max_size|opcache.memory_consumption"
```

En production, réduire éventuellement `display_errors`, passer `opcache.validate_timestamps=0` et durcir les limites mémoire selon la taille du site.

-------------------------
2) Exemple de `Dockerfile` pour Apache (front)

Enregistrez ce fichier sous `docker/apache/Dockerfile`.

```Dockerfile
FROM httpd:2.4

# Enable proxy modules for forwarding to PHP-FPM
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2-utils && rm -rf /var/lib/apt/lists/* || true

# Copy our vhost configuration (provided below) into the image
COPY vhost.conf /usr/local/apache2/conf/sites-enabled/vhost.conf

RUN sed -i '/^#IncludeOptional conf\/extra\/httpd-vhosts.conf/ s/^#//' /usr/local/apache2/conf/httpd.conf || true

# Set a global ServerName to silence AH00558 warnings about FQDN detection
RUN echo "ServerName localhost" >> /usr/local/apache2/conf/httpd.conf || true

# Ensure proxy modules are loaded so Apache can forward PHP to php-fpm
# Enable mod_rewrite for Drupal clean URLs
RUN printf '%s\n' \
    'LoadModule proxy_module modules/mod_proxy.so' \
    'LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so' \
    'LoadModule rewrite_module modules/mod_rewrite.so' >> /usr/local/apache2/conf/httpd.conf || true

# Ensure our sites-enabled vhost directory is included
RUN mkdir -p /usr/local/apache2/conf/sites-enabled \
    && echo 'IncludeOptional conf/sites-enabled/*.conf' >> /usr/local/apache2/conf/httpd.conf

EXPOSE 80

CMD ["httpd-foreground"]
```

**Remarques importantes** :
- **mod_rewrite activé** : permet les clean URLs de Drupal (URLs sans `?q=`).
- La directive `AllowOverride All` dans le vhost permet au fichier `.htaccess` de Drupal de fonctionner.
- Le module `mod_proxy_fcgi` transfère les requêtes PHP vers le conteneur `php` sur le port 9000.

```apacheconf
<VirtualHost *:80>
    ServerName drupal.local
    DocumentRoot /var/www/html/web

    <Directory /var/www/html/web>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Forward PHP requests to the php service (php-fpm) on port 9000
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://php:9000"
    </FilesMatch>

    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 combined
</VirtualHost>
```

**Remarques importantes** :
- **mod_rewrite activé** : permet les clean URLs de Drupal (URLs sans `?q=`).
- La directive `AllowOverride All` dans le vhost permet au fichier `.htaccess` de Drupal de fonctionner.
- Le module `mod_proxy_fcgi` transfère les requêtes PHP vers le conteneur `php` sur le port 9000.

Créez `docker/apache/vhost.conf` à côté du Dockerfile avec le contenu suivant :

-------------------------
3) `podman-compose.yml` (fichier de stack)

Créez `podman-compose.yml` à la racine du projet.

```yaml
version: '3.8'
services:
  web:
    build:
      context: ./docker/apache
      dockerfile: Dockerfile
    image: myapache:latest
    container_name: web
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
    depends_on:
      - php
    networks:
      - drupal_net

  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    image: myphp:8.3-dev
    container_name: php
    volumes:
      - ./src:/var/www/html
    networks:
      - drupal_net

  db:
    image: postgres:16
    container_name: db
    ports:
      - "5432:5432"
    environment:
      POSTGRES_USER: drupal
      POSTGRES_PASSWORD: drupal
      POSTGRES_DB: drupal
    volumes:
      - drupal_db_data:/var/lib/postgresql/data
    networks:
      - drupal_net

volumes:
  drupal_db_data: {}

networks:
  drupal_net: {}
```

Remarques :
- **Important pour Windows/WSL** : Évitez les bind mounts de type `./logs/apache:/var/log/apache2` qui causent des erreurs de chemin `/mnt/c/`. Utilisez `podman logs <container>` pour accéder aux logs.
- Les bind mounts utilisent `:Z` uniquement sur les systèmes SELinux; sous Windows/WSL utilisez simplement `./src:/var/www/html`.
- Le service `web` écoute sur le port hôte `8080`.
- PostgreSQL expose le port `5432` pour accès externe (DBeaver, VSCode, etc.).

-------------------------
4) Build et démarrage — commandes

**Méthode recommandée (avec scripts d'initialisation)** :

Les scripts fournis gèrent automatiquement l'initialisation de WSL, Podman Machine et les conteneurs.

**Bash (Linux/WSL)** :
```bash
# Rendre les scripts exécutables (première fois uniquement)
chmod +x scripts/*.sh

# Démarrage complet (initialise Podman + démarre les conteneurs)
./scripts/start-containers.sh

# Démarrage avec reconstruction des images
./scripts/start-containers.sh --rebuild

# Initialisation Podman/WSL uniquement (sans démarrer les conteneurs)
./scripts/init-podman.sh
```

**PowerShell (Windows)** :
```powershell
# Démarrage complet (initialise Podman + démarre les conteneurs)
.\scripts\start-containers.ps1

# Démarrage avec reconstruction des images
.\scripts\start-containers.ps1 --rebuild

# Initialisation Podman/WSL uniquement
.\scripts\init-podman.ps1
```

**Méthode manuelle (commandes directes)** :

Si vous préférez contrôler chaque étape manuellement :

1) **Initialiser WSL et Podman Machine** (Windows uniquement) :
```bash
# Redémarrer WSL si nécessaire
wsl --shutdown
sleep 3

# Démarrer la machine Podman
podman machine start
```

2) **Construction des images** :
```bash
# Construire toutes les images
podman compose -f podman-compose.yml build

# Ou construire une image spécifique
podman compose -f podman-compose.yml build web
podman compose -f podman-compose.yml build php
```

3) **Démarrer la stack** :
```bash
podman compose -f podman-compose.yml up -d
```

4) **Suivre les logs** :
```bash
podman compose -f podman-compose.yml logs -f

# Ou logs d'un service spécifique
podman logs -f web
podman logs -f php
```

5) **Arrêter et supprimer la stack** :
```bash
podman compose -f podman-compose.yml down
```

**Commandes de dépannage** :

```bash
# Supprimer les conteneurs orphelins
podman rm -f web php db

# Vérifier l'état de Podman Machine
podman machine list

# Vérifier les conteneurs en cours
podman ps -a

# Redémarrer un conteneur spécifique
podman restart web
```

-------------------------
5) Installation de Drupal 11 (avec Composer) dans `src/`

Deux méthodes : (A) exécuter Composer sur la machine hôte et écrire dans `src/` (bind mount), ou (B) exécuter Composer à l'intérieur du conteneur `php`. La méthode B est recommandée (consistance de versions).

Méthode recommandée (dans le conteneur `php`) :

```bash
# Démarrer la stack si nécessaire
./scripts/start.sh

# Créer le projet Drupal dans ./src en lançant Composer dans le conteneur php
Vous pouvez désormais utiliser le script fourni qui exécute Composer dans le conteneur `php` avec un timeout prolongé et journalisation :

```bash
./scripts/drupal-install.sh
```

Le script lance Composer avec `COMPOSER_PROCESS_TIMEOUT=1800` (30 minutes) et écrit la sortie dans `/tmp/composer.log` à l'intérieur du conteneur `php`. Il applique ensuite `chown -R www-data:www-data /var/www/html`.

Si vous préférez exécuter manuellement la commande Composer sans script :

```bash
podman exec -it php bash -lc "COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 /var/www/html --no-interaction"
podman exec -it php bash -lc "chown -R www-data:www-data /var/www/html"
```
```

Ensuite, ouvrez `http://localhost:8080` pour terminer l'installation via l'interface web, ou utilisez Drush pour installer en CLI.

Détails de connexion à la base (valeurs par défaut dans `podman-compose.yml`) :
- Type de base : `Postgres`
- Nom de la base : `drupal`
- Utilisateur : `drupal`
- Mot de passe : `drupal`
- Hôte : `db`
- Port : `5432`

Exemple d'installation CLI avec Drush :

```bash
# Installer drush si nécessaire
podman exec -it php bash -lc "cd /var/www/html && composer require drush/drush --no-interaction"

# Installer le site via drush
podman exec -it php bash -lc "cd /var/www/html && vendor/bin/drush site:install standard --account-name=admin --account-pass=admin --db-url=pgsql://drupal:drupal@db:5432/drupal -y"
```

-------------------------
6) `settings.php` et trusted host

Durant l'installation web, Drupal écrira `sites/default/settings.php`. Si vous devez configurer la connexion DB manuellement, modifiez `src/web/sites/default/settings.php` ou fournissez un `settings.local.php`.

Exemple de `trusted_host_patterns` (à ajouter dans `settings.php`) :

```php
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\\.0\\.0\\.1$',
  '^drupal\\.local$'
];
```

Ajoutez `127.0.0.1 drupal.local` dans votre fichier hosts si vous utilisez ce nom d'hôte local.

-------------------------
7) Variables d'environnement utiles

- `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB` — contrôlent les identifiants DB.
- `PHP_MEMORY_LIMIT` — à définir via `php.ini` si nécessaire.
- `COMPOSER_MEMORY_LIMIT=-1` — utile pour éviter les erreurs mémoire lors des opérations Composer.

Exemple :

```bash
podman exec -it php bash -lc "COMPOSER_MEMORY_LIMIT=-1 composer install"
```

-------------------------
8) Volumes, permissions et notes Windows

- Sous Linux, le bind `./src:/var/www/html` fonctionne directement. Sous Windows, préférez WSL2 pour de meilleures performances et permissions.
- **Logs** : Utilisez `podman logs <container>` au lieu de bind mounts pour les logs. Les bind mounts de type `./logs/apache:/var/log/apache2` causent des erreurs de chemin `/mnt/c/` sous Windows/WSL.
  ```bash
  # Accéder aux logs en temps réel
  podman logs -f web
  podman logs -f php
  podman logs -f db
  
  # Logs des 10 dernières minutes
  podman logs --since 10m web
  ```
- Assurez-vous que `www-data` (ou l'UID du conteneur) peut écrire dans `sites/default/files`.
- Sur Fedora/RHEL, ajoutez `:Z` aux mounts pour SELinux.

-------------------------
9) Dépannage

- Impossible de se connecter à la DB : vérifiez `podman-compose logs db` et les variables `POSTGRES_*`.
- Erreurs PHP : vérifiez `podman-compose logs php` et `podman-compose logs web`.
- Permissions : vérifiez `sites/default/files` et les droits `www-data`.
- Résolution DNS interne : utilisez les noms de service (`db`, `php`) car ils sont résolus via le réseau `drupal_net`.

-------------------------
10) Scripts d'aide et automatisation

Le dépôt inclut des scripts pour simplifier les opérations quotidiennes.

**Scripts d'initialisation et démarrage** :

- **`scripts/init-podman.sh`** / **`scripts/init-podman.ps1`** — Vérifie et initialise l'environnement Podman/WSL. Redémarre WSL si nécessaire, démarre Podman Machine, vérifie la connectivité.
  
- **`scripts/start-containers.sh`** / **`scripts/start-containers.ps1`** — Script de démarrage complet : initialise Podman, nettoie les conteneurs orphelins, reconstruit les images (si `--rebuild` passé), démarre tous les conteneurs. **Recommandé pour le démarrage quotidien**.

**Anciens scripts (compatibilité)** :

- `scripts/build.sh` — construit les images `myphp:8.3-dev` et `myapache:latest`.
- `scripts/start.sh` — construit (si nécessaire), crée le réseau/volume et démarre `db`, `php`, `web`. Utilise `podman-compose` si présent.
- `scripts/stop.sh` — arrête et supprime `web`, `php`, `db`.
- `scripts/start.ps1` / `scripts/stop.ps1` — équivalents PowerShell pour Windows.

**Makefile** — cibles : `build`, `start`, `stop`, `logs`, `clean`.

**Exemples d'utilisation recommandés** :

**Bash (Linux/WSL)** :
```bash
# Rendre les scripts exécutables (première fois)
chmod +x scripts/*.sh

# Démarrage quotidien (méthode recommandée)
./scripts/start-containers.sh

# Démarrage avec reconstruction complète
./scripts/start-containers.sh --rebuild

# Arrêt
podman compose -f podman-compose.yml down

# Logs
podman compose -f podman-compose.yml logs -f
```

**PowerShell (Windows)** :
```powershell
# Démarrage quotidien (méthode recommandée)
.\scripts\start-containers.ps1

# Démarrage avec reconstruction
.\scripts\start-containers.ps1 --rebuild

# Arrêt
podman compose -f podman-compose.yml down
```

**Makefile (optionnel)** :
```bash
make build   # Construire les images
make start   # Démarrer les conteneurs
make stop    # Arrêter les conteneurs
make logs    # Afficher les logs
make clean   # Nettoyage complet
```

-------------------------
11) Démarrage rapide (scripté)

**Méthode recommandée (2025-11-25)** :

```bash
# Depuis la racine du projet

# Bash (Linux/WSL)
chmod +x scripts/*.sh
./scripts/start-containers.sh

# PowerShell (Windows)
.\scripts\start-containers.ps1

# Créer Drupal dans le conteneur php (script fourni)
./scripts/drupal-install.sh

# Ou manuellement
podman exec -it php bash -lc "COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 /var/www/html --no-interaction && chown -R www-data:www-data /var/www/html"

# Ouvrir http://localhost:8080 pour finir l'installation
```

**Détails de connexion base de données** :
- Type : PostgreSQL
- Nom : `drupal`
- Utilisateur : `drupal`
- Mot de passe : `drupal`
- Hôte : `db`
- Port : `5432`

-------------------------
12) Notes sécurité & production

Ce guide est destiné au développement local. Pour la production :
- Utilisez des images figées et durcies.
- Mettez en place TLS et sécurisez les cookies.
- Construisez les artefacts Composer en CI, n'installez pas Composer en production.
- Gérez les secrets (DB) avec un service dédié.

-------------------------
13) Politique de documentation (obligatoire)

**Règles de mise à jour** :
- **Toujours mettre à jour la documentation après chaque modification validée** : après chaque changement accepté de code, configuration, scripts ou infrastructure dans ce dépôt, mettre à jour `docs/PODMAN_DRUPAL11_SETUP.md` (ou autres docs concernés) pour refléter le changement.

**Contenu à inclure** :
- Résumé sur une ligne du changement
- Date et auteur
- Nouvelles commandes ou fichiers ajoutés (chemins)
- Si le changement affecte les étapes de setup/démarrage : inclure les exemples de commandes mis à jour et nouvelles variables d'environnement

**Où documenter** :
- Mettre à jour la section spécifique qui a changé (ex: notes `Dockerfile`, `podman-compose.yml`, section scripts)
- Ajouter une entrée courte au `CHANGELOG.md` si approprié

**Vérification** :
- Après mise à jour de la doc, exécuter les scripts/commandes de start/build localement (ou en CI) pour confirmer que les étapes documentées sont exactes
- Si une étape manuelle est requise, la lister clairement

**Règles additionnelles obligatoires** :
- **Langue** : la documentation descriptive doit être rédigée en français. Les blocs de code, exemples, commandes et fichiers de configuration restent inchangés (ne pas traduire les commandes ni le code).
- **Tests** : pour chaque modification fonctionnelle (code, configuration, scripts), ajouter dans la documentation une section « Tests » décrivant comment exécuter les tests (unitaires, d'intégration ou manuels) et les critères d'acceptation.
- **Validation** : si les tests passent et la modification est validée, mettre à jour la documentation immédiatement avec une note "Tests OK — documentation mise à jour" incluant date et auteur. Si les tests échouent, documenter l'échec et les actions correctives prévues.

**Historique des changements récents** :
- 2025-11-28 — asahraoui — Correction bind mounts logs sous Windows/WSL. Fichiers : `podman-compose.yml`. Notes : Suppression des volumes `./logs/apache`, `./logs/php`, `./logs/postgres` qui causaient des erreurs de chemin `/mnt/c/` sous WSL. Utiliser `podman logs <container>` pour accéder aux logs. Tests OK — documentation mise à jour.
- 2025-11-28 — asahraoui — Ajout module custom Drupal `my_list_field` avec exemples de contrôle de save (FieldType extends ListItemBase, hook_entity_presave, EventSubscriber). Fichiers : `src/web/modules/custom/my_list_field/`. Tests OK.
- 2025-11-27 — asahraoui — Ajout support WebP et AVIF dans GD pour optimisation images. Fichiers : `docker/php/Dockerfile`, `docs/03_CONTAINER_PHP_INSTALL.md`. Notes : WebP (~30% plus léger), AVIF (~50% plus léger). Tests OK.
- 2025-11-25 — Mise à jour PostgreSQL 15 → 16, correction modules Apache obligatoires (proxy, proxy_fcgi, rewrite). Documentation mise à jour avec liste des modules requis vs recommandés. Tests OK.
- 2025-11-25 — Script de gestion des modules Apache ajouté (`manage-apache-modules.sh/ps1`). Permet d'activer/désactiver facilement headers, expires, deflate, ssl, etc. Tests OK — documentation mise à jour.
- 2025-11-25 — Clean URLs activés (mod_rewrite), scripts d'initialisation Podman/WSL ajoutés, migration vers `podman compose`. Tests OK — documentation mise à jour.
- 2025-11-24 — Setup initial Podman/Drupal 11 avec PHP-FPM et PostgreSQL.

-------------------------
14) Gestion des modules Apache

Un script dédié permet d'activer ou désactiver facilement les modules Apache sans éditer manuellement le Dockerfile.

**Commandes disponibles** :

```bash
# Bash (Linux/WSL)
chmod +x scripts/manage-apache-modules.sh

# Afficher les modules actuellement activés
./scripts/manage-apache-modules.sh status

# Lister les modules disponibles
./scripts/manage-apache-modules.sh list

# Activer un ou plusieurs modules (reconstruit automatiquement)
./scripts/manage-apache-modules.sh enable headers expires

# Activer et redémarrer immédiatement
./scripts/manage-apache-modules.sh enable deflate --restart

# Désactiver des modules
./scripts/manage-apache-modules.sh disable ssl

# Modifier sans reconstruire (pour faire plusieurs changements)
./scripts/manage-apache-modules.sh enable headers --no-rebuild
./scripts/manage-apache-modules.sh enable expires --no-rebuild
./scripts/manage-apache-modules.sh rebuild --restart
```

**PowerShell (Windows)** :

```powershell
# Afficher le statut
.\scripts\manage-apache-modules.ps1 status

# Activer des modules
.\scripts\manage-apache-modules.ps1 enable headers expires

# Activer avec redémarrage
.\scripts\manage-apache-modules.ps1 enable deflate -Restart

# Désactiver
.\scripts\manage-apache-modules.ps1 disable ssl
```

**Modules Apache pour Drupal** :

**Modules OBLIGATOIRES (requis pour l'installation)** :
- **proxy** — Proxy inverse (requis pour PHP-FPM)
- **proxy_fcgi** — Interface FastCGI (requis pour communiquer avec PHP-FPM)
- **rewrite** — Clean URLs (requis pour Drupal)

**Modules RECOMMANDÉS (performance et sécurité)** :
- **headers** — Manipulation des en-têtes HTTP (CORS, sécurité)
- **expires** — Contrôle du cache navigateur
- **deflate** — Compression gzip des réponses

**Modules OPTIONNELS** :
- **ssl** — Support HTTPS (avec certificats)
- **remoteip** — Détection IP réelle derrière proxy/load balancer

**Exemple de configuration optimale Drupal** :

```bash
# Les modules proxy, proxy_fcgi et rewrite sont déjà activés par défaut
# Activer les modules recommandés supplémentaires pour la performance
./scripts/manage-apache-modules.sh enable headers expires deflate --restart
```

**⚠️ ATTENTION** : Si vous désactivez `proxy`, `proxy_fcgi` ou `rewrite`, PHP ne fonctionnera plus et Apache renverra le code source PHP brut au lieu de l'exécuter. Ces modules doivent toujours rester activés.

**Modules OBLIGATOIRES (requis pour l'installation)** :
- **proxy** — Proxy inverse (requis pour PHP-FPM)
- **proxy_fcgi** — Interface FastCGI (requis pour communiquer avec PHP-FPM)
- **rewrite** — Clean URLs (requis pour Drupal)

**Modules RECOMMANDÉS (performance et sécurité)** :
- **headers** — Manipulation des en-têtes HTTP (CORS, sécurité)
- **expires** — Contrôle du cache navigateur
- **deflate** — Compression gzip des réponses

**Modules OPTIONNELS** :
- **ssl** — Support HTTPS (avec certificats)
- **remoteip** — Détection IP réelle derrière proxy/load balancer

**Exemple de configuration optimale Drupal** :

```bash
# Les modules proxy, proxy_fcgi et rewrite sont déjà activés par défaut
# Activer les modules recommandés supplémentaires pour la performance
./scripts/manage-apache-modules.sh enable headers expires deflate --restart
```

**Note importante** : Si vous désactivez `proxy`, `proxy_fcgi` ou `rewrite`, PHP ne fonctionnera plus et Apache renverra le code source PHP brut. Ces modules doivent toujours rester activés.

Le script :
- Modifie automatiquement le `Dockerfile`
- Crée une sauvegarde (`.bak`)
- Reconstruit l'image Apache
- Peut redémarrer le conteneur immédiatement si `--restart` est passé
