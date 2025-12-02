# Module ps_agent

> **Layer**: Business  
> **Dependencies**: ps, ps_dictionary  
> **Status**: Production Ready

## Overview

The `ps_agent` module provides a content entity for managing real estate agents with CRM synchronization and BO-protected fields. It handles agent data imports from external CRM systems while preserving BO-specific edits.

### Key Features

- **Content Entity**: Full-featured `agent` entity with 15+ base fields
- **CRM/BO Field Protection**: Configure which fields are preserved during CRM imports
- **Agent Lookup Service**: Centralized `AgentManager` for queries and business logic
- **Admin UI**: Complete CRUD interface at `/admin/ps/structure/agents`
- **Settings Configuration**: Field protection rules at `/admin/ps/config/agents`

## Architecture Position

```
ps_agent (Business Layer)
  ↓ depends on
ps (Foundation) + ps_dictionary (Foundation)
```

**Consumed by**: `ps_offer` (agent references), `ps_import` (CRM synchronization)

## Installation

### Requirements

- Drupal 11+
- PHP 8.3+
- PropertySearch modules: `ps`, `ps_dictionary`
- Drupal core modules: `field`, `text`, `image`, `telephone`

### Install

```bash
# Enable the module
drush en ps_agent -y

# Clear cache
drush cr
```

## Entity Structure

### Agent Entity (`agent`)

**Machine name**: `agent`  
**Base table**: `agent`  
**Data table**: `agent_field_data`  
**Translatable**: Yes  
**Revisionable**: No

#### Base Fields

| Field | Type | CRM Locked | BO Editable | Description |
|-------|------|------------|-------------|-------------|
| `id` | integer | - | - | Primary key |
| `uuid` | uuid | - | - | Universal ID |
| `external_id` | string | ✓ | ✗ | CRM identifier (unique) |
| `first_name` | string | ✓ | ✗ | First name |
| `last_name` | string | ✓ | ✗ | Last name (entity label) |
| `email` | email | ✗ | ✓ | Email address |
| `phone` | telephone | ✗ | ✓ | Phone number |
| `fax` | telephone | ✗ | ✓ | Fax number |
| `photo` | image | ✓ | ✗ | Agent photo |
| `created` | created | - | - | Creation timestamp |
| `changed` | changed | - | - | Modification timestamp |

## Usage

### Service: AgentManager

**Service ID**: `ps_agent.manager`  
**Interface**: `\Drupal\ps_agent\Service\AgentManagerInterface`

#### Examples

```php
// Get the service
$agentManager = \Drupal::service('ps_agent.manager');

// Get all agents
$agents = $agentManager->getActiveAgents();

// Find agent by CRM external ID
$agent = $agentManager->getAgentByExternalId('CRM-12345');

// Find agent by email
$agent = $agentManager->getAgentByEmail('john.doe@example.com');

// Get multiple agents by external IDs
$agents = $agentManager->getAgentsByExternalIds(['CRM-1', 'CRM-2']);

// Check if agent exists
if ($agentManager->agentExists('CRM-12345')) {
  // ...
}

// Get formatted name
$formattedName = $agentManager->getFormattedName($agent);
// Returns: "Doe John"

// Check if field is BO editable
if ($agentManager->isBoEditableField('email')) {
  // Field should be preserved during CRM import
}
```

### Entity Operations

```php
// Create an agent
$agent = \Drupal\ps_agent\Entity\Agent::create([
  'external_id' => 'CRM-12345',
  'first_name' => 'John',
  'last_name' => 'Doe',
  'email' => 'john.doe@example.com',
  'phone' => '+33 1 23 45 67 89',
]);
$agent->save();

// Load an agent
$agent = \Drupal::entityTypeManager()
  ->getStorage('agent')
  ->load($agent_id);

// Update agent
$agent->set('email', 'new.email@example.com');
$agent->set('phone', '+33 6 12 34 56 78');
$agent->save();

// Get agent properties
$externalId = $agent->getExternalId();
$firstName = $agent->getFirstName();
$lastName = $agent->getLastName();
$label = $agent->label(); // Returns "Doe John"
$email = $agent->getEmail();
$phone = $agent->getPhone();
```

### Drush Commands

```bash
# List all agents
drush eval "print_r(array_map(fn(\$a) => \$a->label(), \Drupal::service('ps_agent.manager')->getActiveAgents()));"

# Find agent by external ID
drush eval "print_r(\Drupal::service('ps_agent.manager')->getAgentByExternalId('CRM-12345'));"
```

