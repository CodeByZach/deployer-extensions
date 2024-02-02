<?php
namespace Deployer;


// Print active php extensions.
desc('Lists active php extensions');
task('provision:php:list', function () {
	$output = run('{{bin/php}} -m');
	writeOutput($output);
});


// Print active php extensions with versions.
desc('Lists active php extensions with versions');
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
	$output = run('{{bin/php}} -r '.escapeshellarg($php_code));
	writeOutput($output);
});


// Get the php version.
desc('Gets the php version');
task('provision:php:version', function () {
	$output = run("{{bin/php}} --version");
	writeOutput($output);
});