# Setup Full Script - Installation complète automatisée

Script d'installation et de gestion automatisée de l'environnement Drupal 11 avec Podman.

## Description

Le script `SETUP_FULL.sh` permet de gérer l'environnement Drupal 11 avec deux modes :
1. **Réinitialisation complète** : Supprime tout et réinstalle l'environnement de zéro
2. **Démarrage simple** : Démarre les containers existants

## Fonctionnalités

✅ Installation automatisée complète de Drupal 11  
✅ Construction des images Apache et PHP  
✅ Configuration PostgreSQL avec healthcheck  
✅ Installation via Composer avec timeout optimisé  
✅ Configuration automatique des permissions  
✅ Validation HTTP de l'installation  
✅ Démarrage/arrêt des containers existants  
✅ Messages colorés et informatifs  

## Prérequis

- Podman installé et configuré
- Podman Machine démarrée
- Bash ou Git Bash (sous Windows)
- Connexion Internet (pour télécharger les images et packages)
- Fichiers `composer.json` et `composer.lock` dans le dossier `src/`

## Utilisation

### 1. Rendre le script exécutable

```bash
chmod +x scripts/SETUP_FULL.sh
```

### 2. Exécuter le script

```bash
./scripts/SETUP_FULL.sh
```

### 3. Choisir une option

Le script affiche un menu interactif :

```
==========================================
  Installation Drupal 11 avec Podman
==========================================

Choisissez une option:
  1) Réinitialisation complète (supprime tout et réinstalle)
  2) Démarrer les containers existants

Votre choix (1 ou 2):
```

## Mode 1 : Réinitialisation complète

### Étapes automatisées

1. **Confirmation de sécurité**
   - Demande de confirmation avant suppression

2. **Nettoyage complet**
   - Arrêt de tous les containers
   - Suppression des containers, images, volumes, réseaux
   - Nettoyage système Podman
   - Nettoyage du dossier `src/` (conservation de composer.json/lock)

3. **Création de l'infrastructure**
   - Réseau `drupal_net`
   - Volume `drupal_db_data`

4. **Construction des images**
   - Image Apache (`myapache:latest`) ~121 MB
   - Image PHP (`myphp:8.3-dev`) ~688 MB

5. **Démarrage des containers**
   - PostgreSQL (avec healthcheck, attente 20s)
   - PHP-FPM (avec volume monté)
   - Apache (port 8080:80, avec volume monté)

6. **Installation Drupal**
   - Composer install avec timeout 1200s
   - Installation de 64 packages
   - Scaffolding complet (index.php, .htaccess, etc.)

7. **Configuration Drupal**
   - Copie de `settings.php`
   - Permissions sur `sites/default`
   - Création du dossier `files` avec permissions

8. **Validation**
   - Test HTTP sur `localhost:8080`
   - Vérification de la redirection vers l'installateur

### Sortie attendue

```bash
[INFO] Démarrage de la réinitialisation complète...
[INFO] Arrêt des containers...
[WARNING] Aucun container à arrêter
[INFO] Suppression des containers...
[SUCCESS] Nettoyage terminé!
[INFO] Création du réseau drupal_net...
[INFO] Création du volume drupal_db_data...
[INFO] Construction de l'image Apache (myapache:latest)...
[SUCCESS] Image Apache construite avec succès
[INFO] Construction de l'image PHP (myphp:8.3-dev)...
[SUCCESS] Image PHP construite avec succès
[INFO] Démarrage du container PostgreSQL...
[INFO] Attente du démarrage de PostgreSQL (20 secondes)...
[SUCCESS] PostgreSQL est prêt
[INFO] Démarrage du container PHP-FPM...
[SUCCESS] Container PHP démarré
[INFO] Démarrage du container Apache...
[SUCCESS] Container Apache démarré
[INFO] Installation de Drupal via Composer (peut prendre 10-15 minutes)...
[WARNING] Cette étape peut être longue, surtout l'extraction de drupal/core...
[SUCCESS] Installation Composer terminée avec succès
[INFO] Configuration des permissions...
[SUCCESS] Permissions configurées
[INFO] Test de l'accès HTTP...
[SUCCESS] Le site est accessible sur http://localhost:8080

==========================================
  Installation terminée avec succès!
==========================================

Accédez à l'installateur Drupal: http://localhost:8080

Configuration de la base de données:
  - Type: PostgreSQL
  - Nom de la base: drupal
  - Utilisateur: drupal
  - Mot de passe: drupal
  - Hôte (options avancées): db
  - Port: 5432
```

### Durée estimée

- **Nettoyage** : 1-2 minutes
- **Build Apache** : 2-5 minutes
- **Build PHP** : 5-10 minutes
- **Démarrage PostgreSQL** : 20-30 secondes
- **Installation Composer** : 10-20 minutes (selon la connexion et WSL2)
- **Total** : ~20-40 minutes

## Mode 2 : Démarrage des containers existants

### Utilisation

Simple et rapide, démarre les containers déjà créés.

### Étapes automatisées

1. **Vérification des containers**
   - Vérifie l'existence des containers `db`, `php`, `web`

2. **Démarrage séquentiel**
   - Démarrage de `db`
   - Démarrage de `php`
   - Démarrage de `web`
   - Attente 5 secondes

3. **Validation**
   - Affichage du statut des containers
   - Test HTTP sur `localhost:8080`

### Sortie attendue

