# PropertySearch - Module ps_dico_types

## Vue d'ensemble

Le module `ps_dico_types` fournit un système de gestion de dictionnaires pour Drupal 11, conçu pour PropertySearch. Il implémente un pattern similaire à la taxonomie (vocabulaire/termes) mais utilise des entités de configuration pour permettre l'export/import via CMI (Configuration Management Initiative).

## Architecture

### Entités de configuration

Le module définit deux entités de configuration :

1. **PsDicoType** (Type de dictionnaire)
   - Équivalent d'un vocabulaire de taxonomie
   - Propriétés : id, label, description
   - Route de gestion : `/admin/structure/ps-dico-types`

2. **PsDico** (Élément de dictionnaire)
   - Équivalent d'un terme de taxonomie
   - Propriétés : id, label, type (référence vers PsDicoType), weight
   - Route de gestion : `/admin/structure/ps-dico-types/{type}/dicos`

### Type de champ Dictionary

Le module fournit un type de champ réutilisable :

- **Field Type** : `ps_dictionary`
- **Widget** : `ps_dictionary_select` - Select configuré avec filtrage par type
- **Formatter** : `ps_dictionary_label` - Affiche le label avec option de lien

#### Configuration du widget

Le widget dispose d'un paramètre `dictionary_type` permettant de sélectionner le type de dictionnaire. Les éléments affichés dans le select sont automatiquement filtrés selon ce type et triés par poids puis par label.

#### Configuration du formatter

Le formatter dispose d'un paramètre `link_to_entity` pour afficher le label sous forme de lien vers la page d'édition de l'élément.

## Accès rapide

Le module ajoute des entrées dans le menu d'administration :

- **Structure → Dictionary Types** : Accès direct à la liste des types de dictionnaires
- **Structure → Dictionary Items** : Accès direct à la liste globale des éléments

Des boutons d'action sont disponibles sur chaque page de liste :
- "Add dictionary type" sur `/admin/structure/ps-dico-types`
- "Add dictionary item" sur `/admin/structure/ps-dico-items`

## Utilisation

### Démarrage rapide

1. Créez un type de dictionnaire (ex: Transaction Type)
   - Menu: Structure → Dictionary Types → Add
   - ID: `transaction_type`
2. Ajoutez un champ Dictionary sur un type de contenu (ex: Article)
   - Menu: Structure → Content types → Article → Manage fields → Add field → Dictionary
   - Label: Transaction Test (exemple)
3. Configurez le widget du champ
   - Menu: Manage form display → Engrenage (⚙️) du champ → sélectionnez "Dictionary Type" = `transaction_type`
4. Créez/éditez un contenu Article et choisissez une valeur dans la liste filtrée

### Création d'un type de dictionnaire

1. Accéder à **Structure → Dictionary Types** ou `/admin/structure/ps-dico-types`
2. Cliquer sur "Add Dictionary Type"
3. Renseigner :
   - Label : nom lisible du type (ex: "Catégories de biens")
   - Machine name : identifiant technique (ex: "property_categories")
   - Description : texte optionnel expliquant l'usage

### Ajout d'éléments au dictionnaire

1. Depuis la liste des types, cliquer sur "Manage items" pour le type souhaité
2. OU accéder via **Structure → Dictionary Items** puis filtrer par type
3. OU accéder directement à `/admin/structure/ps-dico-types/{type_id}/dicos`
4. Cliquer sur "Add dictionary item"

## Dépannage

- Le paramètre du widget "Dictionary Type" n'apparaît pas
   - Vérifiez que le widget utilisé est bien `Dictionary select` (ps_dictionary_select)
   - Videz les caches: `make drush/cr` ou `podman exec php vendor/bin/drush cr`
   - Assurez-vous que le module `ps_dico_types` est activé
   - Si nécessaire, re-sauvegardez l'affichage de formulaire (Manage form display)

- Erreur de route manquante lors de la navigation
   - Les pages d'administration sont:
      - Types: `/admin/structure/ps-dico-types`
      - Items (globale): `/admin/structure/ps-dico-items`
      - Items par type: `/admin/structure/ps-dico-types/{ps_dico_type}/dicos`
4. Renseigner :
   - Type : pré-rempli et non modifiable après création
   - Label : nom de l'élément (ex: "Appartement")
   - Machine name : identifiant technique (ex: "apartment")
   - Weight : poids pour le tri (plus léger = plus haut)

### Utilisation du champ Dictionary dans un type de contenu

1. Ajouter un champ de type "Dictionary" à votre type de contenu
2. Configurer le widget :
   - Sélectionner le type de dictionnaire à utiliser
3. Configurer le formatter (optionnel) :
   - Activer/désactiver le lien vers l'entité

## Stratégie de cache

Le module implémente une stratégie de cache stricte suivant les guidelines PropertySearch :

### Cache tags

- **PsDicoType** : `ps_dico_type:{id}`
- **PsDico** : `ps_dico:{id}` ET `ps_dico_type:{type}`
  - L'héritage du tag parent permet l'invalidation en cascade

### Cache contexts

- `languages` : pour le support multilingue

### Cache max-age

- TTL par défaut : 900 secondes (15 minutes)
- Configurable via `ps_dico_types.settings.yml` (clé `cache_ttl`)
- Accessible via `SettingsManager::getCacheTtl()`

## SettingsManager

Le module utilise le pattern SettingsManager pour centraliser l'accès à la configuration :

```php
// Injection du service
$settings_manager = \Drupal::service('ps_dico_types.settings_manager');

// Accès aux paramètres typés
$cache_ttl = $settings_manager->getCacheTtl(); // int
$telemetry = $settings_manager->isTelemetryEnabled(); // bool
$value = $settings_manager->get('custom_key', 'default'); // mixed
```

**Important** : Ne jamais utiliser `\Drupal::config('ps_dico_types.settings')` directement.

## Permissions

Une seule permission centralisée :

- `administer ps_dico_types` : Créer, éditer, supprimer les types et éléments

## Export/Import de configuration

Les entités étant des config entities, elles peuvent être exportées/importées via :

```bash
# Export
drush config:export

# Import
drush config:import
```

Les fichiers générés :
- `ps_dico_types.ps_dico_type.{id}.yml` pour chaque type
- `ps_dico_types.ps_dico.{id}.yml` pour chaque élément

## Exemple d'usage

### Scénario : Catégories de biens immobiliers

1. Créer le type "Catégories de biens" (`property_categories`)
2. Ajouter les éléments :
   - "Appartement" (weight: 0)
   - "Maison" (weight: 1)
   - "Terrain" (weight: 2)
   - "Commerce" (weight: 3)
3. Dans le type de contenu "Annonce" :
   - Ajouter un champ "Catégorie" de type Dictionary
   - Configurer le widget avec type = `property_categories`
   - Le select affichera uniquement les 4 catégories, triées par poids

## Migration depuis Taxonomy (optionnel)

Si vous migrez depuis un vocabulaire de taxonomie :

1. Exporter les termes en CSV
2. Créer le type de dictionnaire correspondant
3. Importer via script Drush ou manuellement
4. Mettre à jour les références dans les contenus existants

## Limitations connues

- Les éléments de dictionnaire ne supportent pas la hiérarchie (contrairement aux termes de taxonomie)
- Pas de support multilingue natif (à implémenter si nécessaire)
- Pas d'API pour ajout programmatique d'éléments depuis l'interface

## Support et contributions

Pour toute question ou contribution, consulter la documentation technique détaillée dans `ps-dico-types-technical.md`.
