# PropertySearch Division Module

## Overview

The **ps_division** module provides a minimal division entity for managing real estate subdivisions (lots, floors, apartments) as simple spatial units within a parent offer or content entity.

### Key Features

- Lightweight `ps_division` content entity with structural classification
- Custom `ps_surface` field type with 5 subfields (value, unit, type, nature, qualification)
- Full dictionary integration via `ps_dictionary` module
- Automatic cache invalidation on parent entity changes
- Surface aggregation services with caching

**Architecture Layer**: Business (depends on ps, ps_dictionary)

---

## Installation

```bash
# Enable dependencies first
vendor/bin/drush en ps ps_dictionary -y

# Enable ps_division
vendor/bin/drush en ps_division -y

# Rebuild cache
vendor/bin/drush cr
```

---

## Entity Structure

### Division Entity Fields

| Field | Type | Description |
|-------|------|-------------|
| `entity_id` | integer (nullable) | Weak reference to parent offer/node |
| `floor` | ps_dictionary | Floor code (dictionary: floor) |
| `building_name` | string | Building name (entity label) |
| `type` | ps_dictionary | Division type (dictionary: surface_type) |
| `nature` | ps_dictionary | Division nature (dictionary: surface_nature) |
| `lot` | string | Lot identifier (alphanumeric) |
| `surfaces` | ps_surface (multi) | Surface measurements collection |
| `availability` | text_long | Multilingual availability notes |

### Surface Field Type (`ps_surface`)

| Subfield | Type | Dictionary | Description |
|----------|------|------------|-------------|
| `value` | decimal(10,2) | - | Numeric surface value |
| `unit` | string | surface_unit | Unit code (M2, HA, etc.) |
| `type` | string | surface_type | Type code (APPT, BUREAU, etc.) |
| `nature` | string | surface_nature | Nature code (INT, EXT, HABIT, etc.) |
| `qualification` | string | surface_qualification | Qualification code (DISPO, LOUE, etc.) |

---

## Usage Examples

### Create Division via Drush

```php
vendor/bin/drush eval "
\$storage = \Drupal::entityTypeManager()->getStorage('ps_division');
\$division = \$storage->create([
  'building_name' => 'Building A',
  'entity_id' => 123, // Parent offer ID
  'lot' => 'LOT-001',
  'floor' => 'R+1',
  'type' => 'APPT',
  'nature' => 'HABIT',
  'availability' => 'Available immediately',
]);

// Add surfaces
\$division->get('surfaces')->appendItem([
  'value' => 50.5,
  'unit' => 'M2',
  'type' => 'HABIT',
  'nature' => 'HABITABLE',
  'qualification' => 'DISPO',
]);

\$division->get('surfaces')->appendItem([
  'value' => 10.0,
  'unit' => 'M2',
  'type' => 'ANEX',
  'nature' => 'CAVE',
  'qualification' => 'DISPO',
]);

\$division->save();
echo 'Created division ID: ' . \$division->id() . PHP_EOL;
echo 'Total surface: ' . \$division->getTotalSurface() . ' m²' . PHP_EOL;
"
```

### Use Division Manager Service

```php
/** @var \Drupal\ps_division\Service\DivisionManagerInterface $manager */
$manager = \Drupal::service('ps_division.manager');

// Get all divisions for parent entity
$divisions = $manager->getByParent(123);

// Calculate total surface
$totalSurface = $manager->calculateTotalSurface(123);

// Validate division
$errors = $manager->validate($division);
if (!empty($errors)) {
  foreach ($errors as $error) {
    \Drupal::messenger()->addError($error);
  }
}

// Get summary
$summary = $manager->getSummary($division);
// Returns: ['id' => 1, 'building_name' => 'Building A', 'type' => 'APPT', ...]
```

### Use Aggregates Service (Cached)

