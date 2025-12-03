# Fix du champ ps_surface - Problème de persistence des valeurs

**Date:** 3 décembre 2025  
**Module:** ps_division  
**Version:** Drupal 11.2.8  
**Branche:** release/0.2-ps

## Contexte

Le module `ps_division` fournit un type de champ personnalisé (`SurfaceItem`) qui permet de stocker des mesures de surface avec plusieurs propriétés :
- `value` : valeur numérique (float)
- `unit` : unité (M2, HA, etc.) - référence au dictionnaire `surface_unit`
- `type` : type de surface (APPT, BUREAU, etc.) - référence au dictionnaire `surface_type`
- `nature` : nature (INT, EXT, etc.) - référence au dictionnaire `surface_nature`
- `qualification` : qualification (DISPO, LOUE, etc.) - référence au dictionnaire `surface_qualification`

## Problème identifié

Lors de l'utilisation du champ `ps_surface` dans un formulaire :
- Le widget affichait correctement tous les champs (valeur, unité, type, nature, qualification)
- L'utilisateur pouvait saisir les valeurs
- Le formulaire se soumettait sans erreur
- **MAIS** aucune valeur ne se sauvegardait dans la base de données (toutes les propriétés restaient NULL)

### Symptômes

```bash
# Test en base de données
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->load(3); 
$surface = $node->get('field_surface'); 
echo 'Value: ' . ($surface->value ?? 'NULL');"
# Résultat: NULL (attendu: valeur saisie)
```

## Cause racine

Le widget `SurfaceDefaultWidget` utilise une structure imbriquée pour l'organisation visuelle :

```php
$element['row_value_unit'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['container-inline']],
];
$element['row_value_unit']['value'] = [...];
$element['row_value_unit']['unit'] = [...];

$element['row_classification'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['container-inline']],
];
$element['row_classification']['type'] = [...];
$element['row_classification']['nature'] = [...];
$element['row_classification']['qualification'] = [...];
```

Cette structure crée deux niveaux d'imbrication :
```
[delta] => [
  'row_value_unit' => [
    'value' => 100.50,
    'unit' => 'M2'
  ],
  'row_classification' => [
    'type' => 'APPT',
    'nature' => 'INT',
    'qualification' => 'DISPO'
  ]
]
```

Mais le type de champ `SurfaceItem` attend une structure plate :
```
[delta] => [
  'value' => 100.50,
  'unit' => 'M2',
  'type' => 'APPT',
  'nature' => 'INT',
  'qualification' => 'DISPO'
]
```

**Impact:** Les valeurs soumises avaient une structure incompatible avec le schéma du champ, empêchant la sauvegarde.

## Solution implémentée

**Fichier:** `src/Plugin/Field/FieldWidget/SurfaceDefaultWidget.php`

**Ajout de la méthode `massageFormValues()`:**

```php
/**
 * {@inheritdoc}
 */
public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
  $massaged = [];
  
  foreach ($values as $delta => $value) {
    // Extract values from nested containers (row_value_unit and row_classification)
    $massaged[$delta] = [
      'value' => $value['row_value_unit']['value'] ?? NULL,
      'unit' => $value['row_value_unit']['unit'] ?? NULL,
      'type' => $value['row_classification']['type'] ?? NULL,
      'nature' => $value['row_classification']['nature'] ?? NULL,
      'qualification' => $value['row_classification']['qualification'] ?? NULL,
    ];
  }
  
  return $massaged;
}
```

**Explication:** 
La méthode `massageFormValues()` est appelée automatiquement par Drupal lors de la soumission du formulaire. Elle transforme la structure imbriquée des containers en structure plate attendue par le champ.

## Flux de données après correction

### Affichage du formulaire
```
1. SurfaceDefaultWidget::formElement()
2. → Crée la structure imbriquée pour l'affichage
3. → row_value_unit: value + unit (inline)
4. → row_classification: type + nature + qualification (inline)
5. → Rendu HTML avec containers inline pour l'UX
```

### Soumission du formulaire
```
1. Utilisateur saisit: value=100.50, unit=M2, type=APPT, nature=INT, qualification=DISPO
2. Drupal collecte les valeurs dans la structure imbriquée
3. SurfaceDefaultWidget::massageFormValues() est appelé
4. → Extrait les valeurs de row_value_unit et row_classification
5. → Retourne structure plate [value, unit, type, nature, qualification]
6. FieldItemList::setValue()
7. → SurfaceItem->value = 100.50
8. → SurfaceItem->unit = "M2"
9. → SurfaceItem->type = "APPT"
10. Entity::save()
11. → Persistence en base de données ✓
```

## Tests de validation

### Test via code
```bash
# Création d'un nouveau node avec champ surface
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->create([
  'type' => 'offer', 
  'title' => 'Test Surface',
  'field_surface' => [
    'value' => 100.50,
    'unit' => 'M2',
    'type' => 'APPT',
    'nature' => 'INT',
    'qualification' => 'DISPO'
  ]
]); 
\$node->save(); 
\$loaded = \Drupal::entityTypeManager()->getStorage('node')->load(\$node->id()); 
\$surface = \$loaded->get('field_surface'); 
echo 'Value: ' . \$surface->value . ', Unit: ' . \$surface->unit;"
# Résultat attendu: "Value: 100.50, Unit: M2" ✓
```

