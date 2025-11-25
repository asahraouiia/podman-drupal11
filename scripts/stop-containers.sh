#!/bin/bash
# Script d'arrêt des conteneurs Drupal
# Arrête proprement tous les conteneurs de la stack

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Arrêt des conteneurs Drupal${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

cd "$PROJECT_ROOT"

# Arrêter les conteneurs
echo "Arrêt des conteneurs..."
podman compose -f podman-compose.yml down

echo ""
echo -e "${GREEN}✓ Conteneurs arrêtés${NC}"
echo ""
echo "Pour redémarrer: ./scripts/start-containers.sh"
echo "Pour supprimer les données: podman compose -f podman-compose.yml down -v"
