#!/bin/bash
# Script d'initialisation et démarrage Podman/WSL
# Vérifie et démarre WSL + Podman Machine + Containers

set -e

echo "=== Initialisation de l'environnement Podman ==="

# 1. Vérifier si WSL est actif
echo "Vérification de WSL..."
if ! wsl -l -v &> /dev/null; then
    echo "❌ Erreur: WSL n'est pas accessible"
    exit 1
fi

# 2. Vérifier l'état de la machine Podman
echo "Vérification de la machine Podman..."
MACHINE_STATUS=$(wsl -l -v | grep podman-machine-default | awk '{print $3}')

if [ "$MACHINE_STATUS" != "Running" ]; then
    echo "Machine Podman arrêtée. Redémarrage de WSL et Podman..."
    wsl --shutdown
    sleep 3
    podman machine start
    echo "✓ Machine Podman démarrée"
else
    echo "✓ Machine Podman déjà active"
fi

# 3. Vérifier la connectivité Podman
echo "Vérification de la connectivité Podman..."
if ! podman ps &> /dev/null; then
    echo "⚠ Problème de connexion Podman. Redémarrage..."
    wsl --shutdown
    sleep 3
    podman machine start
fi

# 4. Afficher l'état
echo ""
echo "=== État de l'environnement ==="
podman --version
echo ""
wsl -l -v
echo ""
podman ps -a
echo ""
echo "✓ Environnement Podman prêt"
