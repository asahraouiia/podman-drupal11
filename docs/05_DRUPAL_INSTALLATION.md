# Guide d'installation Drupal 11

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Prérequis](#1-prérequis)
2. [Installation de Drupal](#2-installation-de-drupal)
3. [Configuration de la base de données](#3-configuration-de-la-base-de-données)
4. [Configuration Drupal](#4-configuration-drupal)
5. [Vérifications post-installation](#5-vérifications-post-installation)
6. [Gestion de Drupal](#6-gestion-de-drupal)
7. [Modules et thèmes](#7-modules-et-thèmes)
8. [Mise à jour Drupal](#8-mise-à-jour-drupal)
9. [Dépannage Drupal](#9-dépannage-drupal)
10. [Drush et outils](#10-drush-et-outils)

---

## 1. Prérequis

### 1.1. Environnement requis

Avant d'installer Drupal, assurez-vous que :

✅ Les conteneurs Podman sont démarrés (voir [PODMAN_ENVIRONMENT.md](PODMAN_ENVIRONMENT.md))  
✅ Apache, PHP-FPM et PostgreSQL sont opérationnels  
✅ Le dossier `src/` existe et est accessible  

```bash
# Vérifier que les conteneurs sont actifs
podman ps

# Sortie attendue : web, php, db tous "Up" et "healthy"
```

### 1.2. Structure du projet

```
src/
├── composer.json        # Dépendances Drupal (à versionner)
├── composer.lock        # Versions exactes (à versionner)
├── web/                 # Racine web (généré, ignoré par Git)
│   ├── index.php
│   ├── core/            # Core Drupal
│   ├── modules/         # Modules contrib/custom
│   ├── themes/          # Thèmes
│   └── sites/
│       └── default/
│           ├── files/   # Fichiers uploadés (persistant)
│           └── settings.php
├── vendor/              # Dépendances PHP (généré, ignoré par Git)
└── recipes/             # Recettes Drupal (généré, ignoré par Git)
```

**Éléments versionnés** : `composer.json`, `composer.lock`  
**Éléments générés** : `web/`, `vendor/`, `recipes/`

---

## 2. Installation de Drupal

### 2.1. Installation automatique (recommandé)

```bash
# Script d'installation Drupal
./scripts/drupal-install.sh

# Ou via Makefile
make drupal-install
```

**Ce que fait ce script** :
1. Vérifie que les conteneurs sont actifs
2. Installe Drupal 11 via Composer dans `/var/www/html`
3. Corrige les permissions des fichiers
4. Affiche l'URL d'accès

**Durée estimée** : 5-15 minutes (selon la connexion internet)

### 2.2. Installation manuelle

#### 2.2.1. Installation via Composer

```bash
# Se connecter au conteneur PHP
podman exec -it php bash

# Installer Drupal 11 avec Composer
cd /var/www/html
COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 . --no-interaction

# Vérifier l'installation
ls -la web/
# Doit contenir: index.php, core/, modules/, themes/, sites/
```

#### 2.2.2. Corriger les permissions

```bash
# Depuis le conteneur PHP
chown -R www-data:www-data /var/www/html

# Créer le dossier files si absent
mkdir -p web/sites/default/files
chmod -R 775 web/sites/default/files
chown -R www-data:www-data web/sites/default/files
```

#### 2.2.3. Vérifier l'accès web

```bash
# Ouvrir dans le navigateur
http://localhost:8080

# Ou tester en ligne de commande
curl -I http://localhost:8080
# Sortie attendue: HTTP/1.1 200 OK ou 302 Found
```

---

## 3. Configuration de la base de données

### 3.1. Installation via l'interface web

#### 3.1.1. Accéder à l'installateur

1. Ouvrir le navigateur : `http://localhost:8080`
2. L'installateur Drupal se lance automatiquement

#### 3.1.2. Choisir le profil d'installation

- **Standard** : Installation complète avec modules de base (recommandé)
- **Minimal** : Installation minimale
- **Demo (Umami)** : Site de démonstration avec contenu

#### 3.1.3. Configurer la base de données

À l'étape "Set up database", entrer les paramètres suivants :

| Paramètre | Valeur |
|-----------|--------|
| **Type de base de données** | PostgreSQL |
| **Nom de la base** | `drupal` |
| **Utilisateur** | `drupal` |
| **Mot de passe** | `drupal` |
| **Hôte avancé** | `db` |
| **Port** | `5432` |

**⚠️ Cliquer sur "Advanced options" pour accéder aux champs "Hôte" et "Port"**

#### 3.1.4. Configurer le site

- **Nom du site** : Ex: "Mon site Drupal"
- **Email du site** : Ex: `admin@example.com`
- **Nom d'utilisateur** : Ex: `admin`
- **Mot de passe** : **Créer un mot de passe fort**
- **Email administrateur** : Ex: `admin@example.com`

### 3.2. Configuration manuelle de settings.php

Si vous préférez configurer manuellement :

```bash
# Se connecter au conteneur PHP
podman exec -it php bash

# Copier le fichier de configuration par défaut
cd /var/www/html/web/sites/default
cp default.settings.php settings.php
chmod 644 settings.php
```

Ajouter la configuration de la base de données dans `settings.php` :

```php
<?php

$databases['default']['default'] = [
  'database' => 'drupal',
  'username' => 'drupal',
  'password' => 'drupal',
  'prefix' => '',
  'host' => 'db',
  'port' => '5432',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'driver' => 'pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];

$settings['hash_salt'] = 'GÉNÉRER_UNE_CHAÎNE_ALÉATOIRE_ICI';
$settings['config_sync_directory'] = '../config/sync';
```

**Générer un hash_salt** :

```bash
# Depuis le conteneur PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 3.3. Installation en ligne de commande (Drush)

```bash
# Installer Drush d'abord
podman exec php bash -c "cd /var/www/html && composer require drush/drush"

# Installer Drupal avec Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush site:install standard \
  --db-url=pgsql://drupal:drupal@db:5432/drupal \
  --site-name='Mon site Drupal' \
  --account-name=admin \
  --account-pass=admin123 \
  --yes"
```

---

## 4. Configuration Drupal

### 4.1. Configuration de base

#### 4.1.1. Paramètres régionaux

1. Aller dans **Configuration** → **Regional and language** → **Regional settings**
2. Configurer :
   - **Fuseau horaire** : Europe/Paris
   - **Premier jour de la semaine** : Lundi
   - **Format de date** : Personnalisé

#### 4.1.2. Clean URLs

Les Clean URLs sont **déjà activés** grâce à `mod_rewrite` dans Apache.

Vérifier :

```
http://localhost:8080/user/login  ✅ Clean URL
http://localhost:8080/?q=user/login  ❌ Ancien format
```

#### 4.1.3. Configuration des fichiers

1. Aller dans **Configuration** → **Media** → **File system**
2. Vérifier :
   - **Public file system path** : `sites/default/files`
   - **Private file system path** : (optionnel) `sites/default/private`

```bash
# Créer le dossier privé si besoin
podman exec php bash -c "mkdir -p /var/www/html/web/sites/default/private && \
  chmod -R 770 /var/www/html/web/sites/default/private && \
  chown -R www-data:www-data /var/www/html/web/sites/default/private"
```

### 4.2. Performance

#### 4.2.1. Activer les caches

1. Aller dans **Configuration** → **Development** → **Performance**
2. Activer :
   - ✅ Aggregate CSS files
   - ✅ Aggregate JavaScript files
   - ✅ Cache pages for anonymous users
   - **Page cache maximum age** : 15 minutes (ou plus)

#### 4.2.2. Configuration PHP (déjà optimisée)

Le fichier `docker/php/php.ini` contient déjà :

- **OPcache** : Cache d'opcodes activé
- **APCu** : Cache utilisateur activé
- **memory_limit** : 512M
- **max_execution_time** : 300s

### 4.3. Sécurité

#### 4.3.1. Protéger settings.php

```bash
# Mettre en lecture seule
podman exec php chmod 444 /var/www/html/web/sites/default/settings.php
```

#### 4.3.2. Créer .htaccess pour le dossier private

Si vous utilisez un dossier privé :

```bash
podman exec php bash -c "echo 'Deny from all' > /var/www/html/web/sites/default/private/.htaccess"
```

#### 4.3.3. Configurer les mises à jour automatiques

1. Aller dans **Configuration** → **System** → **Updates**
2. Activer les notifications d'email pour les mises à jour

---

## 5. Vérifications post-installation

### 5.1. Tests fonctionnels

#### 5.1.1. Accès au site

```bash
# Page d'accueil
curl -I http://localhost:8080
# Attendu: HTTP/1.1 200 OK

# Page de connexion
curl -I http://localhost:8080/user/login
# Attendu: HTTP/1.1 200 OK

# Interface d'administration
curl -I http://localhost:8080/admin
# Attendu: HTTP/1.1 200 OK (si connecté) ou 403 (si non connecté)
```

#### 5.1.2. Vérifier PHP

Créer un fichier de test :

```bash
podman exec php bash -c "cat > /var/www/html/web/phpinfo.php <<'EOF'
<?php
phpinfo();
EOF"

# Tester dans le navigateur
http://localhost:8080/phpinfo.php
```

**⚠️ Supprimer après test** :

```bash
podman exec php rm /var/www/html/web/phpinfo.php
```

#### 5.1.3. Vérifier la connexion PostgreSQL

```bash
# Depuis Drupal
podman exec php bash -c "cd /var/www/html && vendor/bin/drush sql:query 'SELECT version();'"

# Ou directement depuis PostgreSQL
podman exec db psql -U drupal -c "SELECT version();"
# Attendu: PostgreSQL 16.x
```

### 5.2. Status Report

1. Se connecter en tant qu'administrateur
2. Aller dans **Reports** → **Status report** (`/admin/reports/status`)
3. Vérifier qu'il n'y a **pas d'erreurs critiques**

**Problèmes courants** :

| Problème | Solution |
|----------|----------|
| Trusted Host Settings | Ajouter dans `settings.php` : `$settings['trusted_host_patterns'] = ['^localhost$'];` |
| Cron not running | Configurer le cron (voir section 6.4) |
| Update notifications | Activer le module "Update Manager" |

---

## 6. Gestion de Drupal

### 6.1. Se connecter à l'administration

```
URL: http://localhost:8080/user/login
Identifiant: admin (ou celui choisi lors de l'installation)
Mot de passe: (celui défini lors de l'installation)
```

### 6.2. Vider les caches

#### Via l'interface

1. Aller dans **Configuration** → **Development** → **Performance**
2. Cliquer sur **Clear all caches**

#### Via Drush

```bash
# Vider tous les caches
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cache:rebuild"

# Alias court
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cr"
```

### 6.3. Mettre à jour la base de données

Après une mise à jour de modules ou du core :

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush updatedb -y"

# Alias court
podman exec php bash -c "cd /var/www/html && vendor/bin/drush updb -y"
```

### 6.4. Configurer le Cron

#### Méthode 1 : Cron Drupal (développement)

1. Aller dans **Configuration** → **System** → **Cron**
2. Définir : "Run cron every 3 hours"

#### Méthode 2 : Cron système (production)

```bash
# Ajouter au crontab de l'hôte
crontab -e

# Exécuter le cron Drupal toutes les heures
0 * * * * podman exec php bash -c "cd /var/www/html && vendor/bin/drush cron" > /dev/null 2>&1
```

#### Méthode 3 : URL externe (pas recommandé)

```
http://localhost:8080/cron/VOTRE_CLE_CRON
```

Trouver la clé cron dans **Configuration** → **System** → **Cron**.

### 6.5. Exporter/Importer la configuration

#### Exporter la configuration

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush config:export -y"

# Les fichiers sont dans config/sync/
ls config/sync/
```

#### Importer la configuration

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush config:import -y"
```

**Usage** : Synchroniser la configuration entre environnements (dev → staging → production)

---

## 7. Modules et thèmes

### 7.1. Installer des modules

#### Via Composer (recommandé)

```bash
# Installer un module contrib
podman exec php bash -c "cd /var/www/html && composer require drupal/admin_toolbar"

# Installer plusieurs modules
podman exec php bash -c "cd /var/www/html && composer require \
  drupal/admin_toolbar \
  drupal/pathauto \
  drupal/token"
```

#### Activer les modules

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush en admin_toolbar -y"

# Ou via l'interface
# Extend → Cocher les modules → Install
```

#### Modules recommandés

| Module | Description |
|--------|-------------|
| `admin_toolbar` | Améliore la barre d'administration |
| `pathauto` | Génération automatique des URLs |
| `token` | Système de tokens pour pathauto |
| `metatag` | Gestion des balises meta (SEO) |
| `redirect` | Gestion des redirections 301 |
| `webform` | Formulaires avancés |

### 7.2. Installer des thèmes

#### Via Composer

```bash
# Installer un thème contrib
podman exec php bash -c "cd /var/www/html && composer require drupal/bootstrap5"
```

#### Activer un thème

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush theme:enable bootstrap5"

# Définir comme thème par défaut
podman exec php bash -c "cd /var/www/html && vendor/bin/drush config:set system.theme default bootstrap5 -y"
```

#### Ou via l'interface

1. Aller dans **Appearance**
2. Trouver le thème
3. Cliquer sur **Install and set as default**

### 7.3. Créer un module personnalisé

```bash
# Se connecter au conteneur
podman exec -it php bash

# Créer la structure
cd /var/www/html/web/modules
mkdir custom
cd custom
mkdir my_module
cd my_module

# Créer le fichier .info.yml
cat > my_module.info.yml <<'EOF'
name: My Module
type: module
description: 'Mon module personnalisé'
package: Custom
core_version_requirement: ^11
EOF

# Créer le fichier .module
cat > my_module.module <<'EOF'
<?php

/**
 * @file
 * Mon module personnalisé.
 */

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_help().
 */
function my_module_help($route_name, RouteMatchInterface $route_match) {
  if ($route_name === 'help.page.my_module') {
    return '<p>Aide de mon module personnalisé.</p>';
  }
}
EOF
```

Activer le module :

```bash
podman exec php bash -c "cd /var/www/html && vendor/bin/drush en my_module -y"
```

### 7.4. Désinstaller un module

```bash
# Désactiver puis désinstaller
podman exec php bash -c "cd /var/www/html && vendor/bin/drush pm:uninstall admin_toolbar -y"

# Supprimer le code
podman exec php bash -c "cd /var/www/html && composer remove drupal/admin_toolbar"
```

---

## 8. Mise à jour Drupal

### 8.1. Avant de mettre à jour

#### 8.1.1. Sauvegarder la base de données

```bash
# Dump de la base
podman exec db pg_dump -U drupal drupal > backup_$(date +%Y%m%d_%H%M).sql

# Ou via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush sql:dump > /tmp/backup.sql"
```

#### 8.1.2. Sauvegarder les fichiers

```bash
# Archiver le dossier src/
tar -czf backup_src_$(date +%Y%m%d_%H%M).tar.gz src/
```

#### 8.1.3. Mettre le site en maintenance

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer"

# Via l'interface
# Configuration → Development → Maintenance mode
```

### 8.2. Mettre à jour le core Drupal

```bash
# Vérifier les mises à jour disponibles
podman exec php bash -c "cd /var/www/html && composer outdated 'drupal/*'"

# Mettre à jour vers une version mineure (ex: 11.0.0 → 11.0.5)
podman exec php bash -c "cd /var/www/html && composer update drupal/core 'drupal/core-*' --with-all-dependencies"

# Mettre à jour vers une version majeure (ex: 11.0.0 → 12.0.0)
podman exec php bash -c "cd /var/www/html && composer require drupal/core-recommended:^12 drupal/core-composer-scaffold:^12 drupal/core-project-message:^12 --update-with-all-dependencies"
```

### 8.3. Mettre à jour les modules

```bash
# Mettre à jour tous les modules
podman exec php bash -c "cd /var/www/html && composer update 'drupal/*' --with-all-dependencies"

# Mettre à jour un module spécifique
podman exec php bash -c "cd /var/www/html && composer update drupal/admin_toolbar --with-dependencies"
```

### 8.4. Appliquer les mises à jour de la base

```bash
# Mettre à jour la base de données
podman exec php bash -c "cd /var/www/html && vendor/bin/drush updatedb -y"

# Vider les caches
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cache:rebuild"
```

### 8.5. Désactiver le mode maintenance

```bash
# Via Drush
podman exec php bash -c "cd /var/www/html && vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer"
```

### 8.6. Vérifier le Status Report

```
http://localhost:8080/admin/reports/status
```

Vérifier qu'il n'y a pas d'erreurs.

---

## 9. Dépannage Drupal

### 9.1. "The website encountered an unexpected error"

#### Symptôme

Page blanche avec message d'erreur générique.

#### Solution

```bash
# Vérifier les logs PHP
podman logs php

# Vérifier les logs Apache
tail -f logs/apache/error.log

# Vider les caches
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cr"

# Vérifier les permissions
podman exec php chown -R www-data:www-data /var/www/html
```

### 9.2. Erreur de connexion à la base de données

#### Symptôme

```
Drupal\Core\Database\ConnectionNotDefinedException
```

#### Solution

```bash
# Vérifier que PostgreSQL est actif
podman ps | grep db

# Tester la connexion depuis PHP
podman exec php bash -c "php -r \"new PDO('pgsql:host=db;dbname=drupal', 'drupal', 'drupal');\""

# Vérifier settings.php
podman exec php cat /var/www/html/web/sites/default/settings.php | grep -A 10 databases
```

### 9.3. Permission denied sur sites/default/files

#### Symptôme

Impossible d'uploader des fichiers.

#### Solution

```bash
# Corriger les permissions
podman exec php bash -c "chmod -R 775 /var/www/html/web/sites/default/files"
podman exec php bash -c "chown -R www-data:www-data /var/www/html/web/sites/default/files"
```

### 9.4. Page blanche sans message d'erreur

#### Solution

```bash
# Activer l'affichage des erreurs temporairement
podman exec php bash -c "cat >> /var/www/html/web/sites/default/settings.php <<'EOF'
\$config['system.logging']['error_level'] = 'verbose';
EOF"

# Recharger la page pour voir les erreurs

# Puis désactiver après débogage
# Supprimer les lignes ajoutées
```

### 9.5. Cron ne s'exécute pas

#### Diagnostic

```bash
# Vérifier la dernière exécution du cron
podman exec php bash -c "cd /var/www/html && vendor/bin/drush core:cron"

# Voir les logs
podman exec php bash -c "cd /var/www/html && vendor/bin/drush watchdog:show --type=cron"
```

#### Solution

```bash
# Forcer l'exécution
podman exec php bash -c "cd /var/www/html && vendor/bin/drush cron"

# Vérifier la configuration cron
# Configuration → System → Cron
```

### 9.6. Module incompatible après mise à jour

#### Symptôme

```
The following module is missing from the file system: <module_name>
```

#### Solution

```bash
# Réinstaller le module
podman exec php bash -c "cd /var/www/html && composer require drupal/<module_name>"

# Ou désinstaller définitivement
podman exec php bash -c "cd /var/www/html && vendor/bin/drush pm:uninstall <module_name> -y"
```

---

## 10. Drush et outils

### 10.1. Installation de Drush

```bash
# Installer Drush via Composer
podman exec php bash -c "cd /var/www/html && composer require drush/drush"

# Vérifier l'installation
podman exec php bash -c "cd /var/www/html && vendor/bin/drush --version"
```

### 10.2. Commandes Drush essentielles

| Commande | Description |
|----------|-------------|
| `drush cr` | Vider tous les caches |
| `drush updb -y` | Mettre à jour la base de données |
| `drush cron` | Exécuter le cron |
| `drush sql:query "SELECT * FROM users"` | Exécuter une requête SQL |
| `drush sql:dump > backup.sql` | Dump de la base |
| `drush config:export -y` | Exporter la configuration |
| `drush config:import -y` | Importer la configuration |
| `drush user:login` | Générer un lien de connexion admin |
| `drush pm:list` | Lister tous les modules |
| `drush watchdog:show` | Voir les logs système |

### 10.3. Créer un alias Drush

Pour simplifier les commandes :

```bash
# Ajouter au .bashrc ou .zshrc
echo "alias drush='podman exec php bash -c \"cd /var/www/html && vendor/bin/drush\"'" >> ~/.bashrc
source ~/.bashrc

# Utilisation
drush cr
drush status
```

### 10.4. Générer un lien de connexion one-time

```bash
# Générer un lien de connexion pour l'utilisateur 1 (admin)
podman exec php bash -c "cd /var/www/html && vendor/bin/drush user:login"

# Sortie : http://localhost:8080/user/reset/1/...
```

Copier ce lien dans le navigateur pour se connecter directement.

### 10.5. Réinitialiser le mot de passe admin

```bash
# Réinitialiser le mot de passe de l'utilisateur admin (uid 1)
podman exec php bash -c "cd /var/www/html && vendor/bin/drush user:password admin 'nouveau_mot_de_passe'"
```

---

## Aide-mémoire Drupal

| Action | Commande |
|--------|----------|
| **Installer Drupal** | `./scripts/drupal-install.sh` |
| **Vider les caches** | `drush cr` |
| **Mettre à jour la DB** | `drush updb -y` |
| **Exécuter le cron** | `drush cron` |
| **Installer un module** | `composer require drupal/<module>` |
| **Activer un module** | `drush en <module> -y` |
| **Désinstaller un module** | `drush pm:uninstall <module> -y` |
| **Exporter la config** | `drush config:export -y` |
| **Importer la config** | `drush config:import -y` |
| **Dump de la base** | `drush sql:dump > backup.sql` |
| **Lien de connexion** | `drush user:login` |
| **Status report** | `http://localhost:8080/admin/reports/status` |

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2

**Voir aussi** :
- [Environnement Podman](PODMAN_ENVIRONMENT.md) - Configuration et gestion de Podman
- [README.md](../README.md) - Guide de démarrage rapide
