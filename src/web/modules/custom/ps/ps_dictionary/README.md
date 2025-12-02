# PropertySearch Dictionary Module

Business dictionary management with centralized code validation and resolution.

## Overview

The `ps_dictionary` module provides a centralized system for managing business dictionaries used throughout the PropertySearch platform. It handles validation, resolution, and localization of business codes such as property types, transaction types, offer statuses, and more.

## Features

- **12 Pre-configured Dictionary Types**: Property types, transaction types, offer statuses, currencies, price units, price periods, surface units, surface types, diagnostic types, diagnostic statuses, agent roles, and feature groups
- **47 Pre-configured Entries**: Complete set of business codes ready to use
- **Config Entities**: Both dictionary types and entries are configuration entities for easy export/import
- **Cached Resolution**: O(1) lookups after first load with intelligent cache invalidation
- **Draggable Sorting**: Weight-based ordering with drag-and-drop UI
- **Status Management**: Active/inactive and deprecated flags for entries
- **Metadata Support**: Extensible metadata storage for custom attributes (symbols, conversion factors, etc.)
- **Settings Form**: Configure import behavior and deprecation policy
- **Drush Commands**: CLI tools for listing, exporting, and cache management
- **Full UI**: Admin listing at `/admin/ps/structure/dictionaries` and settings at `/admin/ps/config/dictionary`

## Architecture

### Layer
**Foundation** - Depends only on `ps` (core) module

### Dependencies
- Drupal Core 11+
- `ps` module (PropertySearch foundation)

### Service
- **`ps_dictionary.manager`** (`DictionaryManagerInterface`)
  - `isValid(string $type, string $code): bool`
  - `getLabel(string $type, string $code): ?string`
  - `getOptions(string $type, bool $activeOnly = TRUE): array`
  - `getEntry(string $type, string $code): ?DictionaryEntryInterface`
  - `getEntries(string $type, bool $activeOnly = TRUE): array`
  - `isDeprecated(string $type, string $code): bool`
  - `getMetadata(string $type, string $code): array`
  - `clearCache(?string $type = NULL): void`

### Entities

#### DictionaryType (Config Entity)
- **ID**: Machine name (e.g., `property_type`)
- **Label**: Human-readable name
- **Description**: Optional description
- **is_translatable**: Whether entries support translation
- **Metadata**: Custom attributes

#### DictionaryEntry (Config Entity)
- **ID**: Composite `{type}.{code}` (e.g., `property_type.SALE`)
- **dictionary_type**: Parent type ID
- **code**: Machine code (e.g., `SALE`, `RENT`)
- **label**: Human-readable label
- **description**: Optional description
- **weight**: Sort order (lower = first)
- **status**: Active/inactive flag
- **deprecated**: Deprecation flag
- **metadata**: Custom attributes (e.g., currency symbol)

## Installation

```bash
# Enable module
drush en ps_dictionary -y

# Clear cache
drush cr

# Verify installation
drush ps:dictionary-list
```

## Usage

### In Code (Services)

```php
// Inject service
public function __construct(
  private readonly DictionaryManagerInterface $dictionaryManager,
) {}

// Validate code
if ($this->dictionaryManager->isValid('property_type', 'SALE')) {
  // Code is valid and active
}

// Get label
$label = $this->dictionaryManager->getLabel('property_type', 'SALE');
// Returns: "For Sale"

// Get form options
$options = $this->dictionaryManager->getOptions('property_type');
// Returns: ['SALE' => 'For Sale', 'RENT' => 'For Rent', ...]

// Get entry with metadata
$entry = $this->dictionaryManager->getEntry('currency', 'EUR');
$symbol = $entry->getMetadata()['symbol'] ?? '€';

// Check if deprecated
if ($this->dictionaryManager->isDeprecated('property_type', 'OLD_CODE')) {
  // Handle deprecated code
}
```

### Drush Commands

```bash
# List all dictionary types
drush ps:dictionary-list

# Show entries for a type
drush ps:dictionary-show property_type

# Export dictionary as YAML
drush ps:dictionary-export property_type

# Export as JSON
drush ps:dictionary-export property_type --format=json

# Clear cache
drush ps:dictionary-cache-clear
drush ps:dictionary-cache-clear property_type
```

### Admin UI

1. **Dictionary Types**: `/admin/ps/dictionaries`
   - List all dictionary types
   - Add/edit/delete types
   - View entry count per type

2. **Dictionary Entries**: `/admin/ps/dictionaries/{type}/entries`
   - Draggable list for reordering
   - Add/edit/delete entries
   - Filter by status

## Pre-configured Dictionaries

### 1. property_type
- **SALE**: For Sale
- **RENT**: For Rent
- **SALE_RENT**: Sale or Rent
- **TEMP_RENT**: Temporary Rent

### 2. transaction_type
- **OFFICE**: Office
- **LOGISTICS**: Logistics
- **RETAIL**: Retail
- **COWORKING**: Coworking

### 3. offer_status
- **AVAILABLE**: Available
- **UNDER_OFFER**: Under Offer
- **SOLD**: Sold
- **WITHDRAWN**: Withdrawn

### 4. currency
- **EUR**: Euro (€)
- **USD**: US Dollar ($)
- **GBP**: British Pound (£)

