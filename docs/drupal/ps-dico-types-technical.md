# ps_dico_types - Documentation technique

## Architecture interne

### Structure des entités

#### PsDicoType (Entité de configuration)

**Annotation** :
```php
@ConfigEntityType(
  id = "ps_dico_type",
  label = @Translation("Dictionary Type"),
  handlers = {
    "list_builder" = "Drupal\ps_dico_types\PsDicoTypeListBuilder",
    "form" = {
      "add" = "Drupal\ps_dico_types\Form\PsDicoTypeForm",
      "edit" = "Drupal\ps_dico_types\Form\PsDicoTypeForm",
      "delete" = "Drupal\Core\Entity\EntityDeleteForm"
    }
  },
  config_prefix = "ps_dico_type",
  admin_permission = "administer ps_dico_types",
  entity_keys = {
    "id" = "id",
    "label" = "label"
  },
  config_export = {
    "id",
    "label",
    "description"
  }
)
```

**Propriétés** :
- `id` (string) : Identifiant machine unique
- `label` (string) : Label lisible
- `description` (string) : Description optionnelle

**Méthodes publiques** :
- `getDescription(): string`
- `setDescription(string $description): PsDicoTypeInterface`
- `getCacheTags(): array` - Retourne `['config:ps_dico_type:{id}', 'ps_dico_type:{id}']`
- `getCacheMaxAge(): int` - Retourne la valeur de `SettingsManager::getCacheTtl()`

#### PsDico (Entité de configuration)

**Annotation** :
```php
@ConfigEntityType(
  id = "ps_dico",
  label = @Translation("Dictionary Item"),
  handlers = {
    "list_builder" = "Drupal\ps_dico_types\PsDicoListBuilder",
    "form" = {
      "add" = "Drupal\ps_dico_types\Form\PsDicoForm",
      "edit" = "Drupal\ps_dico_types\Form\PsDicoForm",
      "delete" = "Drupal\Core\Entity\EntityDeleteForm"
    }
  },
  config_prefix = "ps_dico",
  admin_permission = "administer ps_dico_types",
  entity_keys = {
    "id" = "id",
    "label" = "label"
  },
  config_export = {
    "id",
    "label",
    "type",
    "weight"
  }
)
```

**Propriétés** :
- `id` (string) : Identifiant machine unique
- `label` (string) : Label lisible
- `type` (string) : Référence vers l'id du PsDicoType
- `weight` (int) : Poids pour le tri (défaut: 0)

**Méthodes publiques** :
- `getType(): string`
- `setType(string $type): PsDicoInterface`
- `getWeight(): int`
- `setWeight(int $weight): PsDicoInterface`
- `getCacheTags(): array` - Retourne `['config:ps_dico:{id}', 'ps_dico:{id}', 'ps_dico_type:{type}']`
- `getCacheMaxAge(): int` - Retourne la valeur de `SettingsManager::getCacheTtl()`

### Type de champ PsDictionaryItem

**Annotation** :
```php
@FieldType(
  id = "ps_dictionary",
  label = @Translation("Dictionary"),
  description = @Translation("Stores a reference to a dictionary item (ps_dico)."),
  default_widget = "ps_dictionary_select",
  default_formatter = "ps_dictionary_label"
)
```

**Propriétés stockées** :
- `value` (string, varchar 255) : ID de l'entité PsDico référencée

**Schéma de base de données** :
```php
[
  'columns' => [
    'value' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ],
  ],
  'indexes' => [
    'value' => ['value'],
  ],
]
```

**Paramètres de stockage (Storage Settings)** :
- `dictionary_type` (string, requis) : ID du PsDicoType à utiliser pour filtrer les options du widget

**Méthodes publiques** :
- `defaultStorageSettings(): array` - Retourne `['dictionary_type' => '']`
- `storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array` - Formulaire de configuration du type de dictionnaire (désactivé si le champ contient des données)
- `isEmpty(): bool` - Vérifie si le champ est vide
- `getEntity(): ?PsDicoInterface` - Charge et retourne l'entité PsDico référencée (avec cache local pour éviter les rechargements répétés)

### Widget PsDictionarySelectWidget

**Annotation** :
```php
@FieldWidget(
  id = "ps_dictionary_select",
  label = @Translation("Dictionary select"),
  field_types = {
    "ps_dictionary"
  }
)
```

**Comportement** :
1. Lit le paramètre `dictionary_type` depuis les paramètres de stockage du champ (Field Storage Settings)
2. Charge tous les PsDico ayant `type = dictionary_type`
3. Trie par `weight` ASC, puis par `label` (alphabétique)
4. Génère un select avec les options filtrées

