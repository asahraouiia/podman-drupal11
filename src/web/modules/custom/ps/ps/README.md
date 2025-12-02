# PropertySearch Module (ps)

**Foundation layer - Technical infrastructure for PropertySearch platform**

## Overview

The `ps` module provides the core technical infrastructure for the PropertySearch Drupal 11 platform. It serves as the foundation layer offering reusable services, plugin systems, and event-driven architecture for all PropertySearch modules.

## Features

### Services

- **SettingsManager** (`ps.settings`): Type-safe configuration access with dot notation support
- **HealthCheckManager** (`ps.health_check`): System health monitoring and diagnostics
- **ValidationRulesEngine** (`ps.validation_rules`): Extensible data validation engine
- **NotificationManager** (`ps.notification`): Multi-channel notification system

### Plugin System

- **DashboardWidget**: Pluggable dashboard widget system using PHP 8.3 attributes
- Plugin manager for widget discovery and management
- Base classes for easy widget development

### Events

- **PropertySearchEvent**: Base class for all PSR-14 events in PropertySearch ecosystem
- Event-driven architecture for decoupled module communication

## Requirements

- **Drupal**: ^11
- **PHP**: 8.3+
- **Dependencies**: drupal:system

## Installation

```bash
# Enable module
drush en ps -y

# Verify services
drush php-eval "dump(\Drupal::service('ps.settings')->get('performance'));"
```

## Configuration

Access configuration at `/admin/ps/config`:

- Performance monitoring settings
- Validation rules configuration
- Notification channel settings

## Usage

### Settings Manager

```php
// Inject service
public function __construct(
  private readonly SettingsManagerInterface $settingsManager,
) {}

// Get setting with dot notation
$threshold = $this->settingsManager->get('performance.slow_request_threshold');

// Check performance monitoring
if ($this->settingsManager->isPerformanceMonitoringEnabled()) {
  // Monitor performance
}
```

### Validation Engine

```php
$rules = [
  'email' => ['required' => TRUE, 'type' => 'string'],
  'age' => ['type' => 'int'],
];

$result = $this->validationEngine->validate($data, $rules);
if (!$result['valid']) {
  // Handle errors
}

// Add custom rule
$this->validationEngine->addRule('custom_rule', function ($value) {
  return $value === 'expected';
});
```

### Dashboard Widgets

Create a new widget:

```php
<?php

declare(strict_types=1);

namespace Drupal\mymodule\Plugin\DashboardWidget;

use Drupal\ps\Plugin\DashboardWidgetBase;
use Drupal\ps\Attribute\DashboardWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[DashboardWidget(
  id: 'my_widget',
  label: new TranslatableMarkup('My Widget'),
  description: new TranslatableMarkup('Custom dashboard widget'),
  category: 'Custom',
)]
class MyWidget extends DashboardWidgetBase {

  public function build(): array {
    return [
      '#markup' => $this->t('Widget content'),
    ];
  }

}
```

### Events

Create custom events:

```php
<?php

declare(strict_types=1);

namespace Drupal\mymodule\Event;

use Drupal\ps\Event\PropertySearchEvent;

final class CustomEvent extends PropertySearchEvent {

  public function __construct(
    public readonly string $data,
  ) {}

  public function getContext(): array {
    return ['data' => $this->data];
  }

}
```

## API Routes

- **Admin Overview**: `/admin/ps` - Platform administration dashboard
- **Settings**: `/admin/ps/config` - Configuration form
- **Health Check**: `/admin/ps/health` - System health status (JSON)

## Permissions

- `access ps administration`: Access admin pages and configuration
- `view ps telemetry`: View health checks and performance metrics

## Architecture

### Module Layer: Foundation

The `ps` module is part of the **Foundation** layer in the PropertySearch architecture:

```
Foundation    → ps (core services), ps_dictionary
Domain        → ps_features, ps_price, ps_diagnostic
Business      → ps_agent, ps_division, ps_offer, ps_import
Functional    → ps_search, ps_compare, ps_favorite, ps_alert
```

### Services Architecture

All services follow strict dependency injection patterns with readonly properties and typed interfaces.

### Plugin Discovery

Uses PHP 8.3 attributes for plugin discovery (not annotations).

## Development

### Code Standards

```bash
# Check standards
vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/ps

# Auto-fix
vendor/bin/phpcbf --standard=Drupal,DrupalPractice web/modules/custom/ps

# Static analysis
vendor/bin/phpstan analyse web/modules/custom/ps --level=max
```

### Testing

```bash
# All tests
vendor/bin/phpunit web/modules/custom/ps/tests/

# Unit tests only
vendor/bin/phpunit web/modules/custom/ps/tests/src/Unit/

# Kernel tests
vendor/bin/phpunit web/modules/custom/ps/tests/src/Kernel/

# Functional tests
vendor/bin/phpunit web/modules/custom/ps/tests/src/Functional/
```

### Test Coverage

Target: >80% code coverage

Current test suites:
- **Unit**: SettingsManager, ValidationRulesEngine, PropertySearchEvent
- **Kernel**: DashboardWidgetManager
- **Functional**: AdminController routes and permissions

## Mandatory Standards

### PHP 8.3 Strict Types

All PHP files must start with:

```php
<?php

declare(strict_types=1);
```

### Attributes (Not Annotations)

✅ Correct:
```php
#[DashboardWidget(
  id: 'example',
  label: new TranslatableMarkup('Example'),
)]
```

❌ Wrong:
```php
/**
 * @DashboardWidget(
 *   id = "example"
 * )
 */
```

### Dependency Injection

Always inject services via constructor. Never use `\Drupal::service()` in class logic.

### Strong Typing

All parameters and return types must be declared.

## Troubleshooting

### Health Check Fails

```bash
# Check database connection
drush sql-query "SELECT 1"

# View health check
curl http://localhost/admin/ps/health
```

### Performance Monitoring

```bash
# Check settings
drush config-get ps.settings performance
```

## Contributing

Follow the PropertySearch coding standards:

1. PHP 8.3+ strict types
2. Attributes for plugins
3. Dependency injection
4. Complete PHPDoc
5. Test coverage >80%

## Maintainers

PropertySearch Development Team

## License

Proprietary - All rights reserved
