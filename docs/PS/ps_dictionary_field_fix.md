# Fix du champ ps_dictionary - Problème de persistence des valeurs

**Date:** 2 décembre 2025  
**Module:** ps_dictionary  
**Version:** Drupal 11.2.8  
**Branche:** release/0.2-ps

## Contexte

Le module `ps_dictionary` fournit un type de champ personnalisé (`DictionaryItem`) qui permet de référencer des entrées de dictionnaire métier (B2B/B2C, types de propriété, statuts, etc.). Ce champ étend `ListStringItem` du module core `options`.

## Problème identifié

Lors de l'utilisation du champ `ps_dictionary` dans un formulaire d'édition de contenu :
- Le widget affichait correctement les options disponibles (ex: B2B, B2C)
- L'utilisateur pouvait sélectionner une valeur
- Le formulaire se soumettait sans erreur
- **MAIS** la valeur ne se sauvegardait pas dans la base de données (restait NULL)

### Symptômes

```bash
# Test en base de données
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->load(3); 
echo \$node->get('field_customer_type')->value;"
# Résultat: NULL (attendu: "B2B")
```

### Analyse des logs

Les logs de debug montraient :
```
massageFormValues received:
Array (
    [0] => Array (
        [0] => Array (
            [value] => B2B
        )
    )
)
```

Structure incorrecte avec un niveau d'imbrication supplémentaire.

## Causes racines

### 1. Configuration du champ (DictionaryItem.php)

**Problème:** Le champ `DictionaryItem` étendait `ListStringItem` qui s'attend à ce que les valeurs autorisées soient définies via :
- Soit un tableau statique `allowed_values` 
- Soit une fonction callback `allowed_values_function`

Le module utilisait aucun de ces mécanismes, laissant `allowed_values` vide.

**Impact:** La validation du champ échouait silencieusement car aucune valeur n'était considérée comme valide.

### 2. Widget surchargé (DictionarySelectWidget.php)

**Problème 1:** Le widget surchargeait manuellement `#options` dans `formElement()`, empêchant le parent `OptionsSelectWidget` d'utiliser le mécanisme standard `getOptionsProvider()`.

**Problème 2:** Le parent `OptionsSelectWidget` est conçu pour les champs multi-valeurs et retournait une structure imbriquée :
```php
[0][0][value] => "B2B"  // Structure retournée
[0][value] => "B2B"     // Structure attendue
```

**Impact:** Les valeurs soumises avaient une structure incompatible avec ce que le système de champ attendait.

## Solutions implémentées

### 1. Utilisation du mécanisme standard allowed_values_function

**Fichier:** `src/Plugin/Field/FieldType/DictionaryItem.php`

**Changement:**
```php
public static function defaultStorageSettings(): array {
  return [
    'dictionary_type' => '',
    // Utilise une fonction callback pour les valeurs dynamiques
    'allowed_values_function' => '\Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem::getAllowedValuesCallback',
  ] + parent::defaultStorageSettings();
}

/**
 * Callback pour fournir les valeurs autorisées dynamiquement.
 */
public static function getAllowedValuesCallback(FieldStorageDefinitionInterface $definition, $entity = NULL, &$cacheable = TRUE): array {
  $dictionary_type = $definition->getSetting('dictionary_type');
  
  if (!$dictionary_type) {
    return [];
  }
  
  /** @var \Drupal\ps_dictionary\Service\DictionaryManagerInterface $manager */
  $manager = \Drupal::service('ps_dictionary.manager');
  return $manager->getOptions($dictionary_type);
}
```

**Explication:** Cette approche utilise le mécanisme standard de Drupal pour les valeurs dynamiques. La fonction `options_allowed_values()` du core appellera automatiquement ce callback pour obtenir les options.

**Migration du champ existant:**
```bash
drush ev "$storage = \Drupal::entityTypeManager()->getStorage('field_storage_config')
  ->load('node.field_customer_type'); 
$storage->setSetting('allowed_values_function', 
  '\Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem::getAllowedValuesCallback'); 
$storage->save();"
```

### 2. Simplification du widget

**Fichier:** `src/Plugin/Field/FieldWidget/DictionarySelectWidget.php`

**Changement 1 - formElement():**
```php
public function formElement(FieldItemListInterface $items, $delta, array $element, 
  array &$form, FormStateInterface $form_state): array {
  // Laisse le parent gérer tout - il utilisera allowed_values_function
  // automatiquement via getOptionsProvider().
  $element = parent::formElement($items, $delta, $element, $form, $form_state);

  // Ajoute seulement une description utile si non configuré
  $dictionary_type = $this->fieldDefinition
    ->getFieldStorageDefinition()
    ->getSetting('dictionary_type');
  
  if (!$dictionary_type) {
    $element['#description'] = $this->t('Dictionary type not configured.');
  }

  return $element;
}
```

