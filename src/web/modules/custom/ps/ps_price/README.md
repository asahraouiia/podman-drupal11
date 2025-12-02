# PropertySearch Price (`ps_price`)

**Type**: Domain specialized module  
**Layer**: Foundation  
**Dependencies**: `ps`, `ps_dictionary`

## Purpose

Provides a structured price field type with support for:
- Multiple subfields (amount, currency, unit, period)
- Price ranges (amount_to)
- Business flags (on_request, from, VAT excluded, charges included)
- Locale-aware formatting
- Price normalization for search comparison

## Architecture Position

```
┌─────────────────────────────────────┐
│  Business Modules                   │ ← ps_offer, ps_search
├─────────────────────────────────────┤
│  Technical Specialized Modules      │ ← ps_price ← YOU ARE HERE
│  (ps_dictionary, ps_features, etc.) │
├─────────────────────────────────────┤
│  Foundation (ps)                    │
└─────────────────────────────────────┘
```

## Features

### Field Type: `ps_price`

9 subfields stored in database:

| Subfield | Type | Description |
|----------|------|-------------|
| `amount` | decimal(15,2) | Main price amount |
| `amount_to` | decimal(15,2) | Maximum for range (optional) |
| `currency` | string(3) | ISO currency code (EUR, USD, etc.) |
| `unit` | string(64) | Price unit (/m²/an, /lot, etc.) |
| `period` | string(32) | Period (year, month, quarter, week) |
| `is_on_request` | boolean | Price on request flag |
| `is_from` | boolean | Display "From" prefix |
| `is_vat_excluded` | boolean | VAT excluded flag |
| `is_charges_included` | boolean | Charges included flag |

### Widgets

- **`ps_price_default`**: Full form with all fields and checkboxes

### Formatters

- **`ps_price_full`**: Complete display with all flags and units
- **`ps_price_short`**: Simplified display (amount + currency only)

### Services

#### `ps_price.formatter` (PriceFormatterInterface)

Formats price values with locale support.

**Methods**:
```php
// Full formatting with flags
public function format(FieldItemInterface $item, array $options = []): string;

// Short formatting (amount + currency)
public function formatShort(FieldItemInterface $item, array $options = []): string;

// Get normalized value for search
public function getNumericForSearch(FieldItemInterface $item): ?float;
```

**Examples**:
```php
$formatter = \Drupal::service('ps_price.formatter');

// Full format
$formatted = $formatter->format($price_item, [
  'show_currency' => TRUE,
  'show_unit' => TRUE,
  'show_period' => TRUE,
  'show_flags' => TRUE,
]);
// Output: "From 1,250.00 EUR /m²/year (excl. VAT, charges incl.)"

// Short format
$short = $formatter->formatShort($price_item);
// Output: "1,250.00 EUR"

// For search indexing
$numeric = $formatter->getNumericForSearch($price_item);
// Output: 1250.0
```

#### `ps_price.normalizer` (PriceNormalizer)

Normalizes prices to reference unit (€/m²/year) for comparison.

**Methods**:
```php
public function normalize(
  FieldItemInterface $item,
  float $surfaceM2 = 1.0
): ?float;
```

**Examples**:
```php
$normalizer = \Drupal::service('ps_price.normalizer');

// Monthly to yearly
$item->amount = 100;
$item->period = 'month';
$normalized = $normalizer->normalize($item);
// Output: 1200 (100 * 12)

// Global to per m²
$item->amount = 10000;
$item->unit = '/year';
$normalized = $normalizer->normalize($item, 100.0);
// Output: 100 (10000 / 100)
```

## Installation

```bash
# Enable module
drush en ps_price -y

# Clear caches
drush cr
```

## Usage

### Adding Price Field to Content Entity

```php
$fields['price'] = BaseFieldDefinition::create('ps_price')
  ->setLabel(t('Price'))
  ->setDescription(t('Property price with details'))
  ->setDisplayOptions('form', [
    'type' => 'ps_price_default',
    'weight' => 10,
  ])
  ->setDisplayOptions('view', [
    'type' => 'ps_price_full',
    'label' => 'above',
    'weight' => 10,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

### Programmatic Usage

```php
// Set price value
$entity->set('price', [
  'amount' => 1250.50,
  'currency' => 'EUR',
  'unit' => '/m²/an',
  'period' => 'year',
  'is_vat_excluded' => TRUE,
  'is_charges_included' => FALSE,
]);

// Set price range
$entity->set('price', [
  'amount' => 1000,
  'amount_to' => 1500,
  'currency' => 'EUR',
]);

// Set "on request"
$entity->set('price', [
  'is_on_request' => TRUE,
]);

// Get formatted price
$price_item = $entity->get('price')->first();
$formatter = \Drupal::service('ps_price.formatter');
$formatted = $formatter->format($price_item);
```

### Validation

Price field automatically validates:
- `amount_to` must be >= `amount` (if set)
- Uses `PriceItem::validate()` method

## Business Logic

### "On Request" Flag

When `is_on_request = TRUE`:
- Amount is ignored in display
- Formatter returns "On request"
- Numeric search value is NULL

### Price Ranges

When `amount_to` is set:
- Formatter displays: "1,000 - 1,500 EUR"
- Validation ensures `amount_to >= amount`

### Flags Behavior

- `is_from`: Adds "From" prefix
- `is_vat_excluded`: Adds "(excl. VAT)" suffix
- `is_charges_included`: Adds "(charges incl.)" suffix

## Testing

```bash
# Run unit tests
vendor/bin/phpunit web/modules/custom/ps_price/tests/src/Unit/

# Run all tests
vendor/bin/phpunit web/modules/custom/ps_price/
```

**Test Coverage**:
- ✅ `PriceFormatterTest`: Format methods, flags, ranges, on_request
- ✅ `PriceNormalizerTest`: Period conversion, unit conversion

## Performance

- **Field storage**: Indexed on `amount` and `currency`
- **Formatter**: O(1) - simple string concatenation
- **Normalizer**: O(1) - mathematical conversion

## API Reference

### Field Type Plugin

```php
\Drupal\ps_price\Plugin\Field\FieldType\PriceItem
```

### Widget Plugins

```php
\Drupal\ps_price\Plugin\Field\FieldWidget\PriceDefaultWidget
```

### Formatter Plugins

```php
\Drupal\ps_price\Plugin\Field\FieldFormatter\PriceFullFormatter
\Drupal\ps_price\Plugin\Field\FieldFormatter\PriceShortFormatter
```

### Services

```php
\Drupal\ps_price\Service\PriceFormatterInterface
\Drupal\ps_price\Service\PriceFormatter
\Drupal\ps_price\Service\PriceNormalizer
```

## See Also

- **Specification**: `docs/modules/ps_price.md`
- **Prompt**: `docs/prompts/04-prompt-ps_price.md`
- **Architecture**: `docs/04-architecture-technique.md`
- **Related modules**: `ps`, `ps_dictionary`, `ps_offer`

## Standards Compliance

- ✅ PHP 8.3+ with `declare(strict_types=1)`
- ✅ Drupal 11 attributes (not annotations)
- ✅ Dependency injection (no `\Drupal::` in services)
- ✅ Full PHPDoc with `@see` links to specs
- ✅ Unit tests with PHPUnit
- ✅ PHPCS/PHPStan compliant