```php
/** @var \Drupal\ps_division\Service\DivisionAggregatesService $aggregates */
$aggregates = \Drupal::service('ps_division.aggregates');

// Get cached total surface
$total = $aggregates->getTotalSurface(123);

// Manually invalidate cache
$aggregates->invalidate(123);
```

---

## Dictionary Configuration

The module requires the following dictionary types to be configured in `ps_dictionary`:

- `floor` - Floor codes (PB, RDC, R+1, R+2, etc.)
- `surface_unit` - Surface units (M2, HA, A, etc.)
- `surface_type` - Surface types (APPT, BUREAU, LOCAL, PARC, etc.)
- `surface_nature` - Surface natures (INT, EXT, HABIT, PROF, etc.)
- `surface_qualification` - Surface qualifications (DISPO, LOUE, RESERVE, etc.)

**Create floor dictionary**:

```bash
vendor/bin/drush eval "
\$storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_type');
if (!\$storage->load('floor')) {
  \$type = \$storage->create([
    'id' => 'floor',
    'label' => 'Floor',
    'description' => 'Floor codes (PB, RDC, R+1, etc.)',
  ]);
  \$type->save();
  echo 'Floor dictionary created.' . PHP_EOL;
}
"
```

---

## Permissions

| Permission | Description |
|------------|-------------|
| `administer ps_division entities` | Create, edit, delete divisions |
| `view division entities` | View division content |

**Grant permissions**:

```bash
vendor/bin/drush role:perm:add administrator "administer ps_division entities,view division entities"
```

---

## Admin UI

Access division management at:

- **Collection**: `/admin/ps/structure/divisions`
- **Add**: `/admin/ps/structure/divisions/add`
- **Edit**: `/admin/ps/structure/divisions/{id}/edit`
- **Delete**: `/admin/ps/structure/divisions/{id}/delete`

---

## Hooks & Events

The module automatically invalidates parent entity cache tags when divisions are created, updated, or deleted via attribute-based hooks:

```php
#[Hook('entity_presave')]
public function entityPresave(EntityInterface $entity): void {
  // Invalidates ps_division_parent:{entity_id} cache tag
}

#[Hook('entity_delete')]
public function entityDelete(EntityInterface $entity): void {
  // Invalidates ps_division_parent:{entity_id} cache tag
}
```

---

## Cache Tags

| Tag | Description |
|-----|-------------|
| `ps_division_list` | All divisions (global) |
| `ps_division_parent:{entity_id}` | Divisions for specific parent |
| `ps_division:{id}` | Individual division |

**Invalidate programmatically**:

```php
\Drupal\Core\Cache\Cache::invalidateTags(['ps_division_parent:123']);
```

---

## Testing

### Manual Smoke Tests

```bash
vendor/bin/drush en ps_division -y && vendor/bin/drush cr
vendor/bin/drush eval "\n$storage = \Drupal::entityTypeManager()->getStorage('ps_division');\n$storage->create(['entity_id' => 1,'building_name' => 'Smoke 1','surfaces' => [ ['value' => 12,'unit' => 'M2'] ]])->save();\n$storage->create(['entity_id' => 1,'building_name' => 'Smoke 2','surfaces' => [ ['value' => 8.5,'unit' => 'M2'] ]])->save();\n$agg = \Drupal::service('ps_division.aggregates');\nprint 'Total: '.$agg->getTotalSurface(1);\n"
```

Expected output includes: `Total: 20.5`

### Automated Tests

Implemented test types:

- Kernel tests: `tests/src/Kernel/DivisionKernelTest.php`, `tests/src/Kernel/DivisionManagerKernelTest.php`
  - Entity creation, surfaces aggregation, cache invalidation, service summary & totals.
- Functional test: `tests/src/Functional/DivisionFormFunctionalTest.php`
  - Form access & submission with minimal fields.

Run tests:

```bash
vendor/bin/phpunit --configuration web/core/phpunit.xml.dist web/modules/custom/ps_division/tests/src/Kernel
vendor/bin/phpunit --configuration web/core/phpunit.xml.dist web/modules/custom/ps_division/tests/src/Functional
```