**Changement 2 - massageFormValues():**
```php
public function massageFormValues(array $values, array $form, 
  FormStateInterface $form_state): array {
  $massaged = parent::massageFormValues($values, $form, $form_state);

  // Corrige la structure imbriquée [0][0][value] => [0][value]
  $fixed = [];
  foreach ($massaged as $delta => $item) {
    if (is_array($item) && isset($item[0]) && is_array($item[0])) {
      // Structure imbriquée - extrait le tableau interne
      $fixed[$delta] = $item[0];
    }
    else {
      $fixed[$delta] = $item;
    }
  }

  return $fixed;
}
```

**Explication:** 
- Le widget ne surcharge plus manuellement les options
- Le parent `OptionsSelectWidget` gère automatiquement via `getOptionsProvider()`
- `massageFormValues()` corrige la structure des valeurs soumises

## Flux de données après correction

### Affichage du formulaire
```
1. OptionsSelectWidget::formElement()
2. → getOptions($entity)
3. → getOptionsProvider()
4. → OptionsProviderInterface::getSettableOptions()
5. → options_allowed_values($definition)
6. → DictionaryItem::getAllowedValuesCallback()
7. → DictionaryManager::getOptions('customer_type')
8. → Retourne ['B2B' => 'B2B', 'B2C' => 'B2C']
```

### Soumission du formulaire
```
1. Utilisateur sélectionne "B2B" et soumet
2. OptionsSelectWidget::massageFormValues()
3. → Parent retourne [0][0][value] => "B2B"
4. DictionarySelectWidget::massageFormValues()
5. → Corrige en [0][value] => "B2B"
6. FieldItemList::setValue()
7. → DictionaryItem->value = "B2B"
8. Entity::save()
9. → Validation OK (B2B existe dans allowed_values)
10. → Persistence en base de données ✓
```

## Tests de validation

### Test via code
```bash
# Création d'un nouveau node
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->create([
  'type' => 'offer', 
  'title' => 'Test Offer', 
  'field_customer_type' => 'B2B'
]); 
\$node->save(); 
\$loaded = \Drupal::entityTypeManager()->getStorage('node')->load(\$node->id()); 
echo \$loaded->get('field_customer_type')->value;"
# Résultat attendu: "B2B" ✓
```

### Test via UI
1. Accéder à `/node/3/edit` (type de contenu Offer)
2. Sélectionner "B2B" dans le champ "Customer type"
3. Cliquer sur "Enregistrer"
4. Vérifier la valeur en base :
```bash
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->load(3); 
echo \$node->get('field_customer_type')->value;"
# Résultat: "B2B" ✓
```

## Fichiers modifiés

### DictionaryItem.php
- Ajout de `allowed_values_function` dans `defaultStorageSettings()`
- Ajout de la méthode statique `getAllowedValuesCallback()`
- Suppression des tentatives de surcharge de `getPossibleValues()` (incompatible)

### DictionarySelectWidget.php
- Simplification de `formElement()` - suppression de la surcharge de `#options`
- Ajout de `massageFormValues()` pour corriger la structure des valeurs
- Suppression de l'injection de `DictionaryManager` (non utilisé)

### Configuration (manuelle)
```bash
# Mise à jour du champ existant field_customer_type
drush ev "$storage = \Drupal::entityTypeManager()
  ->getStorage('field_storage_config')
  ->load('node.field_customer_type'); 
\$storage->setSetting('allowed_values_function', 
  '\Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem::getAllowedValuesCallback'); 
\$storage->save();"
```

## Leçons apprises

### 1. Respecter l'architecture de Drupal
Au lieu de tenter de surcharger des méthodes avec des signatures incompatibles, utiliser les mécanismes prévus :
- `allowed_values_function` pour les valeurs dynamiques
- `getOptionsProvider()` pour l'intégration avec les widgets

### 2. Structure des données de formulaire
Les widgets multi-valeurs de Drupal peuvent retourner des structures imbriquées. Toujours vérifier la structure dans `massageFormValues()`.

### 3. Debug méthodique
L'utilisation de `\Drupal::logger()` pour tracer le flux de données a permis d'identifier rapidement :
- Les valeurs arrivaient bien dans le widget
- La structure était incorrecte
- preSave/postSave n'étaient jamais appelés

### 4. Validation des champs
Un champ qui hérite de `ListItemBase` **doit** définir ses valeurs autorisées, sinon toutes les valeurs sont rejetées silencieusement.

## Références

- [Options module - allowed_values_function](https://git.drupalcode.org/project/drupal/-/blob/11.x/core/modules/options/options.module)
- [OptionsProviderInterface](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21TypedData%21OptionsProviderInterface.php)
- [ListItemBase](https://git.drupalcode.org/project/drupal/-/blob/11.x/core/modules/options/src/Plugin/Field/FieldType/ListItemBase.php)
- [OptionsSelectWidget](https://git.drupalcode.org/project/drupal/-/blob/11.x/core/lib/Drupal/Core/Field/Plugin/Field/FieldWidget/OptionsSelectWidget.php)
