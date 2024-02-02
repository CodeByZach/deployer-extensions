<?php
namespace Deployer;


// Options
set('npm_options', '');


// Set nvm binary, automatically detected otherwise.
set('bin/nvm', function () {
	if (test('[ -s "$HOME/.nvm/nvm.sh" ]')) {
		return 'export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm';
	}
	return which('nvm');
});


// Set npm binary, automatically detected otherwise.
set('bin/npm', function () {
	if (get('bin/nvm')) {
		return '{{bin/nvm}} use && npm';
	}
	return which('npm');
});


// Set node binary, automatically detected otherwise.
set('bin/node', function () {
	if (get('bin/nvm')) {
		return '{{bin/nvm}} use && node';
	}
	return which('node');
});


// Install npm dependencies.
desc('Installs npm packages');
task('deploy:npm:install', function () {
	run("cd {{release_or_current_path}} && {{bin/npm}} ci {{npm_options}}");
});


// Installs nvm.
desc('Installs nvm');
task('provision:nvm', function () {
	run("curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash");
})->verbose()->limit(1);


// List installed npm packages.
desc('Lists installed npm packages');
task('provision:npm:list', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} list");
	writeOutput($output);
});


// List globally installed npm packages.
desc('Lists globally installed npm packages');
task('provision:npm:list_global', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} list -g");
	writeOutput($output);
});


// List nvm installed node versions.
desc('Lists nvm installed node versions');
task('provision:nvm:list', function () {
	$output = run("{{bin/nvm}} list");
	writeOutput($output);
});


// Get the node version.
desc('Gets the node version');
task('provision:node:version', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/node}} --version");
	writeOutput($output);
});


// Get the npm version.
desc('Gets the npm version');
task('provision:npm:version', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} --version");
	writeOutput($output);
});


// Get the nvm version.
desc('Gets the nvm version');
task('provision:nvm:version', function () {
	$output = run("{{bin/nvm}} --version");
	writeOutput($output);
});