#!/bin/bash
# Script de gestion des modules Apache
# Permet d'activer ou désactiver des modules Apache et de reconstruire le conteneur

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DOCKERFILE="$PROJECT_ROOT/docker/apache/Dockerfile"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Modules Apache couramment utilisés
AVAILABLE_MODULES=(
    "rewrite"
    "headers"
    "expires"
    "deflate"
    "ssl"
    "proxy"
    "proxy_http"
    "proxy_fcgi"
    "proxy_balancer"
    "proxy_wstunnel"
    "remoteip"
    "socache_shmcb"
    "auth_basic"
    "authn_file"
    "authz_user"
    "authz_groupfile"
    "mime"
    "dir"
    "alias"
    "filter"
)

show_help() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Script de gestion des modules Apache${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS] <action> <module1> [module2] [...]"
    echo ""
    echo "Actions:"
    echo "  enable <module>      Active un ou plusieurs modules"
    echo "  disable <module>     Désactive un ou plusieurs modules"
    echo "  list                 Liste tous les modules disponibles"
    echo "  status               Affiche les modules actuellement activés"
    echo "  rebuild              Reconstruit le conteneur Apache"
    echo ""
    echo "Options:"
    echo "  --no-rebuild         Ne reconstruit pas le conteneur (juste modifie le Dockerfile)"
    echo "  --restart            Redémarre le conteneur après reconstruction"
    echo "  -h, --help           Affiche cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0 enable headers expires         # Active mod_headers et mod_expires"
    echo "  $0 disable ssl                    # Désactive mod_ssl"
    echo "  $0 list                            # Liste les modules disponibles"
    echo "  $0 status                          # Affiche les modules activés"
    echo "  $0 enable deflate --restart        # Active mod_deflate et redémarre"
    echo ""
    echo "Modules couramment utilisés pour Drupal:"
    echo "  - rewrite    : Clean URLs (déjà activé)"
    echo "  - headers    : Gestion des en-têtes HTTP"
    echo "  - expires    : Cache et expiration des contenus"
    echo "  - deflate    : Compression gzip"
    echo "  - ssl        : Support HTTPS"
    echo ""
}

list_available_modules() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Modules Apache disponibles${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    for module in "${AVAILABLE_MODULES[@]}"; do
        echo -e "  ${GREEN}✓${NC} mod_${module}"
    done
    echo ""
    echo "Note: D'autres modules peuvent être disponibles. Consultez la documentation Apache."
}

get_current_modules() {
    if [ ! -f "$DOCKERFILE" ]; then
        echo -e "${RED}❌ Erreur: Dockerfile introuvable: $DOCKERFILE${NC}"
        exit 1
    fi
    
    # Extraire les modules depuis le Dockerfile
    grep "LoadModule.*modules/mod_" "$DOCKERFILE" | \
        sed -E "s/.*'LoadModule ([a-z_]+)_module.*/\1/" | \
        grep -v "^LoadModule$" || true
}

show_status() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Modules Apache actuellement activés${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    local modules=$(get_current_modules)
    
    if [ -z "$modules" ]; then
        echo -e "${YELLOW}  Aucun module explicitement activé dans le Dockerfile${NC}"
    else
        while IFS= read -r module; do
            echo -e "  ${GREEN}✓${NC} mod_${module}"
        done <<< "$modules"
    fi
    echo ""
    
    # Afficher un aperçu du bloc de modules dans le Dockerfile
    echo -e "${BLUE}Bloc de configuration actuel:${NC}"
    echo -e "${YELLOW}────────────────────────────────────────────────────────────────${NC}"
    grep -A 10 "# Ensure proxy modules" "$DOCKERFILE" | head -n 5
    echo -e "${YELLOW}────────────────────────────────────────────────────────────────${NC}"
    echo ""
}