## Configuration

### Agent Settings

Navigate to: **Configuration > PropertySearch > Agent Settings**  
Route: `/admin/ps/config/agents`  
Permission: `administer agent entities`

#### Settings

**CRM / BO Field Protection**:
- Configure which fields are editable in BO
- BO editable fields are preserved during CRM imports
- Default BO fields: `email`, `phone`, `fax`

**Validation Rules**:
- Email required (default: enabled)
- Phone required (default: disabled)
- Phone format validation (default: disabled)

## CRM/BO Integration

### Field Protection Strategy

During CRM imports, the system protects BO-editable fields:

1. **CRM Locked Fields**: Always overwritten by CRM data
   - `first_name`, `last_name`, `external_id`, `photo`

2. **BO Editable Fields**: Preserved during imports
   - `email`, `phone`, `fax`
   - Configurable via settings form

### Import Process Plugin

For import integration, use the `crm_bo_field_guard` process plugin (see `ps_import` module):

```yaml
process:
  email:
    plugin: crm_bo_field_guard
    source: EMAIL
    bo_fields: ['email', 'phone', 'fax']
```

This plugin checks if the agent exists and preserves BO-editable field values.

## Permissions

| Permission | Label | Description |
|------------|-------|-------------|
| `administer agent entities` | Administer agents | Full admin access + settings |
| `view agent entities` | View agents | View agent entities |
| `create agent entities` | Create agents | Create new agents |
| `edit agent entities` | Edit agents | Edit existing agents |
| `delete agent entities` | Delete agents | Delete agents |
| `edit agent internal data` | Edit internal data | Edit BO-specific fields |

## Admin Routes

| Route | Path | Description |
|-------|------|-------------|
| `entity.agent.collection` | `/admin/ps/structure/agents` | Agent list |
| `entity.agent.add_form` | `/admin/ps/structure/agents/add` | Add agent |
| `entity.agent.edit_form` | `/admin/ps/structure/agents/{agent}/edit` | Edit agent |
| `entity.agent.delete_form` | `/admin/ps/structure/agents/{agent}/delete` | Delete agent |
| `ps_agent.settings` | `/admin/ps/config/agents` | Settings form |

## Testing

### Run Tests

```bash
# Unit tests
vendor/bin/phpunit web/modules/custom/ps_agent/tests/src/Unit/

# Kernel tests
vendor/bin/phpunit web/modules/custom/ps_agent/tests/src/Kernel/

# All tests
vendor/bin/phpunit web/modules/custom/ps_agent/
```

### Test Coverage

- ✅ **Unit**: `AgentManagerTest` - Service logic
- ✅ **Kernel**: `AgentTest` - Entity CRUD operations
- ✅ **Functional**: `AgentCRUDTest` - Full UI workflow

## Specifications

- **Module spec**: `docs/modules/ps_agent.md`
- **Data model**: `docs/02-modele-donnees-drupal.md#43-entité-ps_agent`
- **Generation prompt**: `docs/prompts/06-prompt-ps_agent.md`

## Standards Compliance

This module follows all PropertySearch standards:

- ✅ PHP 8.3+ with `declare(strict_types=1)`
- ✅ PHP 8 attributes (no legacy annotations)
- ✅ Dependency injection (no `\Drupal::service()` in services)
- ✅ Full PHPDoc with `@see` references to specs
- ✅ Config schemas for all configuration
- ✅ Complete test coverage
- ✅ PHPCS + PHPStan compliant

## Troubleshooting

### Import Not Preserving BO Fields

**Problem**: CRM import overwrites email/phone edited in BO

**Solution**: Check that:
1. Fields are enabled in `/admin/ps/config/agents`
2. Import process uses `crm_bo_field_guard` plugin
3. Agent has `external_id` set for lookup

### Display Name Not Calculating

**Problem**: Display name shows "Agent 123" instead of name

**Solution**: 
- Ensure `first_name` and `last_name` are set
- Entity label is auto-calculated as "LAST_NAME FIRST_NAME"
- Clear cache: `drush cr`

## Related Modules

- **ps**: Foundation services and base classes
- **ps_dictionary**: Business dictionaries (if using language codes)
- **ps_offer**: References agents via entity_reference field
- **ps_import**: CRM synchronization with field protection

## Maintainers

PropertySearch Development Team

## License

Proprietary - PropertySearch Platform