**Note importante** :
Le widget ne possède plus de paramètre `dictionary_type` dans ses propres settings. Ce paramètre est maintenant configuré au niveau du stockage du champ (voir `PsDictionaryItem::storageSettingsForm()`), ce qui suit le pattern Drupal des champs de référence d'entité (comme `entity_reference` avec `target_type`).

**Injection de dépendances** :
- `EntityTypeManagerInterface` pour charger les entités

### Formatter PsDictionaryLabelFormatter

**Annotation** :
```php
@FieldFormatter(
  id = "ps_dictionary_label",
  label = @Translation("Dictionary label"),
  field_types = {
    "ps_dictionary"
  }
)
```

**Paramètres (settings)** :
- `link_to_entity` (bool, défaut: FALSE) : Afficher le label comme lien vers l'edit form

**Comportement** :
1. Charge l'entité PsDico via `$item->value`
2. Affiche le label ou un lien vers `entity.ps_dico.edit_form`
3. Ajoute les cache tags : `ps_dico:{id}`, `ps_dico_type:{type}`
4. Ajoute le cache context : `languages`
5. Définit le cache max-age via `SettingsManager::getCacheTtl()`

**Injection de dépendances** :
- `EntityTypeManagerInterface` pour charger les entités
- `SettingsManager` pour le cache TTL

## Service SettingsManager

### Définition du service

**Fichier** : `ps_dico_types.services.yml`

```yaml
services:
  ps_dico_types.settings_manager:
    class: Drupal\ps_dico_types\Service\SettingsManager
    arguments: ['@config.factory']
```

### API publique

```php
namespace Drupal\ps_dico_types\Service;

class SettingsManager {
  
  // Accès générique
  public function get(string $key, $default = NULL): mixed;
  
  // Accès typé
  public function getBool(string $key, bool $default = FALSE): bool;
  public function getInt(string $key, int $default = 0): int;
  public function getArray(string $key, array $default = []): array;
  
  // Raccourcis métier
  public function getCacheTtl(): int;
  public function isTelemetryEnabled(): bool;
  
  // Export complet
  public function getAll(): array;
}
```

### Utilisation recommandée

```php
// Dans un contrôleur/service avec injection
public function __construct(SettingsManager $settings_manager) {
  $this->settingsManager = $settings_manager;
}

// Lecture du cache TTL
$ttl = $this->settingsManager->getCacheTtl();

// Lecture d'un paramètre booléen
$enabled = $this->settingsManager->getBool('enable_feature', TRUE);
```

## Routes et contrôleurs

### Routes principales

| Route | Path | Contrôleur | Permission |
|-------|------|-----------|-----------|
| `entity.ps_dico_type.collection` | `/admin/structure/ps-dico-types` | ListBuilder | `administer ps_dico_types` |
| `entity.ps_dico_type.add_form` | `/admin/structure/ps-dico-types/add` | PsDicoTypeForm | `administer ps_dico_types` |
| `entity.ps_dico_type.edit_form` | `/admin/structure/ps-dico-types/{ps_dico_type}` | PsDicoTypeForm | `administer ps_dico_types` |
| `entity.ps_dico_type.delete_form` | `/admin/structure/ps-dico-types/{ps_dico_type}/delete` | EntityDeleteForm | `administer ps_dico_types` |
| `entity.ps_dico_type.dico_collection` | `/admin/structure/ps-dico-types/{ps_dico_type}/dicos` | PsDicoListController | `administer ps_dico_types` |
| `entity.ps_dico.collection` | `/admin/structure/ps-dico-items` | ListBuilder | `administer ps_dico_types` |
| `entity.ps_dico.add_form` | `/admin/structure/ps-dico-items/add` | PsDicoForm | `administer ps_dico_types` |
| `entity.ps_dico.edit_form` | `/admin/structure/ps-dico-items/{ps_dico}` | PsDicoForm | `administer ps_dico_types` |
| `entity.ps_dico.delete_form` | `/admin/structure/ps-dico-items/{ps_dico}/delete` | EntityDeleteForm | `administer ps_dico_types` |

### Contrôleur PsDicoListController

**Méthode** : `listing(PsDicoTypeInterface $ps_dico_type): array`

**Comportement** :
1. Charge tous les PsDico avec `type = $ps_dico_type->id()`
2. Trie par weight ASC, puis label
3. Génère un tableau avec colonnes : Label, Machine name, Weight, Operations
4. Ajoute un lien "Add dictionary item" pré-rempli avec le type