### Admin View

Administrative View (`views.view.ps_division_admin.yml`) exposes `/admin/ps/structure/divisions/view` with sortable columns (ID, Building, Parent, Type, Nature, Lot).

### Quality Gates

```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/ps_division
vendor/bin/phpstan analyse web/modules/custom/ps_division --level=6 --memory-limit=512M
vendor/bin/phpunit --configuration web/core/phpunit.xml.dist web/modules/custom/ps_division/tests
```

### Future Enhancements

- Kernel test for dictionary invalid codes.
- Functional tests for edit/delete operations.
- Assert View listing row rendering.

---

## Roadmap

### Phase 2 (Future)

- Views integration for division listings
- Dashboard metrics (average surface, qualification distribution)
- Export JSON API for divisions
- Division grouping/merge functionality
- Min/max surface aggregates

---

## Troubleshooting

### Issue: "Base table or view not found: ps_division"

**Solution**: Run entity schema updates:

```bash
vendor/bin/drush updb -y
vendor/bin/drush cr
```

### Issue: "Invalid dictionary code"

**Solution**: Ensure required dictionaries are created:

```bash
# Check existing dictionary types
vendor/bin/drush eval "print_r(array_keys(\Drupal::entityTypeManager()->getStorage('ps_dictionary_type')->loadMultiple()));"

# Create missing types via Drush or admin UI at /admin/ps/config/dictionaries
```

### Issue: Cache not invalidating

**Solution**: Manually clear caches:

```bash
vendor/bin/drush cr
# Or programmatically:
vendor/bin/drush eval "\Drupal::service('ps_division.aggregates')->invalidate(123);"
```

---

## Architecture Compliance

✅ **PHP 8.3**: `declare(strict_types=1)` in all files  
✅ **Attributes**: `#[ContentEntityType]`, `#[FieldType]`, `#[Hook]`  
✅ **DI**: Constructor injection (services); service locator only in hooks  
✅ **Return types**: `static` for fluent interface methods  
✅ **Dictionaries**: All codes validated via `ps_dictionary.manager`  
✅ **Cache**: Proper tagging with `ps_division_parent:{id}`  
✅ **Spec links**: `@see docs/specs/08-ps-division.md` in PHPDoc  

---

## Links

- **Specification**: `docs/specs/08-ps-division.md`
- **Data Model**: `docs/02-modele-donnees-drupal.md#42-entité-ps_division`
- **Standards**: `docs/dev/DRUPAL_STANDARDS.md`
- **Contributing**: `docs/dev/CONTRIBUTING.md`

---

## Maintainers

PropertySearch Development Team  
Module: `ps_division`  
Version: 1.0  
Drupal: ^11
\n+---\n+\n+## Updates (Cleanup Nov 24 2025)\n+\n+### Added Dependencies\n+`ps_division.info.yml` now explicitly declares `drupal:field` and `drupal:text` to reflect runtime field usage (availability text_long field and entity base fields).\n+\n+### Test Environment Notes\n+Functional tests require a Simpletest DB and writable browser output directory:\n+```bash\n+export SIMPLETEST_DB=sqlite://localhost/web/sites/simpletest/test.sqlite\n+mkdir -p web/sites/simpletest/browser_output\n+```\n+Windows PowerShell: `Set-Item -Path Env:SIMPLETEST_DB -Value 'sqlite://localhost/web/sites/simpletest/test.sqlite'`.\n+\n+### Troubleshooting Addition\n+Documented solution for PHPUnit warning about `sites/simpletest/browser_output` not writable.\n+\n+### Next Steps\n+- Add kernel test for invalid dictionary codes (surface_type, surface_nature).\n+- Extend functional tests (edit/delete, admin view assertions).\n+- Consider JSON API or REST export integration.\n+\n+### Quality Gate Status\n+Kernel test suite passing (5 tests, 26 assertions). Functional test pending environment setup.\n*** End Patch
