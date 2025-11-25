# Podman Drupal 11 Development Environment

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Environnement de développement complet pour Drupal 11 utilisant Podman, Apache, PHP-FPM et PostgreSQL 16.

## 🚀 Démarrage rapide

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

## 📋 Caractéristiques

- ✅ **Apache 2.4** avec mod_rewrite, mod_proxy, mod_proxy_fcgi
- ✅ **PHP 8.3-FPM** avec toutes les extensions Drupal
- ✅ **PostgreSQL 16** (requis par Drupal 11)
- ✅ **Clean URLs** activées par défaut
- ✅ **Modules de performance** : headers, expires, deflate
- ✅ **Scripts d'automatisation** Bash et PowerShell
- ✅ **Gestion des modules Apache** simplifiée
- ✅ **Initialisation automatique** de Podman/WSL sur Windows
- ✅ **Documentation complète** en français

## 🛠️ Stack technique

| Service | Image/Version | Port |
|---------|--------------|------|
| Apache | httpd:2.4 | 8080 |
| PHP-FPM | php:8.3-fpm | 9000 |
| PostgreSQL | postgres:16 | 5432 |

## 📚 Documentation

- [Guide complet d'installation](docs/PODMAN_DRUPAL11_SETUP.md)
- [CHANGELOG](CHANGELOG.md)

## ⚡ Commandes principales

### Démarrage
```bash
# Bash (Linux/WSL)
./scripts/start-containers.sh          # Démarrage normal
./scripts/start-containers.sh --rebuild # Avec reconstruction

# PowerShell (Windows)
.\scripts\start-containers.ps1
.\scripts\start-containers.ps1 --rebuild

# Makefile
make start
```

### Gestion des modules Apache
```bash
# Afficher les modules actifs
./scripts/manage-apache-modules.sh status

# Activer des modules
./scripts/manage-apache-modules.sh enable headers expires deflate

# Activer et redémarrer
./scripts/manage-apache-modules.sh enable ssl --restart

# Via Makefile
make apache-modules-status
make apache-modules-enable
```

### Installation Drupal
```bash
# Script automatique
./scripts/drupal-install.sh

# Ou manuellement
podman exec -it php bash -lc "COMPOSER_MEMORY_LIMIT=-1 composer create-project drupal/recommended-project:^11 /var/www/html --no-interaction"
podman exec -it php bash -lc "chown -R www-data:www-data /var/www/html"
```

### Arrêt
```bash
podman compose -f podman-compose.yml down
# ou
make stop
```

## 🔧 Configuration base de données

Lors de l'installation Drupal via l'interface web :

- **Type** : PostgreSQL
- **Nom de la base** : `drupal`
- **Utilisateur** : `drupal`
- **Mot de passe** : `drupal`
- **Hôte** : `db`
- **Port** : `5432`

## ⚠️ Modules Apache requis

**OBLIGATOIRES** (sans eux, PHP ne fonctionne pas) :
- `proxy` — Proxy inverse
- `proxy_fcgi` — Interface FastCGI
- `rewrite` — Clean URLs

**RECOMMANDÉS** :
- `headers` — En-têtes HTTP
- `expires` — Cache navigateur
- `deflate` — Compression gzip

## 🐛 Dépannage

### WSL/Podman ne démarre pas
```bash
wsl --shutdown
podman machine start
```

### PHP renvoie du code source
Vérifier que les modules proxy sont activés :
```bash
./scripts/manage-apache-modules.sh status
```

### Problème de permissions
```bash
make drupal-fix-perms
```

## 📝 Structure du projet

```
podman-drupal11/
├── docker/
│   ├── apache/          # Dockerfile et vhost Apache
│   └── php/             # Dockerfile et php.ini
├── docs/                # Documentation complète
├── scripts/             # Scripts d'automatisation
│   ├── start-containers.sh/ps1
│   ├── manage-apache-modules.sh/ps1
│   └── init-podman.sh/ps1
├── src/                 # Code Drupal (ignoré par Git)
├── podman-compose.yml   # Stack Podman
├── Makefile             # Raccourcis de commandes
└── README.md
```

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📄 Licence

MIT License - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👤 Auteur

**asahraoui.ia**
- GitHub: [@asahraouiia](https://github.com/asahraouiia)
- Email: asahraoui.ia@gmail.com

## 🔗 Liens utiles

- [Drupal 11 Documentation](https://www.drupal.org/docs)
- [Podman Documentation](https://docs.podman.io/)
- [PHP-FPM Documentation](https://www.php.net/manual/en/install.fpm.php)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