**Cache metadata** :
- Tags : `ps_dico_type:{id}`
- Contexts : `languages`

**Title callback** : `title(PsDicoTypeInterface $ps_dico_type): string`
- Retourne : "Dictionary items: {label}"

## List Builders

### PsDicoTypeListBuilder

**Colonnes** :
- Label
- Machine name
- Description

**Opérations supplémentaires** :
- "Manage items" (weight: 10) → route `entity.ps_dico_type.dico_collection`

### PsDicoListBuilder

**Colonnes** :
- Label
- Machine name
- Dictionary Type (label résolu)
- Weight

**Tri** :
- Primary : weight ASC
- Secondary : label (alphabétique, case-insensitive)

**Méthode `load()`** :
Surcharge pour appliquer le tri via `uasort()` après chargement des entités.

## Formulaires

### PsDicoTypeForm

**Champs** :
- `label` (textfield, requis, max 255)
- `id` (machine_name, requis, disabled après création)
- `description` (textarea, optionnel)

**Validation** :
- Unicité de l'id via `PsDicoType::load()`

**Save** :
- Message contextuel selon `SAVED_NEW` ou `SAVED_UPDATED`
- Redirection vers `entity.ps_dico_type.collection`

### PsDicoForm

**Champs** :
- `type` (select, requis, disabled après création)
  * Options chargées depuis tous les PsDicoType
- `label` (textfield, requis, max 255)
- `id` (machine_name, requis, disabled après création)
- `weight` (number, défaut: 0)

**Validation** :
- Unicité de l'id via callback `exists()`

**Save** :
- Message contextuel selon `SAVED_NEW` ou `SAVED_UPDATED`
- Redirection :
  * Si `destination=type_collection` → `entity.ps_dico.type_collection`
  * Sinon → `entity.ps_dico.collection`

## Schéma de configuration

### ps_dico_types.settings

```yaml
type: config_object
label: 'Dictionary Types settings'
mapping:
  cache_ttl:
    type: integer
    label: 'Cache TTL (seconds)'
  enable_telemetry:
    type: boolean
    label: 'Enable telemetry'
```

### ps_dico_types.ps_dico_type.*

```yaml
type: config_entity
label: 'Dictionary Type'
mapping:
  id:
    type: string
    label: 'ID'
  label:
    type: label
    label: 'Label'
  description:
    type: text
    label: 'Description'
```

### ps_dico_types.ps_dico.*

```yaml
type: config_entity
label: 'Dictionary Item'
mapping:
  id:
    type: string
    label: 'ID'
  label:
    type: label
    label: 'Label'
  type:
    type: string
    label: 'Dictionary Type'
  weight:
    type: integer
    label: 'Weight'
```

### field.storage_settings.ps_dictionary

```yaml
type: mapping
label: 'Dictionary field storage settings'
```

### field.field_settings.ps_dictionary

```yaml
type: mapping
label: 'Dictionary field settings'
```

### field.widget.settings.ps_dictionary_select

```yaml
type: mapping
label: 'Dictionary select widget settings'
mapping:
  dictionary_type:
    type: string
    label: 'Dictionary Type'
```

### field.formatter.settings.ps_dictionary_label

```yaml
type: mapping
label: 'Dictionary label formatter settings'
mapping:
  link_to_entity:
    type: boolean
    label: 'Link to entity'
```

## Stratégie de cache détaillée

### Cache tags (invalidation)

**PsDicoType** :
- `config:ps_dico_type_list` (liste des types)
- `ps_dico_type:{id}` (type spécifique)

