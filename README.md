# Deployer Extensions

Custom deployment recipes and provisioning tasks for [Deployer](https://deployer.org/).

## Requirements

- PHP ^8.4
- deployer/deployer ^8.0

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

// Required. These recipes build on Deployer's own configuration -- {{bin/php}},
// {{bin/composer}} and {{release_or_current_path}} are defined there. Without it you
// get: Config option "bin/php" does not exist.
// A framework recipe (recipe/laravel.php, recipe/symfony.php, ...) also works, since
// each of those requires common.php itself.
require 'recipe/common.php';

use CodeByZach\DeployerExtensions\Loader;

// Load the default deployment recipe
Loader::load('default');

// Or load specific recipes
Loader::load('deploy/env');
Loader::load('provision/node');
```

The output helpers (`writeOutput()`, `writeSuccess()`, ...) are autoloaded, so any
recipe can be loaded on its own without pulling in the others.

## Available Recipes

### Deployment

| Recipe | Description |
|--------|-------------|
| `default` | Main deployment workflow with pre-flight checks; loads the three below |
| `deploy/env` | Environment configuration management |
| `deploy/release` | Release and commit tracking |
| `deploy/utils` | The `deploy:abort` task |

### Provisioning (`recipe/provision/`)

| Recipe | Description |
|--------|-------------|
| `provision/apache` | Apache web server management |
| `provision/autossh` | SSH tunnel management via autossh |
| `provision/composer` | Composer installation (`provision:composer:install`) and package management |
| `provision/node` | Node.js/npm with NVM support |
| `provision/php` | PHP environment inspection |

## Notes

Three places where these recipes deliberately sit alongside Deployer's own. All are
intentional, not oversights:

- **`provision:composer:install`** is *not* named `provision:composer`. Deployer has
  defined a task by that name since v7.0.0, reachable from every project via
  `common.php`, and `task()` replaces an existing definition in place -- so sharing the
  name meant whichever file loaded last silently won. Ours installs via the official
  installer with `sudo` and honours `{{composer_install_directory}}`; Deployer's pipes
  `curl` to `php` and hardcodes `/usr/local/bin`. Both are now reachable.

- **`bin/npm`** is deliberately overridden by `provision/node` with an NVM-aware
  version. If you also import Deployer's `contrib/npm.php`, load this recipe *after*
  it, or the plain `which('npm')` lookup wins.

- **`releases:list`** overlaps Deployer's built-in `releases`. It is kept because
  Deployer reads each release's `REVISION` file, which disappears when `keep_releases`
  prunes the directory; `.dep/release_commits_log` outlives pruning, so this task can
  still show a commit where the built-in reports `unknown`.

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis (PHPStan level 8)
composer phpstan

# Check code style
composer check

# Fix code style
composer fix

# Run read-only tasks against localhost (also runs in CI)
composer smoke
```

## License

[MIT](LICENSE)
