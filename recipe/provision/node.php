<?php
namespace Deployer;

/**
 * Extra options for npm install command.
 * ```php
 * set('npm_options', '--legacy-peer-deps');
 * ```
 */
set('npm_options', '');


/**
 * Path to NVM binary, automatically detected.
 * Checks for NVM installation in `$HOME/.nvm/nvm.sh`, then for an `nvm` on PATH.
 *
 * Returns '' when nvm is absent so `bin/npm` and `bin/node` can fall back. nvm is
 * optional, so it is probed with commandExist(); which() throws and would make those
 * fallbacks unreachable. commandExist() only finds executables, not shell functions,
 * so a normal nvm install is caught by the nvm.sh test above.
 */
set('bin/nvm', function () {
	if (test('[ -s "$HOME/.nvm/nvm.sh" ]')) {
		return 'export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm';
	}
	return commandExist('nvm') ? which('nvm') : '';
});


/**
 * Path to npm binary, automatically detected.
 * Uses NVM if available, otherwise falls back to system npm.
 */
set('bin/npm', function () {
	if (get('bin/nvm')) {
		return '{{bin/nvm}} use && npm';
	}
	return which('npm');
});


/**
 * Path to Node.js binary, automatically detected.
 * Uses NVM if available, otherwise falls back to system node.
 */
set('bin/node', function () {
	if (get('bin/nvm')) {
		return '{{bin/nvm}} use && node';
	}
	return which('node');
});


/**
 * Install npm dependencies using `npm ci`.
 */
desc('Installs npm packages');
task('deploy:npm:install', function () {
	run("cd {{release_or_current_path}} && {{bin/npm}} ci {{npm_options}}");
});


/**
 * Version of NVM to install, as a git tag from nvm-sh/nvm.
 * ```php
 * set('nvm_version', 'v0.40.6');
 * ```
 */
set('nvm_version', 'v0.40.6');


/**
 * Install NVM (Node Version Manager) on the server.
 */
desc('Installs nvm');
task('provision:nvm', function () {
	run("curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/{{nvm_version}}/install.sh | bash");
})->verbose()->limit(1);


/**
 * List locally installed npm packages.
 */
desc('Lists installed npm packages');
task('provision:npm:list', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} list");
	writeOutput($output);
});


/**
 * List globally installed npm packages.
 */
desc('Lists globally installed npm packages');
task('provision:npm:list_global', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} list -g");
	writeOutput($output);
});


/**
 * List Node.js versions installed via NVM.
 */
desc('Lists nvm installed node versions');
task('provision:nvm:list', function () {
	if (!get('bin/nvm')) {
		throw error('nvm is not installed. Run `dep provision:nvm` first.');
	}

	$output = run("{{bin/nvm}} list");
	writeOutput($output);
});


/**
 * Display current Node.js version.
 */
desc('Gets the node version');
task('provision:node:version', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/node}} --version");
	writeOutput($output);
});


/**
 * Display current npm version.
 */
desc('Gets the npm version');
task('provision:npm:version', function () {
	$output = run("cd {{release_or_current_path}} && {{bin/npm}} --version");
	writeOutput($output);
});


/**
 * Display current NVM version.
 */
desc('Gets the nvm version');
task('provision:nvm:version', function () {
	if (!get('bin/nvm')) {
		throw error('nvm is not installed. Run `dep provision:nvm` first.');
	}

	$output = run("{{bin/nvm}} --version");
	writeOutput($output);
});