**PsDico** :
- `config:ps_dico_list` (liste globale)
- `ps_dico:{id}` (item spécifique)
- `ps_dico_type:{type}` (tous les items d'un type)

**Héritage des tags** :
Lorsqu'un PsDicoType change, tous les PsDico de ce type sont invalidés via le tag `ps_dico_type:{id}`.

### Cache contexts

- `languages` : Support multilingue (labels traduits)
- `user` : Si implémentation de filtrage par rôle (non actif actuellement)

### Cache max-age

Défini via `SettingsManager::getCacheTtl()` :
- Valeur par défaut : 900 secondes (15 minutes)
- Configurable via `config/install/ps_dico_types.settings.yml`
- Évite `max-age = 0` sauf exception critique

## Exemples de code

### Charger tous les éléments d'un type

```php
$storage = \Drupal::entityTypeManager()->getStorage('ps_dico');
$query = $storage->getQuery()
  ->condition('type', 'property_categories')
  ->accessCheck(TRUE);
$ids = $query->execute();
$items = $storage->loadMultiple($ids);
```

### Ajouter un type de dictionnaire programmatiquement

```php
$type = \Drupal\ps_dico_types\Entity\PsDicoType::create([
  'id' => 'my_type',
  'label' => 'My Dictionary Type',
  'description' => 'Optional description',
]);
$type->save();
```

### Ajouter un élément de dictionnaire

```php
$item = \Drupal\ps_dico_types\Entity\PsDico::create([
  'id' => 'my_item',
  'label' => 'My Item',
  'type' => 'my_type',
  'weight' => 5,
]);
$item->save();
```

### Invalider le cache d'un type

```php
\Drupal\Core\Cache\Cache::invalidateTags(['ps_dico_type:my_type']);
```

## Tests recommandés

### Tests fonctionnels

1. Créer un type de dictionnaire via l'UI
2. Ajouter 3 éléments avec des poids différents
3. Vérifier le tri dans la liste
4. Ajouter un champ Dictionary à un type de contenu
5. Configurer le widget avec le type créé
6. Créer un contenu et sélectionner un élément
7. Vérifier l'affichage avec le formatter

### Tests unitaires

1. `SettingsManager::getCacheTtl()` retourne la valeur par défaut (900)
2. `PsDico::getCacheTags()` contient le tag du type parent
3. Widget retourne un select vide si aucun type configuré
4. Formatter génère un lien si `link_to_entity = TRUE`

## Évolutions possibles

1. **Support hiérarchique** : Ajouter un champ `parent` dans PsDico
2. **Multilingue** : Implémenter `TranslatableInterface` pour les labels
3. **API REST** : Exposer les entités via REST/JSON:API
4. **Dashboard card** : Statistiques d'utilisation des dictionnaires
5. **Import/Export CSV** : Outils Drush pour import/export bulk
6. **Validation avancée** : Contraintes sur les valeurs (regex, longueur, etc.)
7. **Relations** : Support de références croisées entre éléments

## Dépendances

- `drupal:options` (module core) : Fournit les base classes pour les listes
- `drupal:system` (module core) : ConfigEntityBase, routes système

## Fichiers du module

```
ps_dico_types/
├── config/
│   ├── install/
│   │   └── ps_dico_types.settings.yml
│   └── schema/
│       └── ps_dico_types.schema.yml
├── src/
│   ├── Controller/
│   │   └── PsDicoListController.php
│   ├── Entity/
│   │   ├── PsDico.php
│   │   └── PsDicoType.php
│   ├── Form/
│   │   ├── PsDicoForm.php
│   │   └── PsDicoTypeForm.php
│   ├── Plugin/
│   │   └── Field/
│   │       ├── FieldFormatter/
│   │       │   └── PsDictionaryLabelFormatter.php
│   │       ├── FieldType/
│   │       │   └── PsDictionaryItem.php
│   │       └── FieldWidget/
│   │           └── PsDictionarySelectWidget.php
│   ├── Service/
│   │   └── SettingsManager.php
│   ├── PsDicoInterface.php
│   ├── PsDicoListBuilder.php
│   ├── PsDicoTypeInterface.php
│   └── PsDicoTypeListBuilder.php
├── ps_dico_types.info.yml
├── ps_dico_types.links.action.yml
├── ps_dico_types.links.menu.yml
├── ps_dico_types.permissions.yml
├── ps_dico_types.routing.yml
└── ps_dico_types.services.yml
```

Total : 22 fichiers

## Liens de menu et d'action

### Menu items (ps_dico_types.links.menu.yml)

Le module ajoute deux entrées dans le menu Structure :

```yaml
ps_dico_types.admin:
  title: 'Dictionary Types'
  description: 'Manage dictionary types and items'
  route_name: entity.ps_dico_type.collection
  parent: system.admin_structure
  weight: 10

ps_dico_types.dico_items:
  title: 'Dictionary Items'
  description: 'Manage all dictionary items'
  route_name: entity.ps_dico.collection
  parent: system.admin_structure
  weight: 11
```

### Action links (ps_dico_types.links.action.yml)

Boutons d'action sur les pages de liste :

```yaml
entity.ps_dico_type.add_form:
  route_name: entity.ps_dico_type.add_form
  title: 'Add dictionary type'
  appears_on:
    - entity.ps_dico_type.collection

entity.ps_dico.add_form:
  route_name: entity.ps_dico.add_form
  title: 'Add dictionary item'
  appears_on:
    - entity.ps_dico.collection
```

