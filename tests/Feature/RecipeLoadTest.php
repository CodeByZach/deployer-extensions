<?php

it('registers every expected task when all recipes are loaded', function () {
	$tasks = array_keys(inspectRecipes(allRecipes())['tasks']);

	expect($tasks)->toContain(
		// deploy
		'deploy:abort',
		'deploy:cleanup_failed_release',
		'deploy:env:check',
		'deploy:env:upload',
		'deploy:mark_symlink_published',
		'deploy:precheck',
		'deploy:release:commit',
		'deploy:release:remove',
		'releases:list',
		// provision
		'deploy:npm:install',
		'provision:apache:permissions',
		'provision:apache:status',
		'provision:autossh',
		'provision:autossh:open',
		'provision:composer:install',
		'provision:composer:version',
		'provision:node:version',
		'provision:nvm',
		'provision:php:list',
		'provision:php:list_versions',
		'provision:php:version',
		'logs:apache:error',
	);
});


it('finds every recipe file on disk', function () {
	// A silently empty list would make the other Feature tests pass while testing nothing.
	expect(allRecipes())->toContain('default', 'deploy/env', 'provision/node');
});
