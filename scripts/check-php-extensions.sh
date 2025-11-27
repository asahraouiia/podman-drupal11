#!/bin/bash
# Script de vérification des extensions PHP requises pour Drupal 11

set -e

echo "=========================================="
echo " Vérification des extensions PHP"
echo "=========================================="
echo ""

# Fonction pour vérifier une extension
check_extension() {
    local ext=$1
    local required=${2:-"requis"}
    
    if podman exec php php -m 2>/dev/null | grep -i "^${ext}$" >/dev/null; then
        echo "✅ $ext - installé ($required)"
        return 0
    else
        echo "❌ $ext - MANQUANT ($required)"
        return 1
    fi
}

# Fonction pour vérifier et afficher les détails d'une extension
check_extension_details() {
    local ext=$1
    echo ""
    echo "--- Détails de $ext ---"
    podman exec php php -i | grep -A 10 "^${ext}$" | head -15
}

echo "Extensions PHP requises pour Drupal 11:"
echo ""

# Extensions requises
MISSING=0

check_extension "pdo" "requis" || ((MISSING++))
check_extension "pdo_pgsql" "requis pour PostgreSQL" || ((MISSING++))
check_extension "pgsql" "requis pour PostgreSQL" || ((MISSING++))
check_extension "gd" "requis pour images" || ((MISSING++))
check_extension "xml" "requis" || ((MISSING++))
check_extension "zip" "requis" || ((MISSING++))
check_extension "intl" "requis" || ((MISSING++))

# OPcache a un nom spécial
if podman exec php php -m 2>/dev/null | grep -i "OPcache" >/dev/null; then
    echo "✅ opcache - installé (recommandé)"
else
    echo "❌ opcache - MANQUANT (recommandé)"
    ((MISSING++))
fi

check_extension "bcmath" "recommandé" || ((MISSING++))

echo ""
echo "Extensions optionnelles:"
echo ""

check_extension "apcu" "optionnel - cache" || true

echo ""
echo "=========================================="

# Détails GD (important pour Drupal)
check_extension_details "gd"

echo ""
echo "=========================================="
echo " Version PHP"
echo "=========================================="
podman exec php php -v | head -1

echo ""
echo "=========================================="
echo " Résumé"
echo "=========================================="

if [ $MISSING -eq 0 ]; then
    echo "✅ Toutes les extensions requises sont installées"
    exit 0
else
    echo "❌ $MISSING extension(s) manquante(s)"
    exit 1
fi
