<?php
namespace Deployer;


// Options
set('npm_options', '');


// Set npm binary, automatically detected otherwise.
set('bin/npm', function () {
	if (test('[ -s "$HOME/.nvm/nvm.sh" ]')) {
		return 'export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm use && npm';
	}
	return which('npm');
});


// Install of your dependencies.
desc('Installs npm packages');
task('deploy:npm:install', function () {
	run("cd {{release_or_current_path}} && {{bin/npm}} ci {{npm_options}}");
});