### 5. price_unit (4 entries)
- **PER_M2_Y**: €/m²/year
- **PER_M2_M**: €/m²/month
- **PER_LOT_Y**: €/lot/year
- **GLOBAL_Y**: €/year (global)

### 6. price_period (4 entries)
- **YEAR**: Per year
- **MONTH**: Per month
- **QUARTER**: Per quarter
- **WEEK**: Per week

### 7. surface_unit (3 entries)
- **M2**: m² (Square meters)
- **SQFT**: sq ft (Square feet)
- **HECTARE**: ha (Hectares)

### 8. surface_type (4 entries)
- **USABLE**: Usable Area
- **GLA**: Gross Leasable Area
- **NIA**: Net Internal Area
- **GROSS**: Gross Area

### 9. diagnostic_type (6 entries)
- **DPE**: Energy Performance
- **GES**: Greenhouse Gas
- **ASBESTOS**: Asbestos
- **LEAD**: Lead (CREP)
- **TERMITES**: Termites
- **ELECTRICAL**: Electrical Installation

### 10. diagnostic_status (4 entries)
- **VALID**: Valid
- **EXPIRED**: Expired
- **PENDING**: Pending
- **NOT_REQUIRED**: Not Required

### 11. agent_role (3 entries)
- **LISTING**: Listing Agent
- **TRANSACTION**: Transaction Agent
- **PROPERTY_MANAGER**: Property Manager

### 12. feature_group (4 entries)
- **EQUIPMENTS**: Equipments (icon: tools)
- **SERVICES**: Services (icon: service)
- **BUILDING_CONDITION**: Building condition (icon: building)
- **MORE_INFORMATION**: More information (icon: info)

**Total: 43 pre-configured entries**

## Performance

- **First load**: Query database + populate cache
- **Subsequent loads**: O(1) cache retrieval
- **Cache duration**: 1 hour (configurable)
- **Cache invalidation**: Automatic on entry save/delete

## Permissions

- **administer dictionaries**: Full CRUD access to types and entries
- **view dictionaries**: Read-only access
- **edit dictionary entries**: Edit entries only (not types)

## Settings

Configure dictionary behavior at `/admin/ps/dictionaries/settings`:

### Import Behavior
- **allow_unknown_codes**: Accept unknown codes with warning (default: `false`)
- **auto_create_on_unknown**: Auto-create entries for unknown codes (default: `false`)
- **default_status_new_items**: Status for auto-created entries: `active` or `inactive` (default: `inactive`)

### Deprecation Policy
- **deprecated_policy**: How to handle deprecated entries
  - `soft`: Deprecated entries remain visible but marked (default)
  - `hard`: Deprecated entries excluded from searches and forms

## Configuration Export/Import

```bash
# Export config
drush cex -y

# Files exported to:
# - config/sync/ps_dictionary.type.*.yml
# - config/sync/ps_dictionary.entry.*.yml

# Import config
drush cim -y
```

## Testing

```bash
# Run all tests
vendor/bin/phpunit web/modules/custom/ps_dictionary

# Run unit tests only
vendor/bin/phpunit web/modules/custom/ps_dictionary/tests/src/Unit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage web/modules/custom/ps_dictionary
```

## Extending

### Adding New Dictionary Types

1. Create config file: `config/install/ps_dictionary.type.my_type.yml`
2. Add entries: `config/install/ps_dictionary.entry.my_type.*.yml`
3. Clear cache: `drush cr`

### Custom Metadata

```yaml
# ps_dictionary.entry.currency.EUR.yml
metadata:
  symbol: '€'
  iso_code: 'EUR'
  decimal_places: 2
  countries: ['FR', 'DE', 'IT', 'ES']
```

```php
// Access in code
$entry = $this->dictionaryManager->getEntry('currency', 'EUR');
$metadata = $entry->getMetadata();
$symbol = $metadata['symbol'] ?? '';
$decimals = $metadata['decimal_places'] ?? 2;
```

## Integration

### Used By
- `ps_offer`: Validates property types, transaction types, statuses
- `ps_price`: Validates currencies, units, periods
- `ps_features`: Validates feature dictionary codes
- `ps_diagnostic`: Validates diagnostic types and statuses
- `ps_import`: Resolves CRM codes to canonical codes

### Events
- Dictionary cache invalidation triggers on entity save/delete
- Cache tags: `ps_dictionary:{type_id}`

## Troubleshooting

### Cache Issues
```bash
# Clear specific type
drush ps:dictionary-cache-clear property_type

# Clear all
drush ps:dictionary-cache-clear
drush cr
```

### Missing Entries
```bash
# Verify installation
drush ps:dictionary-list

# Show specific type
drush ps:dictionary-show property_type

# Reimport config
drush cim -y
```

## API Reference

See:
- `DictionaryManagerInterface` - Main service interface
- `DictionaryTypeInterface` - Type entity interface
- `DictionaryEntryInterface` - Entry entity interface
- [Specification](../../docs/specs/03-ps-dictionary.md)

## Contributing

See [CONTRIBUTING.md](../../docs/dev/CONTRIBUTING.md) for development guidelines.

## License

Proprietary - PropertySearch Platform
