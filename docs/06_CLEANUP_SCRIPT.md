# Cleanup Script - Podman Environment

Nettoyage complet de l'environnement Podman (conteneurs, images, volumes, réseaux).

## Description

Le script `cleanup.sh` permet de supprimer **complètement** tous les conteneurs, images, volumes et réseaux Podman. Utilisez-le pour repartir de zéro ou libérer de l'espace disque.

## ⚠️ Avertissement

**Ce script supprime TOUT** :
- Tous les conteneurs (en cours d'exécution et arrêtés)
- Toutes les images (y compris personnalisées)
- Tous les volumes (données de base de données incluses)
- Tous les réseaux personnalisés

**Les données ne sont PAS récupérables !** Assurez-vous d'avoir sauvegardé vos données importantes avant d'exécuter ce script.

## Prérequis

- Podman installé et configuré
- Bash ou Git Bash (sous Windows)
- Permissions d'exécution sur le script

## Utilisation

### 1. Rendre le script exécutable

```bash
chmod +x scripts/cleanup.sh
```

### 2. Exécuter le script

```bash
./scripts/cleanup.sh
```

### 3. Confirmer l'action

Le script demande confirmation avant de procéder :

```
⚠️  Cette action va supprimer TOUS les conteneurs, images, volumes et réseaux. Continuer ? (oui/non) :
```

Tapez `oui` pour continuer ou `non` pour annuler.

## Étapes du nettoyage

Le script exécute les opérations suivantes dans l'ordre :

1. **Arrêt des conteneurs** : Arrête tous les conteneurs en cours d'exécution
2. **Suppression des conteneurs** : Supprime tous les conteneurs (y compris arrêtés)
3. **Suppression des images** : Supprime toutes les images Docker/Podman
4. **Suppression des volumes** : Supprime tous les volumes (données persistantes)
5. **Suppression des réseaux** : Supprime tous les réseaux personnalisés
6. **Nettoyage système** : Exécute `podman system prune -a --volumes -f`
7. **Résumé** : Affiche l'état final et l'espace récupéré

## Sortie attendue

```
==========================================
  Nettoyage complet de l'environnement Podman
==========================================

⚠️  Cette action va supprimer TOUS les conteneurs, images, volumes et réseaux. Continuer ? (oui/non) : oui

>>> 1. Arrêt des conteneurs en cours d'exécution

✅ Conteneurs arrêtés

>>> 2. Suppression de tous les conteneurs

✅ Conteneurs supprimés

>>> 3. Suppression de toutes les images

✅ Images supprimées

>>> 4. Suppression de tous les volumes

✅ Volumes supprimés

>>> 5. Suppression des réseaux personnalisés

✅ Réseaux personnalisés supprimés

>>> 6. Nettoyage final du système

✅ Nettoyage complet terminé

État actuel :
-------------
TYPE           TOTAL   ACTIVE  SIZE    RECLAIMABLE
Images         0       0       0B      0B (0%)
Containers     0       0       0B      0B (0%)
Local Volumes  0       0       0B      0B (0%)

Conteneurs : 1 (hors ligne d'en-tête)
Images     : 1 (hors ligne d'en-tête)
Volumes    : 1 (hors ligne d'en-tête)
Réseaux    : 1 (hors ligne d'en-tête)

==========================================
  Nettoyage terminé avec succès !
==========================================
```

## Alternative manuelle

Si vous préférez exécuter les commandes manuellement :

```bash
# Arrêter tous les conteneurs
podman stop $(podman ps -q)

# Supprimer tous les conteneurs
podman rm -f $(podman ps -aq)

# Supprimer toutes les images
podman rmi -f $(podman images -q)

# Supprimer tous les volumes
podman volume rm -f $(podman volume ls -q)

# Supprimer tous les réseaux personnalisés
podman network ls --format '{{.Name}}' | grep -v '^podman$' | xargs podman network rm

# Nettoyage final
podman system prune -a --volumes -f
```

## Commande rapide (sans confirmation)

Si vous voulez exécuter le nettoyage sans confirmation interactive :

```bash
podman system prune -a --volumes -f
```

**Attention** : Cette commande ne demande pas de confirmation avec l'option `-f` (force).

## Après le nettoyage

Après avoir exécuté le script, vous pouvez :

1. **Reconstruire l'environnement** : Suivez la [Procédure complète validée](01_PODMAN_INSTALL.md#9-procédure-complète-validée)
2. **Vérifier l'espace disque** : `podman system df`
3. **Redémarrer Podman Machine** (si nécessaire) : `podman machine restart`

## Dépannage

### Le script ne démarre pas

```bash
# Vérifier les permissions
ls -la scripts/cleanup.sh

# Donner les permissions d'exécution
chmod +x scripts/cleanup.sh
```

### Erreur "command not found"

```bash
# Vérifier que Podman est installé
podman --version

# Vérifier que Podman Machine est démarrée
podman machine list
```

### Volumes impossibles à supprimer

```bash
# Arrêter tous les conteneurs d'abord
podman stop $(podman ps -q)

# Forcer la suppression
podman volume rm -f $(podman volume ls -q)
```

## Bonnes pratiques

- **Sauvegardez vos données** : Exportez vos volumes avant le nettoyage
- **Documentez votre configuration** : Notez les réseaux et volumes importants
- **Testez sur un environnement non-critique** : Validez le script sur un environnement de test d'abord

## Voir aussi

- [01 - Guide d'installation Podman](01_PODMAN_INSTALL.md) - Installation et configuration Podman
- [00 - Guide de lecture](00_GUIDE_LECTURE.md) - Comment naviguer dans la documentation
- [README.md](../README.md) - Guide de démarrage rapide
- [CHANGELOG.md](../CHANGELOG.md) - Historique des modifications

---

**Auteur** : Abdellah Sahraoui  
**Date** : Novembre 2025  
**Version** : 0.2
