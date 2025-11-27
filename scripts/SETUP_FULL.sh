#!/bin/bash

################################################################################
# Script d'installation complète de l'environnement Drupal 11 avec Podman
# Ce script permet de réinitialiser complètement le projet ou de démarrer
# les containers existants
################################################################################

set -e  # Arrêter en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Première question : Voulez-vous réinitialiser ?
echo ""
echo "=========================================="
echo "  Installation Drupal 11 avec Podman"
echo "=========================================="
echo ""
log_warning "Voulez-vous réinitialiser complètement l'environnement ?"
echo "  (supprime tous les containers, images, volumes et réseaux)"
echo ""
read -p "Réinitialiser ? (oui/non): " reinit_choice

if [ "$reinit_choice" == "oui" ]; then
    log_info "Démarrage de la réinitialisation complète..."
    
    # Optimiser WSL2 et Podman Machine pour l'installation
    log_info "Optimisation des ressources WSL2 et Podman Machine..."
    
    # Créer/Mettre à jour .wslconfig pour optimiser WSL2
    WSLCONFIG_PATH="/c/Users/abdel/.wslconfig"
    log_info "Configuration WSL2 dans $WSLCONFIG_PATH..."
    
    cat > "$WSLCONFIG_PATH" << 'EOF'
[wsl2]
# Allouer plus de ressources pour l'installation Drupal
memory=6GB
processors=4
swap=2GB
localhostForwarding=true

