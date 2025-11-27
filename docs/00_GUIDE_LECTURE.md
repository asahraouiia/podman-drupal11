# Guide de Lecture - Podman Drupal 11

Ce guide vous aide à naviguer dans la documentation selon votre niveau et vos objectifs.

## 🎯 Quel guide lire en premier ?

### Vous êtes débutant avec Podman ?

**Suivez ce parcours dans l'ordre :**

1. **[01 - INSTALLATION PODMAN](01_PODMAN_INSTALL.md)** ⏱️ 30-45 min
   - Installer WSL2 sur Windows
   - Installer Podman Desktop
   - Créer et démarrer votre première machine virtuelle
   - **Objectif** : Avoir Podman fonctionnel sur votre système

2. **[02 - CONTENEUR APACHE](02_CONTAINER_APACHE_INSTALL.md)** ⏱️ 15-20 min
   - Comprendre le rôle d'Apache dans l'architecture
   - Découvrir le Dockerfile Apache
   - Apprendre les modules nécessaires (proxy, fcgi)
   - **Objectif** : Comprendre le serveur web

3. **[03 - CONTENEUR PHP-FPM](03_CONTAINER_PHP_INSTALL.md)** ⏱️ 15-20 min
   - Comprendre le rôle de PHP-FPM
   - Découvrir les extensions PHP requises
   - Apprendre à gérer Composer
   - **Objectif** : Comprendre l'exécution PHP

4. **[04 - CONTENEUR POSTGRESQL](04_CONTAINER_POSTGRESQL_INSTALL.md)** ⏱️ 15-20 min
   - Comprendre le stockage des données
   - Apprendre les volumes Podman
   - Découvrir les sauvegardes PostgreSQL
   - **Objectif** : Comprendre la persistance des données

5. **[05 - INSTALLATION DRUPAL](05_DRUPAL_INSTALLATION.md)** ⏱️ 20-30 min
   - Installer Drupal via l'interface web
   - Configurer la connexion base de données
   - Découvrir Drush et les outils Drupal
   - **Objectif** : Avoir un site Drupal fonctionnel

**Temps total : ~2 heures**

---

### Vous connaissez déjà Podman ?

**Lecture rapide recommandée :**

1. **[01 - INSTALLATION PODMAN](01_PODMAN_INSTALL.md)** - Section 9 uniquement
   - ✅ Procédure complète validée
   - Commandes exactes pour démarrer l'environnement
   - Syntaxe Windows des volumes (⚠️ important)

2. **Survoler les guides conteneurs** pour comprendre l'architecture
   - Apache : Modules proxy et proxy_fcgi
   - PHP : Extensions installées
   - PostgreSQL : Version 16, credentials par défaut

3. **[05 - INSTALLATION DRUPAL](05_DRUPAL_INSTALLATION.md)** - Si vous ne connaissez pas Drupal

**Temps total : ~30 minutes**

---

### Vous avez déjà un environnement installé ?

**Consultez selon vos besoins :**

| Besoin | Guide | Section |
|--------|-------|---------||
| Installation automatisée complète | [07 - SETUP FULL SCRIPT](07_SETUP_FULL_SCRIPT.md) | Tout le guide |
| Nettoyer l'environnement | [06 - CLEANUP SCRIPT](06_CLEANUP_SCRIPT.md) | Tout le guide |
| Problème Apache | [02 - CONTENEUR APACHE](02_CONTAINER_APACHE_INSTALL.md) | Section 9 - Dépannage |
| Problème PHP | [03 - CONTENEUR PHP-FPM](03_CONTAINER_PHP_INSTALL.md) | Section 10 - Dépannage |
| Problème PostgreSQL | [04 - CONTENEUR POSTGRESQL](04_CONTAINER_POSTGRESQL_INSTALL.md) | Section 10 - Dépannage |
| Problème Drupal | [05 - INSTALLATION DRUPAL](05_DRUPAL_INSTALLATION.md) | Section 9 - Dépannage |
| Ajouter un module Apache | [02 - CONTENEUR APACHE](02_CONTAINER_APACHE_INSTALL.md) | Section 5 - Gestion modules |
| Ajouter une extension PHP | [03 - CONTENEUR PHP-FPM](03_CONTAINER_PHP_INSTALL.md) | Section 4 - Extensions |
| Sauvegarder la BDD | [04 - CONTENEUR POSTGRESQL](04_CONTAINER_POSTGRESQL_INSTALL.md) | Section 7 - Sauvegarde |
| Mettre à jour Drupal | [05 - INSTALLATION DRUPAL](05_DRUPAL_INSTALLATION.md) | Section 5 - Mises à jour |

