# Environnement Drupal 11 avec Podman

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Environnement de développement Drupal 11 sous Podman avec Apache, PHP 8.3-FPM et PostgreSQL 16.

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

**Pour l'installation détaillée, la configuration et le dépannage, consultez :**

👉 **[GUIDE D'INSTALLATION COMPLET](docs/INSTALLATION.md)** 👈

Ce guide couvre :
- Prérequis et installation de Podman/WSL2
- Structure du projet
- Configuration des services (Apache, PHP, PostgreSQL)
- Commandes d'administration
- Tests de vérification
- Dépannage des problèmes courants
- Bonnes pratiques de sécurité

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

👉 **Pour plus de solutions : [Guide de Dépannage](docs/INSTALLATION.md#7-dépannage)**

## 📁 Structure

```
podman/
├── docker/                   # Dockerfiles personnalisés
│   ├── apache/              # Configuration Apache
│   └── php/                 # Configuration PHP-FPM
├── scripts/                 # Scripts d'automatisation
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