enable_modules() {
    local modules=("$@")
    local current_modules=$(get_current_modules)
    
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Activation de modules Apache${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Construire la nouvelle liste de modules
    local new_modules=""
    for module in $current_modules; do
        new_modules+="    'LoadModule ${module}_module modules/mod_${module}.so' \\\\\n"
    done
    
    # Ajouter les nouveaux modules
    for module in "${modules[@]}"; do
        if echo "$current_modules" | grep -q "^${module}$"; then
            echo -e "${YELLOW}  ⚠  mod_${module} est déjà activé${NC}"
        else
            echo -e "${GREEN}  ✓  Activation de mod_${module}${NC}"
            new_modules+="    'LoadModule ${module}_module modules/mod_${module}.so' \\\\\n"
        fi
    done
    
    # Retirer le dernier backslash
    new_modules=$(echo -e "$new_modules" | sed '$ s/ \\\\$//')
    
    # Mettre à jour le Dockerfile
    update_dockerfile "$new_modules"
}

disable_modules() {
    local modules=("$@")
    local current_modules=$(get_current_modules)
    
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Désactivation de modules Apache${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Construire la nouvelle liste sans les modules à désactiver
    local new_modules=""
    for module in $current_modules; do
        local skip=false
        for disable_module in "${modules[@]}"; do
            if [ "$module" == "$disable_module" ]; then
                skip=true
                echo -e "${GREEN}  ✓  Désactivation de mod_${module}${NC}"
                break
            fi
        done
        
        if [ "$skip" == "false" ]; then
            new_modules+="    'LoadModule ${module}_module modules/mod_${module}.so' \\\\\n"
        fi
    done
    
    # Retirer le dernier backslash
    new_modules=$(echo -e "$new_modules" | sed '$ s/ \\\\$//')
    
    if [ -z "$new_modules" ]; then
        new_modules="    'LoadModule proxy_module modules/mod_proxy.so'"
    fi
    
    # Mettre à jour le Dockerfile
    update_dockerfile "$new_modules"
}

update_dockerfile() {
    local new_modules="$1"
    
    # Créer une sauvegarde
    cp "$DOCKERFILE" "$DOCKERFILE.bak"
    echo -e "${BLUE}  ℹ  Sauvegarde créée: $DOCKERFILE.bak${NC}"
    
    # Utiliser sed pour remplacer le bloc RUN printf
    # On cherche le bloc qui commence par "# Ensure proxy modules" et se termine avant "# Ensure our sites-enabled"
    awk -v new="$new_modules" '
    BEGIN { in_block=0; printed=0 }
    /# Ensure proxy modules/ { 
        print
        in_block=1
        next
    }
    /# Ensure our sites-enabled/ {
        if (in_block && !printed) {
            print "RUN printf '\''%s\\n'\'' \\"
            printf "%s", new
            print " >> /usr/local/apache2/conf/httpd.conf || true"
            print ""
            printed=1
        }
        in_block=0
        print
        next
    }
    {
        if (!in_block) print
    }
    ' "$DOCKERFILE" > "$DOCKERFILE.tmp"
    
    mv "$DOCKERFILE.tmp" "$DOCKERFILE"
    echo -e "${GREEN}  ✓  Dockerfile mis à jour${NC}"
    echo ""
}

rebuild_container() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Reconstruction du conteneur Apache${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    cd "$PROJECT_ROOT"
    
    echo -e "${YELLOW}  →  Construction de l'image...${NC}"
    podman compose -f podman-compose.yml build web
    
    echo ""
    echo -e "${GREEN}  ✓  Image reconstruite avec succès${NC}"
}

restart_container() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Redémarrage du conteneur Apache${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    cd "$PROJECT_ROOT"
    
    echo -e "${YELLOW}  →  Arrêt du conteneur web...${NC}"
    podman compose -f podman-compose.yml stop web
    
    echo -e "${YELLOW}  →  Démarrage du conteneur web...${NC}"
    podman compose -f podman-compose.yml up -d web
    
    echo ""
    echo -e "${GREEN}  ✓  Conteneur redémarré${NC}"
}

# Parse des arguments
NO_REBUILD=false
RESTART=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        --no-rebuild)
            NO_REBUILD=true
            shift
            ;;
        --restart)
            RESTART=true
            shift
            ;;
        list)
            list_available_modules
            exit 0
            ;;
        status)
            show_status
            exit 0
            ;;
        rebuild)
            rebuild_container
            if [ "$RESTART" == "true" ]; then
                restart_container
            fi
            exit 0
            ;;
        enable)
            shift
            if [ $# -eq 0 ]; then
                echo -e "${RED}❌ Erreur: Aucun module spécifié${NC}"
                echo "Usage: $0 enable <module1> [module2] ..."
                exit 1
            fi
            
            modules=()
            while [[ $# -gt 0 ]] && [[ ! "$1" =~ ^-- ]]; do
                modules+=("$1")
                shift
            done
            
            enable_modules "${modules[@]}"
            
            if [ "$NO_REBUILD" == "false" ]; then
                rebuild_container
            fi
            
            if [ "$RESTART" == "true" ]; then
                restart_container
            fi
            
            echo ""
            echo -e "${GREEN}✓ Modules activés avec succès${NC}"
            exit 0
            ;;
        disable)
            shift
            if [ $# -eq 0 ]; then
                echo -e "${RED}❌ Erreur: Aucun module spécifié${NC}"
                echo "Usage: $0 disable <module1> [module2] ..."
                exit 1
            fi
            
            modules=()
            while [[ $# -gt 0 ]] && [[ ! "$1" =~ ^-- ]]; do
                modules+=("$1")
                shift
            done
            
            disable_modules "${modules[@]}"
            
            if [ "$NO_REBUILD" == "false" ]; then
                rebuild_container
            fi
            
            if [ "$RESTART" == "true" ]; then
                restart_container
            fi
            
            echo ""
            echo -e "${GREEN}✓ Modules désactivés avec succès${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Erreur: Action inconnue: $1${NC}"
            echo ""
            show_help
            exit 1
            ;;
    esac
done

# Si aucun argument, afficher l'aide
show_help
