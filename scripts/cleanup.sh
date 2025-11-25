#!/bin/bash

# Script de nettoyage complet de l'environnement Podman
# Supprime tous les conteneurs, images, volumes et réseaux
# Auteur: Abdellah Sahraoui
# Date: 2025-11-26

set -e

echo "=========================================="
echo "  Nettoyage complet de l'environnement Podman"
echo "=========================================="
echo ""

# Fonction pour afficher une section
print_section() {
    echo ""
    echo ">>> $1"
    echo ""
}

# Fonction pour confirmer l'action
confirm() {
    read -p "⚠️  Cette action va supprimer TOUS les conteneurs, images, volumes et réseaux. Continuer ? (oui/non) : " choice
    case "$choice" in 
        oui|OUI|o|O|yes|YES|y|Y ) return 0;;
        * ) echo "Opération annulée."; exit 1;;
    esac
}

# Demander confirmation
confirm

# 1. Arrêter tous les conteneurs en cours d'exécution
print_section "1. Arrêt des conteneurs en cours d'exécution"
if [ "$(podman ps -q)" ]; then
    podman stop $(podman ps -q)
    echo "✅ Conteneurs arrêtés"
else
    echo "ℹ️  Aucun conteneur en cours d'exécution"
fi

# 2. Supprimer tous les conteneurs (y compris arrêtés)
print_section "2. Suppression de tous les conteneurs"
if [ "$(podman ps -aq)" ]; then
    podman rm -f $(podman ps -aq)
    echo "✅ Conteneurs supprimés"
else
    echo "ℹ️  Aucun conteneur à supprimer"
fi

# 3. Supprimer toutes les images
print_section "3. Suppression de toutes les images"
if [ "$(podman images -q)" ]; then
    podman rmi -f $(podman images -q)
    echo "✅ Images supprimées"
else
    echo "ℹ️  Aucune image à supprimer"
fi

# 4. Supprimer tous les volumes
print_section "4. Suppression de tous les volumes"
if [ "$(podman volume ls -q)" ]; then
    podman volume rm -f $(podman volume ls -q)
    echo "✅ Volumes supprimés"
else
    echo "ℹ️  Aucun volume à supprimer"
fi

# 5. Supprimer tous les réseaux personnalisés
print_section "5. Suppression des réseaux personnalisés"
if [ "$(podman network ls --format '{{.Name}}' | grep -v '^podman$')" ]; then
    podman network ls --format '{{.Name}}' | grep -v '^podman$' | xargs -r podman network rm
    echo "✅ Réseaux personnalisés supprimés"
else
    echo "ℹ️  Aucun réseau personnalisé à supprimer"
fi

# 6. Nettoyage final du système
print_section "6. Nettoyage final du système"
podman system prune -a --volumes -f

# 7. Afficher l'espace disque récupéré
print_section "7. Résumé"
echo "✅ Nettoyage complet terminé"
echo ""
echo "État actuel :"
echo "-------------"
podman system df
echo ""
echo "Conteneurs : $(podman ps -a | wc -l) (hors ligne d'en-tête)"
echo "Images     : $(podman images | wc -l) (hors ligne d'en-tête)"
echo "Volumes    : $(podman volume ls | wc -l) (hors ligne d'en-tête)"
echo "Réseaux    : $(podman network ls | wc -l) (hors ligne d'en-tête)"
echo ""
echo "=========================================="
echo "  Nettoyage terminé avec succès !"
echo "=========================================="
