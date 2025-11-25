#!/bin/bash
# Script de vérification de santé des conteneurs
# Vérifie l'état et les healthchecks de tous les conteneurs

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Vérification de santé des conteneurs${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

# Fonction pour vérifier un conteneur
check_container() {
    local container=$1
    local service_name=$2
    
    echo -n "Vérification de ${service_name}... "
    
    # Vérifier si le conteneur existe
    if ! podman ps -a --format "{{.Names}}" | grep -q "^${container}$"; then
        echo -e "${RED}❌ NON TROUVÉ${NC}"
        return 1
    fi
    
    # Vérifier si le conteneur est en cours d'exécution
    if ! podman ps --format "{{.Names}}" | grep -q "^${container}$"; then
        echo -e "${RED}❌ ARRÊTÉ${NC}"
        return 1
    fi
    
    # Vérifier le healthcheck
    health_status=$(podman inspect ${container} --format='{{.State.Health.Status}}' 2>/dev/null || echo "none")
    
    case $health_status in
        "healthy")
            echo -e "${GREEN}✓ HEALTHY${NC}"
            return 0
            ;;
        "unhealthy")
            echo -e "${RED}❌ UNHEALTHY${NC}"
            echo -e "${YELLOW}  Détails du healthcheck:${NC}"
            podman inspect ${container} --format='{{json .State.Health}}' | jq '.' 2>/dev/null || echo "  (jq non installé)"
            return 1
            ;;
        "starting")
            echo -e "${YELLOW}⏳ STARTING${NC}"
            return 0
            ;;
        "none")
            echo -e "${YELLOW}⚠ PAS DE HEALTHCHECK${NC}"
            return 0
            ;;
        *)
            echo -e "${YELLOW}⚠ STATUT INCONNU: ${health_status}${NC}"
            return 1
            ;;
    esac
}

# Vérifier chaque conteneur
echo "Conteneurs à vérifier:"
echo ""

check_container "web" "Apache (web)"
web_status=$?

check_container "php" "PHP-FPM (php)"
php_status=$?

check_container "db" "PostgreSQL (db)"
db_status=$?

echo ""
echo -e "${BLUE}───────────────────────────────────────────────────────────────${NC}"
echo ""

# Afficher un résumé
echo "Résumé:"
echo ""

if [ $web_status -eq 0 ] && [ $php_status -eq 0 ] && [ $db_status -eq 0 ]; then
    echo -e "${GREEN}✓ Tous les conteneurs sont opérationnels${NC}"
    exit 0
else
    echo -e "${RED}❌ Certains conteneurs ont des problèmes${NC}"
    echo ""
    echo "Commandes de dépannage:"
    echo "  - Voir les logs: podman logs <container>"
    echo "  - Redémarrer: podman restart <container>"
    echo "  - Détails: podman inspect <container>"
    exit 1
fi
