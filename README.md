# Deployer
## Requirements
- deployer/deployer (^7.3) [Docs](https://deployer.org/docs/7.x/getting-started)


## Installation
1. Install deployer
    ```bash
    composer require --dev "deployer/deployer";
    ```

2. Include in `deploy.php`
    ```php
    require(__DIR__.'/src/deployer/recipe/default.php');
    ```