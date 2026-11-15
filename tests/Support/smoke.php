<?php

namespace Deployer;

/*
 * Smoke recipe: runs read-only tasks against localhost in CI.
 *
 * Every runtime bug this package has had -- an undefined helper, parse() choking on
 * `{{` in command output, an unreachable nvm fallback -- was invisible to both PHPStan
 * and the test suite, and surfaced only by executing a task for real.
 *
 * Usage: vendor/bin/dep -f tests/Support/smoke.php smoke local
 */

use CodeByZach\DeployerExtensions\Loader;

require 'recipe/common.php';

Loader::load('default');
Loader::load('provision/php');
Loader::load('provision/composer');
Loader::load('provision/node');

// The provision tasks cd into release_or_current_path, so it has to exist. The .nvmrc
// makes bin/node resolve through nvm, which CI must have.
task('smoke:setup', function () {
    if (getenv('CI') && get('bin/nvm') === '') {
        throw new \RuntimeException('nvm is not installed, so the .nvmrc case would test nothing.');
    }
    run('mkdir -p {{deploy_path}}/current');
    run('echo system > {{deploy_path}}/current/.nvmrc');
})->hidden();

// Command output containing `{{` must print rather than throw ConfigurationException.
// The `\{{` escape puts the braces in the output without putting them in the command.
task('smoke:braces', function () {
    writeOutput(run('echo "output with \{{not_a_config_key}} in it"'));
})->hidden();

desc('Runs every read-only task against localhost');
task('smoke', [
    'smoke:setup',
    'smoke:braces',
    'provision:php:version',
    'provision:php:list',
    'provision:php:list_versions',
    'provision:composer:version',
    'provision:node:version',
    'provision:npm:version',
]);

localhost('local')
    ->set('deploy_path', sys_get_temp_dir() . '/deployer-extensions-smoke')
    ->set('labels', ['env' => 'local']);
