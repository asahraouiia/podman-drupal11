# Environnement Drupal 11 avec Podman

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Environnement de développement Drupal 11 sous Podman avec Apache, PHP 8.3-FPM et PostgreSQL 16.

## 📖 Guide de Lecture

👉 **[00 - GUIDE DE LECTURE COMPLET](docs/00_GUIDE_LECTURE.md)** 👈 - Commencez ici !

**Pour les débutants** (première installation) :
1. 📘 [01 - INSTALLATION PODMAN](docs/01_PODMAN_INSTALL.md) - Installer WSL2 et Podman (30-45 min)
2. 📗 [02 - CONTENEUR APACHE](docs/02_CONTAINER_APACHE_INSTALL.md) - Comprendre la configuration Apache
3. 📙 [03 - CONTENEUR PHP-FPM](docs/03_CONTAINER_PHP_INSTALL.md) - Comprendre la configuration PHP
4. 📕 [04 - CONTENEUR POSTGRESQL](docs/04_CONTAINER_POSTGRESQL_INSTALL.md) - Comprendre la base de données
5. 📔 [05 - INSTALLATION DRUPAL](docs/05_DRUPAL_INSTALLATION.md) - Installer et configurer Drupal

**Pour les utilisateurs avancés** (environnement déjà installé) :
- 🔧 [06 - CLEANUP SCRIPT](docs/06_CLEANUP_SCRIPT.md) - Nettoyer l'environnement
- 📚 Consulter directement les sections spécifiques des guides ci-dessus
- 🎯 Utiliser les [Commandes Principales](#🛠️-commandes-principales)

**En cas de problème** :
- 🚨 Section **Dépannage** de chaque guide
- 💡 [Dépannage Rapide](#🐛-dépannage-rapide) ci-dessous

## ⚡ Démarrage Rapide

### Windows (WSL2 requis)

```powershell
# 1. Initialiser Podman et WSL2
.\scripts\start-containers.ps1

# 2. Accéder à Drupal
# http://localhost:8080
```

### Linux/macOS

```bash
# 1. Initialiser Podman
./scripts/start-containers.sh

# 2. Accéder à Drupal
# http://localhost:8080
```

## 📚 Documentation Complète

### 📖 Parcours d'apprentissage recommandé

| Étape | Guide | Temps estimé | Objectif |
|-------|-------|--------------|----------|
| **1** | [01 - INSTALLATION PODMAN](docs/01_PODMAN_INSTALL.md) | 30-45 min | Installer WSL2, Podman Desktop, créer la machine virtuelle |
| **2** | [02 - CONTENEUR APACHE](docs/02_CONTAINER_APACHE_INSTALL.md) | 15-20 min | Comprendre le serveur web et le proxy FastCGI |
| **3** | [03 - CONTENEUR PHP-FPM](docs/03_CONTAINER_PHP_INSTALL.md) | 15-20 min | Comprendre PHP, extensions et Composer |
| **4** | [04 - CONTENEUR POSTGRESQL](docs/04_CONTAINER_POSTGRESQL_INSTALL.md) | 15-20 min | Comprendre la base de données et volumes |
| **5** | [05 - INSTALLATION DRUPAL](docs/05_DRUPAL_INSTALLATION.md) | 20-30 min | Installer et configurer Drupal 11 |

**Total : ~2 heures pour une installation complète de zéro**

### 🎯 Guides par thématique

**Installation et configuration**

👉 **[01 - INSTALLATION PODMAN](docs/01_PODMAN_INSTALL.md)** 👈
- Installation de WSL2 et Podman Desktop sur Windows 11
- Configuration initiale et gestion de Podman Machine
- Volumes, réseaux et commandes de base
- **✅ Procédure complète validée** - Workflow testé étape par étape
- Dépannage Podman et bonnes pratiques

### Configuration des conteneurs

👉 **[02 - CONTENEUR APACHE](docs/02_CONTAINER_APACHE_INSTALL.md)** 👈
- Dockerfile et configuration VirtualHost
- Modules Apache (proxy, fcgi, rewrite)
- Gestion du conteneur et logs
- Dépannage et optimisation Apache

👉 **[03 - CONTENEUR PHP-FPM](docs/03_CONTAINER_PHP_INSTALL.md)** 👈
- Dockerfile et configuration PHP (php.ini)
- Extensions PHP pour Drupal
- Composer et gestion des dépendances
- Dépannage et optimisation PHP

👉 **[04 - CONTENEUR POSTGRESQL](docs/04_CONTAINER_POSTGRESQL_INSTALL.md)** 👈
- Configuration PostgreSQL 16
- Variables d'environnement et volumes
- Connexion, sauvegarde et restauration
- Dépannage et optimisation PostgreSQL

### Installation Drupal

👉 **[05 - INSTALLATION DRUPAL](docs/05_DRUPAL_INSTALLATION.md)** 👈
- Installation de Drupal 11
- Configuration de la base de données
- Gestion des modules et thèmes
- Mise à jour Drupal et modules
- Drush et outils de développement
- Dépannage Drupal

### 🧹 Maintenance

👉 **[06 - SCRIPT DE NETTOYAGE](docs/06_CLEANUP_SCRIPT.md)** 👈
- Suppression complète de l'environnement
- Commandes de nettoyage manuel
- Récupération d'espace disque
- Repartir de zéro proprement

---

## 🛠️ Commandes Principales

```bash
# Démarrer les conteneurs
make start                    # ou: podman compose up -d

# Arrêter les conteneurs
make stop                     # ou: ./scripts/stop-containers.sh

# Vérifier la santé des conteneurs
make health-check             # ou: ./scripts/health-check.sh

# Collecter les logs
make logs-collect             # ou: ./scripts/logs-collect.sh

# Voir les logs en temps réel
make logs                     # ou: podman logs -f web

# Gérer les modules Apache
make apache-modules-status    # Voir les modules actifs
make apache-modules-enable MODULE=ssl  # Activer un module
```

## 🔧 Configuration

### Services Exposés

| Service | Port  | Accès                |
|---------|-------|----------------------|
| Apache  | 8080  | http://localhost:8080 |
| PHP-FPM | 9000  | Interne uniquement    |
| PostgreSQL | 5432 | Interne uniquement |

### Base de Données (Développement)

- **Utilisateur** : `drupal`
- **Mot de passe** : `drupal`
- **Base** : `drupal`
- **Version** : PostgreSQL 16.11

⚠️ **Production** : Changez impérativement ces identifiants !

## 🐛 Dépannage Rapide

**Problème** : Les conteneurs ne démarrent pas
```bash
podman compose down
podman compose up -d --force-recreate
```

**Problème** : PHP affiche le code source au lieu de l'exécuter
```bash
# Vérifier les modules Apache
make apache-modules-status
# Les modules proxy et proxy_fcgi doivent être activés
```

**Problème** : Erreur de connexion PostgreSQL
```bash
# Vérifier que PostgreSQL 16 est bien utilisé
podman exec db psql -U drupal -c "SELECT version();"
```

👉 **Pour plus de solutions :**
- [Dépannage Podman](docs/01_PODMAN_INSTALL.md#7-dépannage)
- [Dépannage Apache](docs/02_CONTAINER_APACHE_INSTALL.md#9-dépannage)
- [Dépannage PHP](docs/03_CONTAINER_PHP_INSTALL.md#10-dépannage)
- [Dépannage PostgreSQL](docs/04_CONTAINER_POSTGRESQL_INSTALL.md#10-dépannage)
- [Dépannage Drupal](docs/05_DRUPAL_INSTALLATION.md#9-dépannage-drupal)

## 📁 Structure

```
podman/
├── docker/                   # Dockerfiles personnalisés
│   ├── apache/              # Configuration Apache
│   └── php/                 # Configuration PHP-FPM
├── scripts/                 # Scripts d'automatisation
│   ├── cleanup.sh           # ⚠️ Nettoyage complet (supprime tout)
│   ├── start-containers.*   # Démarrage complet
│   ├── stop-containers.*    # Arrêt propre
│   ├── health-check.*       # Vérification santé
│   ├── logs-collect.*       # Collecte des logs
│   └── manage-apache-modules.* # Gestion modules Apache
├── src/                     # Code source Drupal
├── logs/                    # Logs des conteneurs
├── docs/                    # Documentation
└── podman-compose.yml       # Orchestration des services
```

## 📝 Versions

- **Drupal** : 11.x
- **PHP** : 8.3-FPM
- **Apache** : 2.4
- **PostgreSQL** : 16.11
- **Podman** : 4.x avec plugin compose

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📄 Licence

MIT License - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👤 Auteur

**asahraoui.ia**
- GitHub: [@asahraouiia](https://github.com/asahraouiia)
- Email: asahraoui.ia@gmail.com

## 🔗 Ressources

- [Documentation Drupal 11](https://www.drupal.org/docs/understanding-drupal/drupal-11)
- [Documentation Podman](https://docs.podman.io/)
- [PHP 8.3 Documentation](https://www.php.net/releases/8.3/)
- [PostgreSQL 16 Documentation](https://www.postgresql.org/docs/16/)

---

**Auteur** : Abdellah Sahraoui  
**Date** : Janvier 2025  
**Version** : 0.2
