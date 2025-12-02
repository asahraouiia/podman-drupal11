# PropertySearch Diagnostic Module

Provides diagnostic field type and configuration entities for regulatory diagnostics (DPE, GES) with automatic class calculation, visual display, and dimmed state for incomplete data.

## Purpose

The `ps_diagnostic` module is a **Domain layer** module that provides:

- **Structured field type** for storing diagnostic data (energy performance, emissions, technical indicators)
- **Config entities** for diagnostic type configuration (DPE, GES with color tables and ranges)
- **Automatic class calculation** from numeric values using configured ranges
- **Visual display** with colored energy class bars (horizontal SVG/vertical/compact layouts)
- **Dimmed state rendering** for diagnostics with missing data (configurable opacity)
- **Services** for normalization, completeness scoring, search indexing, and comparison

## Architecture

**Layer**: Domain  
**Dependencies**: `ps` (Foundation)  
**Consumed by**: `ps_offer` (Business), `ps_import` (Business), `ps_search` (Functional)

## Features

### Config Entity: `ps_diagnostic_type`

Configurable diagnostic types with class ranges and colors:

**Default types installed**:
- **DPE** (Consommations énergétiques): A-G classes with green→red gradient (kWh/m²/an)
- **GES** (Émissions de gaz à effet de serre): A-G classes with lavender→violet gradient (kg CO₂/m²/an)

**Admin UI**: `/admin/ps/structure/diagnostic-types`