# Optimiser les performances I/O
[experimental]
autoMemoryReclaim=gradual
sparseVhd=true
EOF
    
    log_success "Fichier .wslconfig créé/mis à jour"
    log_warning "Redémarrage de WSL2 pour appliquer les changements..."
    
    # Redémarrer WSL2
    cmd.exe /c "wsl --shutdown" 2>/dev/null || true
    sleep 5
    
    log_info "Redémarrage de Podman Machine avec ressources optimisées..."
    podman machine stop 2>/dev/null || log_warning "Podman Machine déjà arrêtée"
    
    # Note: Podman Machine sous WSL utilise les ressources définies dans .wslconfig
    # La commande 'podman machine set' n'est pas supportée
    log_info "Podman Machine utilisera les ressources WSL2 (6GB RAM, 4 CPUs)"
    
    podman machine start
    
    # Attendre que Podman soit prêt
    log_info "Attente du démarrage de Podman (15 secondes)..."
    sleep 15
    
    log_success "WSL2 et Podman Machine optimisés (WSL2: 6GB RAM, 4 CPUs, 2GB swap)"
    
    # Arrêter tous les containers en cours
    log_info "Arrêt des containers..."
    podman stop $(podman ps -aq) 2>/dev/null || log_warning "Aucun container à arrêter"
    
    # Supprimer tous les containers
    log_info "Suppression des containers..."
    podman rm $(podman ps -aq) 2>/dev/null || log_warning "Aucun container à supprimer"
    
    # Supprimer toutes les images
    log_info "Suppression des images..."
    podman rmi $(podman images -aq) 2>/dev/null || log_warning "Aucune image à supprimer"
    
    # Supprimer tous les volumes
    log_info "Suppression des volumes..."
    podman volume rm $(podman volume ls -q) 2>/dev/null || log_warning "Aucun volume à supprimer"
    
    # Supprimer tous les réseaux personnalisés
    log_info "Suppression des réseaux..."
    podman network rm $(podman network ls -q) 2>/dev/null || log_warning "Aucun réseau à supprimer"
    
    # Nettoyage système complet
    log_info "Nettoyage système..."
    podman system prune -af --volumes
    
    # Nettoyer le dossier src (garder composer.json et composer.lock)
    log_info "Nettoyage du dossier src (conservation de composer.json et composer.lock)..."
    find src -mindepth 1 ! -name 'composer.json' ! -name 'composer.lock' ! -name '.editorconfig' ! -name '.gitattributes' -exec rm -rf {} + 2>/dev/null || true
    
    log_success "Nettoyage terminé!"
    
    # Créer le réseau drupal_net
    log_info "Création du réseau drupal_net..."
    podman network create drupal_net
    
    # Créer le volume pour PostgreSQL
    log_info "Création du volume drupal_db_data..."
    podman volume create drupal_db_data
    
    # Build de l'image Apache
    log_info "Construction de l'image Apache (myapache:latest)..."
    podman build -t myapache:latest -f docker/apache/Dockerfile docker/apache/
    log_success "Image Apache construite avec succès"
    
    # Build de l'image PHP
    log_info "Construction de l'image PHP (myphp:8.3-dev)..."
    podman build -t myphp:8.3-dev -f docker/php/Dockerfile docker/php/
    log_success "Image PHP construite avec succès"
    
    # Démarrer le container PostgreSQL
    log_info "Démarrage du container PostgreSQL..."
    podman run -d \
        --name db \
        --network drupal_net \
        -e POSTGRES_DB=drupal \
        -e POSTGRES_USER=drupal \
        -e POSTGRES_PASSWORD=drupal \
        -v drupal_db_data:/var/lib/postgresql/data \
        --health-cmd="pg_isready -U drupal" \
        --health-interval=10s \
        --health-timeout=5s \
        --health-retries=5 \
        postgres:16
    
    # Attendre que PostgreSQL soit prêt
    log_info "Attente du démarrage de PostgreSQL (20 secondes)..."
    sleep 20
    
    # Vérifier le statut de PostgreSQL
    DB_STATUS=$(podman inspect --format='{{.State.Health.Status}}' db)
    if [ "$DB_STATUS" == "healthy" ]; then
        log_success "PostgreSQL est prêt"
    else
        log_warning "PostgreSQL n'est pas encore healthy, mais on continue..."
    fi
    
    # Obtenir le chemin absolu du dossier src (compatible Git Bash Windows)
    # Remplacer /c/ par C:/ et échapper les backslashes pour Podman
    CURRENT_DIR=$(pwd | sed 's|^/\([a-z]\)/|\U\1:/|' | sed 's|/|\\|g')
    SRC_PATH="${CURRENT_DIR}\\src"
    
    # Démarrer le container PHP
    log_info "Démarrage du container PHP-FPM..."
    podman run -d \
        --name php \
        --network drupal_net \
        -v "${SRC_PATH}:/var/www/html" \
        myphp:8.3-dev
    log_success "Container PHP démarré"
    
    # Démarrer le container Apache
    log_info "Démarrage du container Apache..."
    podman run -d \
        --name web \
        --network drupal_net \
        -p 8080:80 \
        -v "${SRC_PATH}:/var/www/html" \
        myapache:latest
    log_success "Container Apache démarré"
    
    # Vérifier que les 3 containers sont en cours d'exécution
    log_info "Vérification des containers..."
    podman ps
    
    # Installation de Drupal via Composer
    log_info "Installation de Drupal via Composer (peut prendre 10-20 minutes)..."
    log_warning "L'extraction de drupal/core est lente dans WSL2 (~5-10 minutes)..."
    
    # Utiliser --prefer-dist avec un timeout très long pour WSL2
    # Note: L'extraction du zip drupal/core (~40MB → ~200MB) est le goulot d'étranglement
    podman exec php sh -c 'cd /var/www/html && COMPOSER_PROCESS_TIMEOUT=2400 composer install --no-progress --prefer-dist --optimize-autoloader'
    
    COMPOSER_EXIT=$?
    
    if [ $COMPOSER_EXIT -eq 0 ]; then
        log_success "Installation Composer terminée avec succès"
    else
        log_error "Erreur lors de l'installation Composer (code: $COMPOSER_EXIT)"
        log_warning "Si le timeout persiste, augmentez la RAM de Podman Machine:"
        log_warning "  podman machine stop"
        log_warning "  podman machine set --memory 4096"
        log_warning "  podman machine start"
        exit 1
    fi
    
    # Configuration des permissions pour Drupal
    log_info "Configuration des permissions..."
    
    # Copier le fichier settings.php
    podman exec php sh -c 'cp /var/www/html/web/sites/default/default.settings.php /var/www/html/web/sites/default/settings.php'
    podman exec php sh -c 'chmod 666 /var/www/html/web/sites/default/settings.php'
    
    # Permissions sur le dossier sites/default
    podman exec php sh -c 'chmod 777 /var/www/html/web/sites/default'
    
    # Créer et configurer le dossier files
    podman exec php sh -c 'mkdir -p /var/www/html/web/sites/default/files'
    podman exec php sh -c 'chmod -R 777 /var/www/html/web/sites/default/files'
    
    log_success "Permissions configurées"
    
    # Test de l'accès HTTP
    log_info "Test de l'accès HTTP..."
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
    
    if [ "$HTTP_STATUS" == "200" ] || [ "$HTTP_STATUS" == "302" ]; then
        log_success "Le site est accessible sur http://localhost:8080"
    else
        log_warning "Le site répond avec le code HTTP: $HTTP_STATUS"
    fi
    
    echo ""
    log_success "=========================================="
    log_success "  Installation terminée avec succès!"
    log_success "=========================================="
    echo ""
    echo "Accédez à l'installateur Drupal: http://localhost:8080"
    echo ""
    echo "Configuration de la base de données:"
    echo "  - Type: PostgreSQL"
    echo "  - Nom de la base: drupal"
    echo "  - Utilisateur: drupal"
    echo "  - Mot de passe: drupal"
    echo "  - Hôte (options avancées): db"
    echo "  - Port: 5432"
    echo ""

else
    log_info "Réinitialisation annulée. Démarrage des containers existants..."
    
    # Vérifier si les containers existent
    if ! podman ps -a | grep -q "db\|php\|web"; then
        log_error "Aucun container trouvé. Relancez le script et choisissez 'oui' pour une installation complète."
        exit 1
    fi
    
    # Démarrer les containers
    log_info "Démarrage du container db..."
    podman start db 2>/dev/null || log_warning "Container db déjà démarré ou non trouvé"
    
    log_info "Démarrage du container php..."
    podman start php 2>/dev/null || log_warning "Container php déjà démarré ou non trouvé"
    
    log_info "Démarrage du container web..."
    podman start web 2>/dev/null || log_warning "Container web déjà démarré ou non trouvé"
    
    # Attendre un peu pour que les containers démarrent
    sleep 5
    
    # Afficher le statut
    log_info "Statut des containers:"
    podman ps
    
    # Test de l'accès HTTP
    log_info "Test de l'accès HTTP..."
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
    
    if [ "$HTTP_STATUS" == "200" ] || [ "$HTTP_STATUS" == "302" ]; then
        log_success "Le site est accessible sur http://localhost:8080"
    else
        log_warning "Le site répond avec le code HTTP: $HTTP_STATUS"
    fi
    
    log_success "Containers démarrés avec succès!"
fi
