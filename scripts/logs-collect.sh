#!/bin/bash
# Script de collecte des logs des conteneurs
# Copie les logs des conteneurs vers le dossier logs/

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOGS_DIR="$PROJECT_ROOT/logs"

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Collecte des logs des conteneurs${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

# Créer la structure de logs si nécessaire
mkdir -p "${LOGS_DIR}"/{apache,php,postgres}

# Fonction pour collecter les logs d'un conteneur
collect_logs() {
    local container=$1
    local log_dir=$2
    local log_file=$3
    
    echo -n "Collecte des logs de ${container}... "
    
    if podman ps --format "{{.Names}}" | grep -q "^${container}$"; then
        podman logs ${container} > "${log_dir}/${log_file}" 2>&1
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠ Conteneur non démarré${NC}"
    fi
}

# Collecter les logs
collect_logs "web" "${LOGS_DIR}/apache" "container-logs.log"
collect_logs "php" "${LOGS_DIR}/php" "container-logs.log"
collect_logs "db" "${LOGS_DIR}/postgres" "container-logs.log"

echo ""
echo -e "${GREEN}✓ Logs collectés dans: ${LOGS_DIR}/${NC}"
echo ""
echo "Fichiers créés:"
echo "  - ${LOGS_DIR}/apache/container-logs.log"
echo "  - ${LOGS_DIR}/php/container-logs.log"
echo "  - ${LOGS_DIR}/postgres/container-logs.log"
echo ""
echo "Les logs Apache et PostgreSQL sont également disponibles via les volumes montés:"
echo "  - ${LOGS_DIR}/apache/access.log"
echo "  - ${LOGS_DIR}/apache/error.log"
echo "  - ${LOGS_DIR}/php/php-fpm.log"
echo "  - ${LOGS_DIR}/postgres/postgresql.log"
