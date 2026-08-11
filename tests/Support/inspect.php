<?php

namespace Deployer;

/*
 * Loads recipes in isolation and reports the resulting task set as JSON.
 * Run as a subprocess by the Feature tests -- see inspectRecipes() in tests/Pest.php.
 *
 * Usage: php inspect.php [<recipe> ...]
 */

use CodeByZach\DeployerExtensions\Loader;
use Symfony\Component\Console\Application;

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

// Mirrors what bin/dep does, so `require 'recipe/common.php'` resolves.
set_include_path($root . '/vendor/deployer/deployer' . PATH_SEPARATOR . get_include_path());

$deployer = new Deployer(new Application());

require 'recipe/common.php';

$baseline = [];
foreach ($deployer->tasks as $task) {
    $baseline[] = $task->getName();
}

foreach (array_slice($argv, 1) as $recipe) {
    Loader::load($recipe);
}

$tasks = [];
foreach ($deployer->tasks as $task) {
    $tasks[$task->getName()] = $task->getSourceLocation();
}

echo json_encode([
    'baseline' => $baseline,
    'tasks'    => $tasks,
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