Each type defines:
- `id`: Machine name (e.g., `dpe`, `ges`)
- `label`: Human-readable label
- `unit`: Unit of measurement
- `classes`: Array of 7 classes (A-G) with:
  - `label`: Class letter (A-G)
  - `color`: Hex color code (#RRGGBB)
  - `range_max`: Maximum value for this class (NULL for last class)

### Field Type: `ps_diagnostic`

Structured field with **7 subfields**:

| Subfield | Type | Description |
|----------|------|-------------|
| `type_id` | string | Diagnostic type ID (e.g., `dpe`, `ges`) |
| `value_numeric` | float | Numeric diagnostic value |
| `label_code` | string | Energy class label (A-G, auto-calculated if empty) |
| `valid_from` | string | Start date of validity (ISO 8601) |
| `valid_to` | string | End date of validity (ISO 8601) |
| `no_classification` | boolean | Special state: not classified (displays "?") |
| `non_applicable` | boolean | Special state: not applicable (displays "N/A") |

**Field Behavior**:
- **isEmpty()**: Returns `TRUE` only when `type_id` is empty OR all other fields are empty (dates, value, class, flags)
- **setValue()**: Normalizes empty strings to NULL, casts numerics to float, converts booleans
- **Validation**: Type ID checked against config entities, label_code restricted to A-G

### Services

#### DiagnosticClassCalculator

**Service ID**: `ps_diagnostic.class_calculator`

**NEW**: Calculates energy class from numeric value using config entity ranges.

```php
$calculator = \Drupal::service('ps_diagnostic.class_calculator');

// Calculate class from value.
$class = $calculator->calculateClass('dpe', 200.0); // Returns 'D'

// Get full display info (class, color, unit, display_text, is_special).
$data = [
  'type_id' => 'dpe',
  'value_numeric' => 200.0,
  'label_code' => NULL,
  'no_classification' => FALSE,
  'non_applicable' => FALSE,
];
$displayInfo = $calculator->getDisplayInfo($data);
// Returns: ['class' => 'D', 'color' => '#F7941D', 'unit' => 'kWh/m²/an', 'display_text' => 'D', 'is_special' => FALSE]
```

#### DiagnosticNormalizer

**Service ID**: `ps_diagnostic.normalizer`

Validates and normalizes diagnostic data:

- Type ID validation (checks config entity exists)
- Numeric value conversion (negatives → NULL)
- Auto-calculates `label_code` if empty (via DiagnosticClassCalculator)
- Date coherence checks
- Boolean flag normalization

```php
$normalizer = \Drupal::service('ps_diagnostic.normalizer');
$normalized = $normalizer->normalize([
  'type_id' => 'dpe',
  'value_numeric' => 200.0,
  'label_code' => NULL, // Will be auto-calculated as 'D'
]);
```

#### CompletenessCalculator

**Service ID**: `ps_diagnostic.completeness_calculator`

Calculates completeness score (0-100) based on field presence with new weighted formula:

- `type_id`: 30%
- `value_numeric`: 25%
- `label_code`: 20%
- `valid_from`: 15%
- `valid_to`: 10%

```php
$calculator = \Drupal::service('ps_diagnostic.completeness_calculator');
$score = $calculator->calculateScore([
  'type_id' => 'dpe',
  'value_numeric' => 200.0,
  'label_code' => 'D',
  'valid_from' => '2024-01-01',
  'valid_to' => '2034-01-01',
]); // Returns 100
```

#### SearchMapper

**Service ID**: `ps_diagnostic.search_mapper`

Extracts diagnostic data for search indexing (changed `type_codes` → `type_ids`).

```php
$mapper = \Drupal::service('ps_diagnostic.search_mapper');
$searchData = $mapper->mapForSearch($diagnostics);
// Returns: ['type_ids' => ['dpe', 'ges'], 'label_codes' => ['D', 'A'], 'values_numeric' => [200.0, 10.2], 'max_completeness_score' => 100]
```

#### CompareBuilder

**Service ID**: `ps_diagnostic.compare_builder`

Builds structured data for comparison display (removed `unit` field, uses `type_id`).

```php
$builder = \Drupal::service('ps_diagnostic.compare_builder');
$compareData = $builder->buildCompareData($diagnostics);
// Returns: ['energy' => ['D', 'A'], 'values' => [['value' => 200.0, 'type_id' => 'dpe']], 'completeness' => 100, 'validity' => [...]]
```

### Visual Display

**Formatter**: `DiagnosticDefaultFormatter`

Provides colored energy class bars with **3 layout options** and **dimmed state** for incomplete diagnostics:

#### Layouts

1. **Horizontal** (default): Official DPE/GES style with SVG horizontal bar, all classes visible, black cursor on current class, numeric value legend below
2. **Vertical**: List format with left border colored
3. **Compact**: Inline badge with minimal spacing

#### Formatter Settings

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `show_value` | boolean | TRUE | Display numeric value with unit |
| `show_dates` | boolean | FALSE | Display validity period (from/to) |
| `layout` | string | 'horizontal' | Layout mode: horizontal/vertical/compact |
| `dim_empty` | boolean | TRUE | Apply dimmed state when no value and no class |
| `dim_opacity` | integer | 30 | Opacity percentage (10-90) for dimmed state |

#### Dimmed State

When a diagnostic has `type_id` set but **neither** `value_numeric` **nor** `label_code`:
- SVG bar group (`<g id="bar">`) gets reduced opacity (default 30%)
- Cursor displays centered with "?" character
- No legend/value shown
- Indicates incomplete/pending diagnostic data

**Example use case**: Real estate offer has GES type assigned but diagnostic not yet performed → displays grayed-out bar with "?" to indicate missing data.

**Configuration**: Admin can adjust opacity (10-90%) and enable/disable dimmed state via display settings.

**CSS classes**: `.ps-diagnostic--horizontal`, `.ps-diagnostic--vertical`, `.ps-diagnostic--compact`, `.ps-diagnostic--empty` (dimmed state), `.ps-diagnostic__special` (for N/A and ?)

## Installation

1. Enable dependencies first:

   ```bash
   drush en ps -y
   ```

2. Enable ps_diagnostic:

   ```bash
   drush en ps_diagnostic -y
   ```

3. Clear cache:

   ```bash
   drush cr
   ```

4. Verify config entities installed:

   ```bash
   drush php:eval "print_r(\\Drupal::entityTypeManager()->getStorage('ps_diagnostic_type')->loadMultiple());"
   ```

   Should show `dpe` and `ges` entities with classes/colors.

## Configuration

**Settings**: `/admin/ps/config/diagnostic`

Available settings:

- **Surface rounding precision**: Decimal places for surface display (0-4)
- **Enable completeness score**: Calculate and display completeness scores
- **Available compliance flags**: Define possible compliance flags (one per line)

**Config Entity Management**: `/admin/ps/structure/diagnostic-types`

Manage diagnostic types (DPE, GES, etc.) including classes, colors, and ranges.

## Usage

### Adding Field to Entity

```php
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Create field storage.
FieldStorageConfig::create([
  'field_name' => 'field_diagnostics',
  'entity_type' => 'node',
  'type' => 'ps_diagnostic',
  'cardinality' => -1, // Unlimited.
])->save();

// Create field instance.
FieldConfig::create([
  'field_name' => 'field_diagnostics',
  'entity_type' => 'node',
  'bundle' => 'ps_offer',
  'label' => 'Diagnostics',
  'settings' => [
    'enable_validation' => TRUE,
  ],
])->save();
```

### Programmatic Field Value

```php
$offer->field_diagnostics[] = [
  'type_id' => 'dpe',
  'value_numeric' => 120.5,
  'label_code' => NULL, // Auto-calculated as 'B'
  'valid_from' => '2024-01-01',
  'valid_to' => '2034-01-01',
  'no_classification' => FALSE,
  'non_applicable' => FALSE,
];
$offer->save();

// Special states
$offer->field_diagnostics[] = [
  'type_id' => 'dpe',
  'no_classification' => TRUE, // Displays "?"
];

$offer->field_diagnostics[] = [
  'type_id' => 'ges',
  'non_applicable' => TRUE, // Displays "N/A"
];
```

### Using Services

```php
// Normalize diagnostic data.
$normalizer = \Drupal::service('ps_diagnostic.normalizer');
$normalized = $normalizer->normalize([
  'type_code' => 'DPE',
  'label_code' => 'B',
  'value_numeric' => -10, // Will be converted to NULL.
]);

// Calculate completeness score.
$calculator = \Drupal::service('ps_diagnostic.completeness_calculator');
$score = $calculator->calculateScore($normalized);

// Map for search.
$mapper = \Drupal::service('ps_diagnostic.search_mapper');
$searchData = $mapper->mapForSearch($offer->field_diagnostics->getValue());

// Build comparison data.
$builder = \Drupal::service('ps_diagnostic.compare_builder');
$compareData = $builder->buildCompareData($offer->field_diagnostics->getValue());
```

## Config Entity Management

### Creating New Diagnostic Type

**Admin UI**: `/admin/ps/structure/diagnostic-types/add`

**Programmatically**:

```php
use Drupal\ps_diagnostic\Entity\PsDiagnosticType;

PsDiagnosticType::create([
  'id' => 'asbestos',
  'label' => 'Diagnostic Amiante',
  'unit' => 'présence',
  'classes' => [
    'positive' => ['label' => 'Présent', 'color' => '#ED1C24', 'range_max' => NULL],
    'negative' => ['label' => 'Absent', 'color' => '#00A651', 'range_max' => NULL],
  ],
])->save();
```

### Editing Existing Type

```bash
drush config:edit ps_diagnostic.type.dpe
```

Modify `classes` array to adjust colors or ranges, then:

```bash
drush cr
```

## Testing

### Unit Tests

**Total**: 29 tests covering 7 classes:

```bash
# Run all diagnostic tests
vendor/bin/phpunit web/modules/custom/ps_diagnostic/tests

# Expected output: OK (29 tests, 70+ assertions)
```

**Test coverage**:

- `DiagnosticClassCalculatorTest`: Class calculation, special states, display info (5 tests)
- `DiagnosticNormalizerTest`: Validation, auto-calculation, date checks (4 tests)
- `CompletenessCalculatorTest`: Score calculation, field weights (5 tests)
- `SearchMapperTest`: Indexing data extraction (3 tests)
- `CompareBuilderTest`: Comparison data building (3 tests)
- `DiagnosticItemTest`: isEmpty(), setValue(), normalization (11 tests)
- `DiagnosticDefaultFormatterTest`: Settings, dimmed state logic (6 tests)

### Drush Commands

```bash
# Check field definitions
drush field:list ps_offer

# Inspect field storage
drush sqlq "DESCRIBE node__field_diagnostics"
```

## Permissions

- **administer ps_diagnostic**: Configure diagnostic settings and manage diagnostic data

## API Reference

### Interfaces

- `DiagnosticNormalizerInterface`: Validation and normalization
- `CompletenessCalculatorInterface`: Score calculation
- `SearchMapperInterface`: Search indexing mapping
- `CompareBuilderInterface`: Comparison data building

### Field Type

- `DiagnosticItem`: Field type implementation with validation constraints

### Widget & Formatter

- `DiagnosticDefaultWidget`: Default form widget
- `DiagnosticDefaultFormatter`: Default display formatter with configurable output

## Documentation

- **Functional Spec**: `docs/specs/07-ps-diagnostic.md`
- **Data Model**: `docs/02-modele-donnees-drupal.md#3-field-types-custom`
- **Contributing**: `docs/dev/CONTRIBUTING.md`
- **Drupal Standards**: `docs/dev/DRUPAL_STANDARDS.md`

## Quality Gates

```bash
# PHPCS - Code standards
vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/ps_diagnostic

# Auto-fix
vendor/bin/phpcbf --standard=Drupal,DrupalPractice web/modules/custom/ps_diagnostic

# PHPStan - Static analysis
vendor/bin/phpstan analyse web/modules/custom/ps_diagnostic --level=max

# PHPUnit - Unit tests
vendor/bin/phpunit web/modules/custom/ps_diagnostic/tests
```

## Troubleshooting

### Invalid diagnostic type

If validation fails with "Invalid diagnostic type ID", ensure:

1. Config entity exists at `/admin/ps/structure/diagnostic-types`
2. Machine name matches (lowercase, e.g., `dpe`, not `DPE`)
3. Cache is cleared (`drush cr`)

### Class not auto-calculating

Check:

1. `type_id` and `value_numeric` are both provided
2. Config entity has valid `classes` with `range_max` values
3. Ensure ranges are sorted (A=lowest, G=highest)

### Visual display not showing colors

Verify:

1. Library attached: Check formatter settings
2. CSS loaded: Inspect element for `.ps-diagnostic` classes
3. Template exists: `templates/ps-diagnostic-display.html.twig`

## Roadmap

- Historical tracking of diagnostic changes
- Dashboard widgets for energy class distribution
- Advanced coherence rules (cross-field validation)
- Integration with external diagnostic APIs

## License

GPL-2.0+

## Maintainer

PropertySearch Project Team
