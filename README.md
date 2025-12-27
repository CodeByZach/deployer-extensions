# Deployer Extensions

Custom deployment recipes and provisioning tasks for [Deployer](https://deployer.org/).

## Requirements

- PHP ^8.2
- deployer/deployer ^7.5.12

## Installation

Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/CodeByZach/deployer-extensions"
        }
    ]
}
```

Then install:

```bash
composer require codebyzach/deployer-extensions
```

## Usage

In your `deploy.php`:

```php
<?php
namespace Deployer;

require 'vendor/autoload.php';

use CodeByZach\DeployerExtensions\Loader;

// Load the default deployment recipe
Loader::load('default');

// Or load specific recipes
Loader::load('deploy/env');
Loader::load('provision/node');
```

## Available Recipes

### Deployment (`recipe/deploy/`)

| Recipe | Description |
|--------|-------------|
| `default` | Main deployment workflow with pre-flight checks |
| `deploy/env` | Environment configuration management |
| `deploy/release` | Release and commit tracking |
| `deploy/utils` | Helper functions (messages, formatting) |

### Provisioning (`recipe/provision/`)

| Recipe | Description |
|--------|-------------|
| `provision/apache` | Apache web server management |
| `provision/autossh` | SSH tunnel management via autossh |
| `provision/composer` | Composer installation and package management |
| `provision/node` | Node.js/npm with NVM support |
| `provision/php` | PHP environment inspection |

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer phpstan
```

## License

MIT
