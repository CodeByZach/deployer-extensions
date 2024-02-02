<?php
namespace Deployer;

require_once('recipe/composer.php');


// Set the composer install directory.
set('composer_install_directory', '/usr/local/bin');


// Install composer.
desc('Installs composer');
task('provision:composer', function () {
	run('{{bin/php}} -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');" && sudo {{bin/php}} composer-setup.php --install-dir={{composer_install_directory}} && {{bin/php}} -r "unlink(\'composer-setup.php\');"');
})->verbose();


// List installed composer packages.
desc('Lists installed composer packages');
task('provision:composer:list', function () {
	$output = run('cd {{release_or_current_path}} && {{bin/composer}} show');
	writeOutput($output);
});


// List globally installed composer packages.
desc('Lists globally installed composer packages');
task('provision:composer:list_global', function () {
	$output = run('{{bin/composer}} global show');
	writeOutput($output);
});


// Get the composer version.
desc('Gets the composer version');
task('provision:composer:version', function () {
	$output = run("{{bin/composer}} --version");
	writeOutput($output);
});


// Print composer diagnose.
desc('Outputs composer diagnose');
task('provision:composer:diagnose', function () {
	$output = run("{{bin/composer}} diagnose");
	writeOutput($output);
});