---

## 📋 Structure de chaque guide

Tous les guides suivent la même structure pour faciliter la navigation :

1. **Introduction** - Qu'est-ce que c'est ?
2. **Prérequis** - Ce qu'il faut avant de commencer
3. **Installation/Configuration** - Étapes détaillées
4. **Vérification** - Comment tester que ça fonctionne
5. **Gestion** - Commandes utiles au quotidien
6. **Optimisation** - Améliorer les performances
7. **Sauvegarde** (si applicable) - Protéger vos données
8. **Bonnes pratiques** - Conseils d'experts
9. **Dépannage** - Solutions aux problèmes courants
10. **Ressources** - Liens utiles

---

## 🚀 Scénarios d'utilisation

### Scénario 1 : Installation complète de zéro

**Situation** : Vous n'avez jamais utilisé Podman, vous voulez installer Drupal.

**Parcours** :
1. Lire [01 - INSTALLATION PODMAN](01_PODMAN_INSTALL.md) sections 1-4
2. Exécuter la [Procédure complète validée](01_PODMAN_INSTALL.md#9-procédure-complète-validée)
3. Lire [05 - INSTALLATION DRUPAL](05_DRUPAL_INSTALLATION.md) sections 1-3
4. Installer Drupal via http://localhost:8080

**Temps : ~1 heure**

---

### Scénario 2 : Environnement ne démarre pas

**Situation** : Vous aviez un environnement fonctionnel, mais maintenant ça ne marche plus.

**Parcours** :
1. Vérifier [Dépannage Podman](01_PODMAN_INSTALL.md#7-dépannage)
2. Si problème persiste : utiliser [06 - CLEANUP SCRIPT](06_CLEANUP_SCRIPT.md)
3. Réinstaller avec [Procédure complète validée](01_PODMAN_INSTALL.md#9-procédure-complète-validée)

**Temps : ~20-40 minutes**

---

### Scénario 3 : Erreur 504 Gateway Timeout

**Situation** : Le site affiche une erreur 504 ou le code PHP s'affiche au lieu de s'exécuter.

**Parcours** :
1. Vérifier [Dépannage Apache](02_CONTAINER_APACHE_INSTALL.md#9-dépannage) - Section "Code PHP affiché"
2. Vérifier [Dépannage PHP](03_CONTAINER_PHP_INSTALL.md#10-dépannage) - Section "PHP-FPM ne répond pas"
3. Vérifier que les modules proxy sont activés :
   ```bash
   podman exec web httpd -M | grep proxy
   ```

**Temps : ~10 minutes**

---

### Scénario 4 : Base de données perdue

**Situation** : Vous avez perdu vos données Drupal après un nettoyage.

**Parcours** :
1. Lire [Sauvegarde PostgreSQL](04_CONTAINER_POSTGRESQL_INSTALL.md#7-sauvegarde-et-restauration)
2. Si vous avez une sauvegarde : restaurer
3. Sinon : réinstaller Drupal via [Installation Drupal](05_DRUPAL_INSTALLATION.md)

**⚠️ Conseil** : Toujours sauvegarder avant un nettoyage !

---

### Scénario 5 : Personnaliser l'environnement

**Situation** : Vous voulez ajouter des extensions PHP ou modules Apache.

**Parcours** :
- Pour Apache : [Gestion modules Apache](02_CONTAINER_APACHE_INSTALL.md#5-gestion-des-modules-apache)
- Pour PHP : [Extensions PHP](03_CONTAINER_PHP_INSTALL.md#4-extensions-php-installées)
- Modifier les Dockerfiles
- Reconstruire les images :
  ```bash
  podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache
  podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
  ```

**Temps : ~30 minutes**

---

## 🔍 Index des problèmes courants

| Problème | Solution | Guide |
|----------|----------|-------|
| Podman Machine ne démarre pas | Redémarrer WSL : `wsl --shutdown` | [01_PODMAN_INSTALL.md](01_PODMAN_INSTALL.md#71-podman-machine-ne-démarre-pas) |
| Port 8080 déjà utilisé | Changer le port ou arrêter le processus | [01_PODMAN_INSTALL.md](01_PODMAN_INSTALL.md#73-port-déjà-utilisé) |
| Code PHP affiché au lieu d'exécuté | Vérifier modules proxy Apache | [02_CONTAINER_APACHE_INSTALL.md](02_CONTAINER_APACHE_INSTALL.md#9-dépannage) |
| Erreur 504 Gateway Timeout | Attendre 30s ou redémarrer PHP | [03_CONTAINER_PHP_INSTALL.md](03_CONTAINER_PHP_INSTALL.md#10-dépannage) |
| Connexion PostgreSQL refusée | Vérifier healthcheck : `podman ps` | [04_CONTAINER_POSTGRESQL_INSTALL.md](04_CONTAINER_POSTGRESQL_INSTALL.md#10-dépannage) |
| Volume Windows ne monte pas | Utiliser syntaxe `C:\\Users\\...` | [01_PODMAN_INSTALL.md](01_PODMAN_INSTALL.md#95-démarrage-des-conteneurs) |
| Installation Drupal bloquée | Vérifier connexion BDD | [05_DRUPAL_INSTALLATION.md](05_DRUPAL_INSTALLATION.md#9-dépannage-drupal) |
| Drupal lent | Activer OPcache et APCu | [03_CONTAINER_PHP_INSTALL.md](03_CONTAINER_PHP_INSTALL.md#8-optimisation-php) |

---

## 📚 Glossaire rapide

| Terme | Définition |
|-------|------------|
| **Podman** | Alternative à Docker pour gérer des conteneurs |
| **WSL2** | Windows Subsystem for Linux version 2 |
| **Podman Machine** | Machine virtuelle Linux pour exécuter Podman sur Windows |
| **Apache** | Serveur web qui reçoit les requêtes HTTP |
| **PHP-FPM** | FastCGI Process Manager - exécute le code PHP |
| **PostgreSQL** | Système de gestion de base de données |
| **Volume** | Stockage persistant pour les données |
| **Réseau** | Permet aux conteneurs de communiquer entre eux |
| **Image** | Modèle pour créer un conteneur |
| **Conteneur** | Instance en cours d'exécution d'une image |
| **Dockerfile** | Fichier de recette pour construire une image |
| **Healthcheck** | Vérification automatique qu'un conteneur fonctionne |
| **Drupal** | CMS (Content Management System) PHP |
| **Composer** | Gestionnaire de dépendances PHP |
| **Drush** | Outil en ligne de commande pour Drupal |

---

## 🎓 Niveaux de compétence

### Débutant
- Aucune connaissance de Podman/Docker
- Première installation
- **Temps de lecture** : 2 heures
- **Guides à lire** : Tous dans l'ordre

### Intermédiaire
- Connaît les concepts de conteneurs
- A déjà utilisé Docker/Podman
- **Temps de lecture** : 30-45 minutes
- **Guides à lire** : Section 9 de PODMAN_INSTALL + survol des autres

### Avancé
- Maîtrise Podman et conteneurs
- Cherche des solutions spécifiques
- **Temps de lecture** : 5-10 minutes par problème
- **Guides à lire** : Sections Dépannage uniquement

---

## 💡 Conseils de lecture

1. **Ne sautez pas les prérequis** - Ils sont là pour une raison
2. **Testez au fur et à mesure** - Vérifiez chaque étape avant de continuer
3. **Gardez un terminal ouvert** - Pour tester les commandes immédiatement
4. **Bookmarkez les sections Dépannage** - Vous en aurez besoin
5. **Lisez les Notes/Avertissements** - Ils évitent les erreurs courantes

---

## 🔗 Voir aussi

- [README.md](../README.md) - Vue d'ensemble du projet
- [CHANGELOG.md](../CHANGELOG.md) - Historique des modifications
- [Makefile](../Makefile) - Commandes automatisées

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2