```bash
[INFO] Démarrage des containers existants...
[INFO] Démarrage du container db...
db
[INFO] Démarrage du container php...
php
[INFO] Démarrage du container web...
web
[INFO] Statut des containers:
CONTAINER ID  IMAGE                          COMMAND           CREATED       STATUS                   PORTS
e92f24c8060e  docker.io/library/postgres:16  postgres          21 hours ago  Up 8 seconds (starting)  5432/tcp
78087166270e  localhost/myphp:8.3-dev        php-fpm           21 hours ago  Up 7 seconds             9000/tcp
98dc32f58a43  localhost/myapache:latest      httpd-foreground  21 hours ago  Up 6 seconds             0.0.0.0:8080->80/tcp
[INFO] Test de l'accès HTTP...
[SUCCESS] Le site est accessible sur http://localhost:8080
[SUCCESS] Containers démarrés avec succès!
```

### Durée estimée

- **Total** : ~10-15 secondes

## Configuration de la base de données Drupal

Après l'installation, accédez à `http://localhost:8080` et configurez :

1. **Langue** : Sélectionnez votre langue
2. **Profil** : Standard
3. **Base de données** :
   - **Type** : PostgreSQL
   - **Nom de la base** : `drupal`
   - **Utilisateur** : `drupal`
   - **Mot de passe** : `drupal`
   - **Hôte avancé** : `db`
   - **Port** : `5432`
4. **Configuration du site** : Informations administrateur

## Structure créée

```
src/
├── composer.json           # Dépendances PHP (versionné)
├── composer.lock           # Versions exactes (versionné)
├── .editorconfig          # Configuration éditeur
├── .gitattributes         # Attributs Git
├── vendor/                # Packages Composer (64 packages)
│   ├── drupal/core/       # Drupal core 11.2.8
│   ├── symfony/           # Composants Symfony
│   ├── guzzlehttp/        # Client HTTP
│   └── ...
└── web/                   # Document root (racine web)
    ├── index.php          # Point d'entrée
    ├── .htaccess          # Configuration Apache
    ├── core/              # Core Drupal
    ├── modules/           # Modules contrib
    ├── themes/            # Thèmes
    ├── profiles/          # Profils d'installation
    └── sites/
        └── default/
            ├── settings.php       # Configuration (permissions 666)
            └── files/             # Fichiers uploadés (permissions 777)
```

## Containers créés

| Container | Image | Port | Réseau | Volume |
|-----------|-------|------|--------|--------|
| `db` | postgres:16 | 5432 | drupal_net | drupal_db_data |
| `php` | myphp:8.3-dev | 9000 | drupal_net | ./src → /var/www/html |
| `web` | myapache:latest | 8080→80 | drupal_net | ./src → /var/www/html |

## Gestion de l'erreur Composer timeout

Le script utilise `COMPOSER_PROCESS_TIMEOUT=1200` (20 minutes) pour éviter les timeouts lors de l'extraction de `drupal/core` dans WSL2.

### Si le timeout persiste

```bash
# Augmenter le timeout manuellement
podman exec php sh -c 'cd /var/www/html && COMPOSER_PROCESS_TIMEOUT=2400 composer install'
```

## Commandes utiles après installation

```bash
# Voir les logs Apache
podman logs -f web

# Voir les logs PHP-FPM
podman logs -f php

# Voir les logs PostgreSQL
podman logs -f db

# Accéder au shell PHP
podman exec -it php sh

# Accéder à PostgreSQL
podman exec -it db psql -U drupal

# Arrêter tous les containers
podman stop db php web

# Redémarrer tous les containers
podman start db php web
```

## Dépannage

### Erreur "No such file or directory"

```bash
# Vérifier que vous êtes dans le bon dossier
pwd
# Devrait afficher : .../asahraoui_labs/projects/podman

# Vérifier que le script existe
ls -la scripts/SETUP_FULL.sh
```

### Timeout Composer persistant

```bash
# Augmenter les ressources Podman Machine
podman machine stop
podman machine set --cpus 4 --memory 4096
podman machine start
```

### Port 8080 déjà utilisé

```bash
# Trouver le processus utilisant le port
netstat -ano | grep 8080

# Changer le port dans la commande podman run
# Remplacer -p 8080:80 par -p 8081:80
```

### PostgreSQL pas healthy

```bash
# Attendre plus longtemps (jusqu'à 1 minute)
podman exec db pg_isready -U drupal

# Vérifier les logs
podman logs db
```

## Bonnes pratiques

- **Sauvegardez vos données** avant une réinitialisation complète
- **Utilisez le mode 2** pour les redémarrages quotidiens
- **Gardez composer.lock** versionné pour la reproductibilité
- **Documentez vos modifications** de configuration
- **Testez sur un environnement de dev** avant la production

## Différences avec cleanup.sh

| Caractéristique | cleanup.sh | SETUP_FULL.sh |
|-----------------|------------|---------------|
| **Nettoyage** | ✅ | ✅ (mode 1) |
| **Installation** | ❌ | ✅ (mode 1) |
| **Démarrage** | ❌ | ✅ (mode 2) |
| **Build images** | ❌ | ✅ |
| **Composer install** | ❌ | ✅ |
| **Configuration Drupal** | ❌ | ✅ |
| **Tests automatisés** | ❌ | ✅ |

## Voir aussi

- [06 - Cleanup Script](06_CLEANUP_SCRIPT.md) - Nettoyage simple de l'environnement
- [05 - Installation Drupal](05_DRUPAL_INSTALLATION.md) - Installation manuelle détaillée
- [01 - Installation Podman](01_PODMAN_INSTALL.md) - Configuration Podman/WSL2
- [README.md](../README.md) - Guide de démarrage rapide

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2
