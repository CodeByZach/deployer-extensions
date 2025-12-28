<?php
namespace Deployer;

use function Deployer\Support\escape_shell_argument;


/**
 * List all loaded PHP extensions.
 */
desc('Lists active PHP extensions');
task('provision:php:list', function () {
	$output = run('{{bin/php}} -m');
	writeOutput($output);
});


/**
 * List all loaded PHP extensions with their versions.
 */
desc('Lists active PHP extensions with versions');
task('provision:php:list_versions', function () {
	$php_code = <<<PHP
\$extensions = get_loaded_extensions();
sort(\$extensions);
foreach (\$extensions as \$extension) {
	if (extension_loaded(\$extension)) {
		\$version = phpversion(\$extension) ?: 'unknown';
		printf("%-30s %s\n", \$extension, \$version);
	}
}
PHP;

	// Execute PHP code on the server and capture the output.
	$output = run('{{bin/php}} -r '.escape_shell_argument($php_code));
	writeOutput($output);
});


/**
 * Display PHP version.
 */
desc('Gets the PHP version');
task('provision:php:version', function () {
	$output = run("{{bin/php}} --version");
	writeOutput($output);
});