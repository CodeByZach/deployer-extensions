<?php
namespace Deployer;


// Options
set('npm_options', '');


// Set npm binary, automatically detected otherwise.
set('bin/npm', function () {
	return which('npm');
});


// Install of your dependencies.
desc('Installs npm packages');
task('deploy:npm:install', function () {
	run("cd {{release_path}} && {{bin/npm}} ci {{npm_options}}");
});