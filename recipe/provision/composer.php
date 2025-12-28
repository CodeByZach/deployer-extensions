<?php
namespace Deployer;

// @phpstan-ignore-next-line
require_once('recipe/composer.php');


/**
 * Directory where Composer will be installed globally.
 * ```php
 * set('composer_install_directory', '/usr/bin');
 * ```
 */
set('composer_install_directory', '/usr/local/bin');


/**
 * Install Composer globally on the server.
 */
desc('Installs Composer');
task('provision:composer', function () {
	run('{{bin/php}} -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');" && sudo {{bin/php}} composer-setup.php --install-dir={{composer_install_directory}} && {{bin/php}} -r "unlink(\'composer-setup.php\');"');
})->verbose();


/**
 * List locally installed Composer packages.
 */
desc('Lists installed Composer packages');
task('provision:composer:list', function () {
	$output = run('cd {{release_or_current_path}} && {{bin/composer}} show');
	writeOutput($output);
});


/**
 * List globally installed Composer packages.
 */
desc('Lists globally installed Composer packages');
task('provision:composer:list_global', function () {
	$output = run('{{bin/composer}} global show');
	writeOutput($output);
});


/**
 * Display Composer version.
 */
desc('Gets the Composer version');
task('provision:composer:version', function () {
	$output = run("{{bin/composer}} --version");
	writeOutput($output);
});


/**
 * Run Composer diagnostics to check for common issues.
 */
desc('Outputs Composer diagnose');
task('provision:composer:diagnose', function () {
	$output = run("{{bin/composer}} diagnose");
	writeOutput($output);
});