### Test via UI
1. Éditer un contenu de type Offer
2. Saisir dans le champ Surface :
   - Valeur : 100.50
   - Unité : M2
   - Type : APPT
   - Nature : INT
   - Qualification : DISPO
3. Enregistrer
4. Vérifier la valeur :
```bash
drush ev "$node = \Drupal::entityTypeManager()->getStorage('node')->load(3); 
$surface = $node->get('field_surface'); 
echo 'Value: ' . $surface->value . ', Unit: ' . $surface->unit . ', Type: ' . $surface->type;"
# Résultat: "Value: 100.50, Unit: M2, Type: APPT" ✓
```

## Similarité avec le fix ps_dictionary

Ce problème est **identique** à celui rencontré avec le champ `ps_dictionary` :
- **Même cause:** Structure imbriquée dans le widget vs structure plate attendue par le champ
- **Même solution:** Implémentation de `massageFormValues()` pour aplatir la structure
- **Leçon:** Tout widget utilisant des containers imbriqués doit implémenter `massageFormValues()`

### Comparaison

| Aspect | ps_dictionary | ps_surface |
|--------|--------------|------------|
| **Problème** | Surcharge de `#options` + structure imbriquée | Structure imbriquée (containers inline) |
| **Symptôme** | Valeur NULL après save | Toutes propriétés NULL après save |
| **Solution** | `massageFormValues()` + `allowed_values_function` | `massageFormValues()` uniquement |
| **Complexité** | Moyenne (2 fixes) | Simple (1 fix) |

## Pattern de développement

### Quand implémenter massageFormValues() ?

**Toujours implémenter `massageFormValues()` si :**

1. Vous utilisez des containers (`#type => 'container'`) pour organiser les sous-champs
2. Vous utilisez `container-inline` pour l'affichage horizontal
3. Vous utilisez des fieldsets ou détails imbriqués
4. La structure du formulaire ne correspond pas exactement au schéma du champ

### Template pour massageFormValues()

```php
public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
  $massaged = [];
  
  foreach ($values as $delta => $value) {
    // Extraire les valeurs de la structure imbriquée
    $massaged[$delta] = [
      'property1' => $value['container1']['property1'] ?? NULL,
      'property2' => $value['container2']['property2'] ?? NULL,
      // ... autres propriétés
    ];
  }
  
  return $massaged;
}
```

### Debugging massageFormValues()

Pour déboguer la structure des valeurs soumises :

```php
public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
  // Debug: afficher la structure reçue
  \Drupal::logger('MODULE_NAME')->debug('Received values: @values', [
    '@values' => print_r($values, TRUE),
  ]);
  
  $massaged = /* ... transformation ... */;
  
  // Debug: afficher la structure transformée
  \Drupal::logger('MODULE_NAME')->debug('Massaged values: @values', [
    '@values' => print_r($massaged, TRUE),
  ]);
  
  return $massaged;
}
```

Puis consulter les logs :
```bash
drush wd-show --type=MODULE_NAME --count=10
```

## Fichiers modifiés

### SurfaceDefaultWidget.php
- **Ajout:** Méthode `massageFormValues()` pour transformer la structure imbriquée en structure plate
- **Impact:** 18 lignes ajoutées
- **Emplacement:** `src/web/modules/custom/ps/ps_division/src/Plugin/Field/FieldWidget/SurfaceDefaultWidget.php`

## Leçons apprises

### 1. Organisation visuelle vs structure de données
Les containers sont excellents pour l'UX (regroupement visuel, inline layout) mais créent une déconnexion entre la structure du formulaire et celle attendue par le système de champs.

### 2. massageFormValues() est essentiel
C'est le pont entre la présentation (formulaire) et les données (champ). Ne pas l'implémenter quand nécessaire = valeurs perdues.

### 3. Tester la persistence systématiquement
Lors du développement d'un widget personnalisé :
1. Tester l'affichage (valeurs par défaut correctes ?)
2. Tester la soumission (valeurs soumises ?)
3. **Tester la persistence** (valeurs en base après save ?)
4. Tester le rechargement (valeurs affichées après reload ?)

### 4. Pattern reproductible
Ce fix s'applique à **tous** les champs multi-propriétés avec widgets structurés :
- `ps_surface` (5 propriétés)
- `ps_price` (4 propriétés : amount, currency, unit, period)
- `ps_diagnostic` (2 propriétés : value, type)
- Tout futur champ complexe

## Recommandations

### Pour les développeurs
1. **Toujours** implémenter `massageFormValues()` pour les widgets avec structure imbriquée
2. Ajouter des tests automatisés pour vérifier la persistence
3. Documenter la structure attendue dans les commentaires du widget

### Pour les prochains champs
Vérifier systématiquement les widgets de :
- `ps_price` (PriceDefaultWidget)
- `ps_diagnostic` (DiagnosticDefaultWidget)
- Tout champ avec plusieurs propriétés

Appliquer le même fix si nécessaire.

## Références

- [WidgetInterface::massageFormValues()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetInterface.php/function/WidgetInterface%3A%3AmassageFormValues)
- [Fix ps_dictionary](./ps_dictionary_field_fix.md) - Problème similaire avec solution
- [Form API - Container](https://api.drupal.org/api/drupal/elements/container)
- [Field API - Multi-value fields](https://www.drupal.org/docs/drupal-apis/entity-api/fields-and-field-types)
