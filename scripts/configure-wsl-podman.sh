#!/bin/bash

################################################################################
# Script de configuration WSL2 et Podman Machine pour Drupal 11
# Optimise les ressources pour accélérer l'installation Composer
################################################################################

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo ""
echo "=========================================="
echo "  Configuration WSL2 et Podman Machine"
echo "=========================================="
echo ""

# Copier le fichier .wslconfig
WSLCONFIG_SOURCE="$(dirname "$0")/../config/.wslconfig"
# Détecter le nom d'utilisateur Windows
WIN_USER=$(cmd.exe /c "echo %USERNAME%" 2>/dev/null | tr -d '\r')
WSLCONFIG_DEST="/mnt/c/Users/${WIN_USER}/.wslconfig"

log_info "Copie de .wslconfig vers $WSLCONFIG_DEST..."
cp "$WSLCONFIG_SOURCE" "$WSLCONFIG_DEST"

if [ $? -eq 0 ]; then
    log_success "Fichier .wslconfig copié avec succès"
else
    log_error "Erreur lors de la copie de .wslconfig"
    exit 1
fi

echo ""
echo "Configuration WSL2 appliquée:"
echo "  - Memory: 6GB"
echo "  - Processors: 4"
echo "  - Swap: 2GB"
echo ""

# Redémarrer WSL2
log_warning "Redémarrage de WSL2 pour appliquer les changements..."
cmd.exe /c "wsl --shutdown" 2>/dev/null

if [ $? -eq 0 ]; then
    log_success "WSL2 arrêté avec succès"
else
    log_warning "WSL2 n'était pas en cours d'exécution"
fi

log_info "Attente de 5 secondes..."
sleep 5

# Arrêter Podman Machine
echo ""
log_info "Arrêt de Podman Machine..."
podman machine stop 2>/dev/null

if [ $? -eq 0 ]; then
    log_success "Podman Machine arrêtée"
else
    log_warning "Podman Machine n'était pas en cours d'exécution"
fi

# Note: Podman Machine sous WSL utilise les ressources définies dans .wslconfig
# La commande 'podman machine set' n'est pas supportée pour les machines WSL
echo ""
log_info "Podman Machine utilisera les ressources WSL2 définies dans .wslconfig"

# Démarrer Podman Machine
echo ""
log_info "Démarrage de Podman Machine..."
podman machine start

if [ $? -eq 0 ]; then
    log_success "Podman Machine démarrée"
else
    log_error "Erreur lors du démarrage de Podman Machine"
    exit 1
fi

echo ""
log_info "Attente du démarrage complet (15 secondes)..."
sleep 15

echo ""
log_success "=========================================="
log_success "  Configuration terminée avec succès!"
log_success "=========================================="
echo ""
echo "WSL2:"
echo "  - 6GB RAM"
echo "  - 4 CPUs"
echo "  - 2GB Swap"
echo ""
echo "Podman Machine:"
echo "  - Utilise les ressources WSL2"
echo "  - 6GB RAM (partagé avec WSL2)"
echo "  - 4 CPUs (partagé avec WSL2)"
echo ""
echo "Vous pouvez maintenant lancer ./scripts/SETUP_FULL.sh"
echo ""
