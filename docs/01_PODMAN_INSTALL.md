# Guide d'installation Podman - Windows 11 / WSL2

**Dernière mise à jour** : 2025-11-25  
**Dépôt GitHub** : [https://github.com/asahraouiia/podman-drupal11](https://github.com/asahraouiia/podman-drupal11)

---

## Table des matières

1. [Prérequis système](#1-prérequis-système)
2. [Installation de WSL2](#2-installation-de-wsl2)
3. [Installation de Podman Desktop](#3-installation-de-podman-desktop)
4. [Configuration initiale](#4-configuration-initiale)
5. [Gestion de Podman Machine](#5-gestion-de-podman-machine)
6. [Volumes et réseaux](#6-volumes-et-réseaux)
7. [Dépannage](#7-dépannage)
8. [Bonnes pratiques](#8-bonnes-pratiques)

---

## 1. Prérequis système

### 1.1. Système d'exploitation

**Windows 11 Home** avec WSL2 (Windows Subsystem for Linux version 2).

### 1.2. Configuration minimale

- **Processeur** : 64-bit avec virtualisation activée
- **Mémoire** : 4 GB RAM minimum (8 GB recommandé)
- **Espace disque** : 20 GB disponibles
- **Virtualisation** : Activée dans le BIOS

### 1.3. Logiciels requis

- **Podman Desktop** 4.x ou supérieur
- **WSL2** avec distribution Ubuntu
- **Git** pour cloner le projet
- **Terminal** : Git Bash ou PowerShell

---

## 2. Installation de WSL2

### 2.1. Vérifier si WSL2 est installé

```bash
wsl --status
```

Si la commande retourne une erreur, WSL n'est pas installé.

### 2.2. Installer WSL2 et Ubuntu

```powershell
# Ouvrir PowerShell en tant qu'administrateur
wsl --install

# Installer Ubuntu (recommandé)
wsl --install -d Ubuntu

# Redémarrer l'ordinateur
```

**Durée estimée** : 10-15 minutes (téléchargement inclus)

### 2.3. Configurer Ubuntu

Après le redémarrage :

1. Ubuntu s'ouvrira automatiquement
2. Créer un nom d'utilisateur (ex: `drupaldev`)
3. Créer un mot de passe

**Conseil** : Notez bien votre mot de passe, il sera nécessaire pour `sudo`.

### 2.4. Vérifier la version WSL

```bash
wsl --list --verbose
```

**Sortie attendue** :

```
  NAME      STATE           VERSION
* Ubuntu    Running         2
```

Assurez-vous que la colonne VERSION indique `2`.

Si la distribution est en version 1 :

```powershell
wsl --set-version Ubuntu 2
```

### 2.5. Activer la virtualisation (si nécessaire)

Si vous rencontrez des erreurs, vérifiez que la virtualisation est activée :

```powershell
# Vérifier dans PowerShell
Get-ComputerInfo -Property "HyperV*"
```

Si désactivée, redémarrer et entrer dans le BIOS :
1. Redémarrer le PC
2. Appuyer sur F2/F10/DEL (selon le fabricant)
3. Chercher "Virtualization Technology" ou "VT-x/AMD-V"
4. Activer l'option
5. Sauvegarder et redémarrer

### 2.6. Mettre à jour Ubuntu

```bash
# Se connecter à Ubuntu
wsl

# Mettre à jour les paquets
sudo apt update && sudo apt upgrade -y
```

---

## 3. Installation de Podman Desktop

### 3.1. Télécharger Podman Desktop

1. Aller sur [https://podman-desktop.io/downloads](https://podman-desktop.io/downloads)
2. Télécharger la version **Windows** (fichier `.exe`)
3. Exécuter l'installateur

**Taille du téléchargement** : ~150 MB

### 3.2. Installer Podman Desktop

1. Lancer l'installateur téléchargé
2. Accepter la licence
3. Choisir le répertoire d'installation (par défaut : `C:\Program Files\Podman Desktop`)
4. Cliquer sur **Install**
5. Attendre la fin de l'installation

**Durée** : 2-3 minutes

### 3.3. Initialiser Podman Machine

Après installation :

1. Ouvrir **Podman Desktop**
2. Au premier lancement, une fenêtre demande d'initialiser la machine
3. Cliquer sur **Initialize and Start**
4. Attendre la création de la machine Podman (2-3 minutes)

**Ce qui est créé** :
- Machine virtuelle WSL nommée `podman-machine-default`
- Configuration réseau pour les conteneurs
- Socket Podman pour les commandes CLI

### 3.4. Vérifier l'installation (CLI)

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

### 3.5. Test de fonctionnement

```bash
# Tester Podman avec une image simple
podman run --rm hello-world

# Sortie attendue:
# Hello from Docker!
# This message shows that your installation appears to be working correctly.
```

**Que fait cette commande ?**
- Télécharge l'image `hello-world`
- Crée un conteneur
- Exécute le conteneur
- Affiche le message de succès
- Supprime le conteneur (`--rm`)

Si le test échoue, passer à la section [Dépannage](#7-dépannage).

---

## 4. Configuration initiale

### 4.1. Cloner le projet Drupal

```bash
# Créer un dossier pour vos projets
mkdir -p ~/projects
cd ~/projects

# Cloner le dépôt
git clone https://github.com/asahraouiia/podman-drupal11.git
cd podman-drupal11
```

### 4.2. Structure du projet

```
podman-drupal11/
├── docker/                  # Dockerfiles personnalisés
│   ├── apache/
│   └── php/
├── src/                     # Code Drupal
├── logs/                    # Logs des conteneurs
├── scripts/                 # Scripts d'automatisation
├── docs/                    # Documentation
└── podman-compose.yml       # Orchestration
```

### 4.3. Créer les dossiers de logs

```bash
# Créer la structure de logs
mkdir -p logs/{apache,php,postgres}
```

### 4.4. Rendre les scripts exécutables

```bash
# Donner les permissions d'exécution
chmod +x scripts/*.sh
```

---

## 5. Gestion de Podman Machine

### 5.1. Commandes de base

```bash
# Lister les machines
podman machine list

# Démarrer la machine
podman machine start

# Arrêter la machine
podman machine stop

# Redémarrer la machine
podman machine restart

# Supprimer la machine
podman machine rm podman-machine-default
```

### 5.2. Configuration de la machine

```bash
# Voir les détails de la machine
podman machine inspect podman-machine-default

# Modifier les ressources (CPU/RAM)
podman machine set --cpus 4 --memory 4096 podman-machine-default
```

### 5.3. SSH dans la machine

```bash
# Se connecter à la machine Podman
podman machine ssh

# Sortir de la session SSH
exit
```

---

## 6. Volumes et réseaux

### 6.1. Volumes

#### 6.1.1. Lister les volumes

```bash
podman volume ls
```

#### 6.1.2. Créer un volume

```bash
podman volume create mon_volume
```

#### 6.1.3. Inspecter un volume

```bash
podman volume inspect drupal_db_data
```

#### 6.1.4. Supprimer un volume

```bash
# Supprimer un volume spécifique
podman volume rm mon_volume

# Supprimer tous les volumes non utilisés
podman volume prune
```

### 6.2. Réseaux

#### 6.2.1. Lister les réseaux

```bash
podman network ls
```

#### 6.2.2. Créer un réseau

```bash
podman network create mon_reseau
```

#### 6.2.3. Inspecter un réseau

```bash
podman network inspect podman_drupal_net
```

#### 6.2.4. Supprimer un réseau

```bash
# Supprimer un réseau spécifique
podman network rm mon_reseau

# Supprimer tous les réseaux non utilisés
podman network prune
```

---

## 7. Dépannage

### 7.1. Podman Machine ne démarre pas

#### Symptôme

```bash
podman ps
# Error: cannot connect to Podman socket
```

#### Solution 1 : Redémarrer WSL

```bash
# Arrêter WSL complètement
wsl --shutdown

# Attendre 5 secondes
sleep 5

# Démarrer Podman Machine
podman machine start

# Vérifier l'état
podman machine list
```

#### Solution 2 : Recréer la machine

```bash
# Arrêter la machine
podman machine stop

# Supprimer la machine
podman machine rm podman-machine-default

# Recréer et initialiser
podman machine init
podman machine start
```

### 7.2. Erreur de virtualisation

#### Symptôme

```
Error: WSL 2 requires an update to its kernel component
```

#### Solution

1. Télécharger le package de mise à jour WSL2 : [https://aka.ms/wsl2kernel](https://aka.ms/wsl2kernel)
2. Installer le package
3. Redémarrer l'ordinateur
4. Relancer Podman Desktop

### 7.3. Port déjà utilisé

#### Symptôme

```
Error: rootlessport cannot expose privileged port 8080
```

#### Solution 1 : Identifier le processus

```bash
# Windows
netstat -ano | findstr :8080

# Arrêter le processus
taskkill /PID <PID> /F
```

#### Solution 2 : Changer le port

Modifier `podman-compose.yml` :

```yaml
services:
  web:
    ports:
      - "8081:80"  # Au lieu de 8080:80
```

### 7.4. Images ne se téléchargent pas

#### Symptôme

```
Error: error pulling image
```

#### Solution

```bash
# Vérifier la connexion Internet
ping google.com

# Tester l'accès au registre
curl https://registry.hub.docker.com

# Configurer un proxy (si nécessaire)
export HTTP_PROXY=http://proxy.example.com:8080
export HTTPS_PROXY=http://proxy.example.com:8080
```

### 7.5. Permission denied sur volumes

#### Symptôme

```
Permission denied: /var/www/html
```

#### Solution

```bash
# Vérifier les permissions du dossier hôte
ls -la src/

# Donner les permissions appropriées
chmod -R 775 src/
chown -R $(whoami):$(whoami) src/
```

### 7.6. WSL consomme trop de ressources

#### Solution

Créer un fichier `.wslconfig` dans votre dossier utilisateur Windows :

```ini
# C:\Users\<VotreNom>\.wslconfig

[wsl2]
memory=4GB          # Limiter la RAM à 4 GB
processors=2        # Limiter à 2 CPU
swap=2GB            # Swap de 2 GB
```

Redémarrer WSL :

```bash
wsl --shutdown
```

---

## 8. Bonnes pratiques

### 8.1. Sauvegardes

#### 8.1.1. Exporter une machine Podman

```bash
# Exporter la machine (pas encore supporté par Podman Machine)
# Alternative : Sauvegarder les volumes
podman volume export drupal_db_data > drupal_db_volume.tar
```

#### 8.1.2. Sauvegarder les images

```bash
# Sauvegarder une image
podman save -o myapache.tar localhost/myapache:latest

# Restaurer une image
podman load -i myapache.tar
```

### 8.2. Nettoyage régulier

```bash
# Nettoyer les conteneurs arrêtés
podman container prune

# Nettoyer les images non utilisées
podman image prune -a

# Nettoyer les volumes non utilisés
podman volume prune

# Nettoyer tout (conteneurs, images, volumes, réseaux)
podman system prune -a --volumes
```

### 8.3. Monitoring des ressources

```bash
# Voir l'utilisation des ressources
podman stats

# Voir l'espace disque utilisé
podman system df

# Détails sur l'espace disque
podman system df -v
```

### 8.4. Mise à jour de Podman Desktop

1. Ouvrir Podman Desktop
2. Aller dans **Settings** → **About**
3. Cliquer sur **Check for updates**
4. Suivre les instructions pour mettre à jour

### 8.5. Logs de Podman Machine

```bash
# Voir les logs de la machine
podman machine inspect podman-machine-default | grep -A 10 Log

# Logs WSL
wsl --list --verbose
```

### 8.6. Sécurité

#### 8.6.1. Limiter l'accès réseau

Par défaut, Podman utilise des réseaux privés. Pour exposer un service :

```yaml
# Lier uniquement sur localhost
ports:
  - "127.0.0.1:8080:80"
```

#### 8.6.2. Scanner les images pour les vulnérabilités

```bash
# Installer Trivy
podman run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy image myapache:latest
```

---

## 9. Procédure complète validée

Cette section documente la procédure complète testée et validée le 2025-11-25 pour installer l'environnement de zéro.

### 9.1. Nettoyage complet (optionnel)

Si vous repartez de zéro, utilisez le script de nettoyage automatique :

```bash
# Option 1 : Script automatique (recommandé)
./scripts/cleanup.sh
```

Le script demande confirmation et effectue :
- Arrêt de tous les conteneurs
- Suppression de tous les conteneurs
- Suppression de toutes les images
- Suppression de tous les volumes
- Suppression de tous les réseaux personnalisés
- Nettoyage final du système

```bash
# Option 2 : Commande manuelle rapide
podman system prune -a --volumes -f
```

**Résultat attendu** : Récupération de plusieurs GB d'espace disque.

**Voir aussi** : [Documentation du script cleanup.sh](06_CLEANUP_SCRIPT.md)

### 9.2. Création du réseau

```bash
podman network create drupal_net
```

**Sortie attendue** : `drupal_net`

### 9.3. Construction des images

**Image Apache** (~2-5 minutes) :

```bash
cd /c/Users/abdel/OneDrive/Documents/asahraoui_labs/projects/podman
podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache
```

**Taille finale** : ~121 MB

**Image PHP** (~5-10 minutes) :

```bash
podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php
```

**Taille finale** : ~688 MB

### 9.4. Création du volume PostgreSQL

```bash
podman volume create drupal_db_data
```

### 9.5. Démarrage des conteneurs

**⚠️ IMPORTANT - Syntaxe des volumes sous Windows Git Bash** :

Sous Git Bash, vous **devez** utiliser le format Windows natif avec backslashes échappés :

```bash
# ❌ NE FONCTIONNE PAS :
-v ./src:/var/www/html
-v "$PWD"/src:/var/www/html
-v /c/Users/.../src:/var/www/html

# ✅ FONCTIONNE :
-v C:\\Users\\abdel\\OneDrive\\Documents\\asahraoui_labs\\projects\\podman\\src:/var/www/html
```

**PostgreSQL** :

```bash
podman run -d \
  --name db \
  --network drupal_net \
  -v drupal_db_data:/var/lib/postgresql/data \
  -e POSTGRES_USER=drupal \
  -e POSTGRES_PASSWORD=drupal \
  -e POSTGRES_DB=drupal \
  --restart unless-stopped \
  --health-cmd "pg_isready -U drupal" \
  --health-interval 10s \
  --health-timeout 5s \
  --health-retries 5 \
  --health-start-period 30s \
  postgres:16
```

**Attendre que PostgreSQL soit healthy** (15-30 secondes) :

```bash
sleep 15
podman ps
```

Vérifier que STATUS indique `(healthy)`.

**PHP-FPM** :

```bash
podman run -d \
  --name php \
  --network drupal_net \
  -v C:\\Users\\abdel\\OneDrive\\Documents\\asahraoui_labs\\projects\\podman\\src:/var/www/html \
  myphp:8.3-dev
```

**⚠️ Adapter le chemin** : Remplacez `C:\\Users\\abdel\\OneDrive\\Documents\\asahraoui_labs\\projects\\podman` par votre chemin réel.

**Apache** :

```bash
podman run -d \
  --name web \
  --network drupal_net \
  -p 8080:80 \
  -v C:\\Users\\abdel\\OneDrive\\Documents\\asahraoui_labs\\projects\\podman\\src:/var/www/html \
  myapache:latest
```

### 9.6. Vérification

**Lister les conteneurs** :

```bash
podman ps
```

**Sortie attendue** : 3 conteneurs (db, php, web) avec STATUS "Up".

**Tester l'accès HTTP** :

```bash
# Attendre 10 secondes que PHP-FPM soit prêt
sleep 10
curl -I http://localhost:8080
```

**Résultat attendu** :
```
HTTP/1.1 302 Found
...
X-Powered-By: PHP/8.3.28
Location: /core/install.php
```

**Ouvrir dans le navigateur** : http://localhost:8080

### 9.7. Ordre de démarrage et temps d'attente

1. **PostgreSQL** démarre en premier (10-30s pour devenir healthy)
2. **PHP-FPM** démarre rapidement (~5s)
3. **Apache** démarre rapidement mais peut timeout si PHP n'est pas prêt
4. **Premier accès HTTP** peut prendre 10-30s (chargement Composer/Drupal)

**Solution au timeout 504** : Attendre 30 secondes et réessayer.

---

## Aide-mémoire des commandes

| Action | Commande |
|--------|----------|
| **Vérifier version** | `podman --version` |
| **Lister machines** | `podman machine list` |
| **Démarrer machine** | `podman machine start` |
| **Arrêter machine** | `podman machine stop` |
| **Redémarrer WSL** | `wsl --shutdown` |
| **Lister conteneurs** | `podman ps` |
| **Lister volumes** | `podman volume ls` |
| **Lister réseaux** | `podman network ls` |
| **Nettoyer système** | `podman system prune -a` |
| **Stats ressources** | `podman stats` |
| **Espace disque** | `podman system df` |
| **Logs conteneur** | `podman logs -f <nom>` |
| **Exec commande** | `podman exec <nom> <cmd>` |

---

## Ressources

### Documentation officielle

- [Podman Desktop](https://podman-desktop.io/docs)
- [Podman CLI](https://docs.podman.io/)
- [WSL2](https://learn.microsoft.com/en-us/windows/wsl/)

### Support

- [Podman Desktop GitHub](https://github.com/containers/podman-desktop/issues)
- [Podman GitHub](https://github.com/containers/podman/issues)

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2

**Voir aussi** :
- [02 - Configuration Apache](02_CONTAINER_APACHE_INSTALL.md) - Installation et configuration du conteneur Apache
- [03 - Configuration PHP](03_CONTAINER_PHP_INSTALL.md) - Installation et configuration du conteneur PHP-FPM
- [04 - Configuration PostgreSQL](04_CONTAINER_POSTGRESQL_INSTALL.md) - Installation et configuration du conteneur PostgreSQL
- [05 - Installation Drupal](05_DRUPAL_INSTALLATION.md) - Installation et gestion de Drupal
- [00 - Guide de lecture](00_GUIDE_LECTURE.md) - Comment naviguer dans la documentation
- [README.md](../README.md) - Guide de démarrage rapide
