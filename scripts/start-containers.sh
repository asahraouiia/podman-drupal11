#!/bin/bash
# Script de démarrage complet des conteneurs Drupal
# Initialise Podman puis démarre/reconstruit les conteneurs

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "=== Démarrage des conteneurs Drupal ==="

# 1. Initialiser Podman/WSL
echo "Étape 1: Initialisation de Podman..."
bash "$SCRIPT_DIR/init-podman.sh"

# 2. Se placer dans le répertoire du projet
cd "$PROJECT_ROOT"

# 3. Nettoyer les anciens conteneurs si nécessaire
echo ""
echo "Étape 2: Nettoyage des conteneurs existants..."
podman compose -f podman-compose.yml down 2>/dev/null || true

# Vérifier et supprimer les conteneurs orphelins
ORPHAN_CONTAINERS=$(podman ps -a --filter "name=^(web|php|db)$" --format "{{.Names}}" 2>/dev/null || true)
if [ -n "$ORPHAN_CONTAINERS" ]; then
    echo "Suppression des conteneurs orphelins: $ORPHAN_CONTAINERS"
    podman rm -f $ORPHAN_CONTAINERS
fi

# 4. Reconstruire les images si demandé
if [ "$1" == "--rebuild" ] || [ "$1" == "-r" ]; then
    echo ""
    echo "Étape 3: Reconstruction des images..."
    podman compose -f podman-compose.yml build
else
    echo ""
    echo "Étape 3: Utilisation des images existantes (utilisez --rebuild pour reconstruire)"
fi

# 5. Démarrer les conteneurs
echo ""
echo "Étape 4: Démarrage des conteneurs..."
podman compose -f podman-compose.yml up -d

# 6. Attendre que les services soient prêts
echo ""
echo "Étape 5: Vérification de l'état des services..."
sleep 3

# 7. Afficher l'état final
echo ""
echo "=== État des conteneurs ==="
podman ps --filter "name=^(web|php|db)$"

echo ""
echo "✓ Conteneurs démarrés avec succès"
echo ""
echo "Accès au site: http://localhost:8080"
echo ""
echo "Commandes utiles:"
echo "  - Logs en temps réel: podman compose -f podman-compose.yml logs -f"
echo "  - Shell PHP: podman exec -it php bash"
echo "  - Shell Apache: podman exec -it web bash"
echo "  - Arrêter: podman compose -f podman-compose.yml down"
