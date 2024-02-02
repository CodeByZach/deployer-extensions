<?php
namespace Deployer;


// Set the apache webroot directory.
set('apache_webroot_directory', '/var/www');


// Set default log locations.
set('apache_error_log_files',  '/var/log/apache2/error.log');
set('apache_access_log_files', '/var/log/apache2/access.log');


// Tail the apache PHP error logs.
desc('Shows apache PHP error logs');
task('logs:apache:error', function () {
	run('tail -f {{apache_error_log_files}}');
})->verbose();


// Tail the apache PHP access logs.
desc('Shows apache PHP access logs');
task('logs:apache:access', function () {
	run('tail -f {{apache_access_log_files}}');
})->verbose();


// Set the correct webroot permissions.
desc('Sets the correct webroot permissions');
task('provision:apache:permissions', function () {
	// Change ownership recursively to root user and www-data group
	run('sudo chown -R root:www-data {{apache_webroot_directory}}');

	// Set file permissions to 664 (rw-rw-r--) recursively
	run('sudo find {{apache_webroot_directory}} -type f -exec chmod 664 {} \;');

	// Set directory permissions to 775 (rwxrwxr-x) recursively
	run('sudo find {{apache_webroot_directory}} -type d -exec chmod 775 {} \;');

	// Set the setgid bit on directories to inherit group ownership
	run('sudo find {{apache_webroot_directory}} -type d -exec chmod g+s {} \;');
})->verbose();


// Start apache.
desc('Starts apache');
task('provision:apache:start', function () {
	$output = run('sudo systemctl start apache2.service');
	writePlain($output);
});


// Stop apache.
desc('Stops apache');
task('provision:apache:stop', function () {
	$output = run('sudo systemctl stop apache2.service');
	writePlain($output);
});


// Restart apache.
desc('Restarts apache');
task('provision:apache:restart', function () {
	$output = run('sudo systemctl restart apache2.service');
	writeOutput($output);
});


// Get apache status.
desc('Gets apache status');
task('provision:apache:status', function () {
	$output = run('sudo systemctl status apache2.service');
	writeOutput($output);
});


// Print active apache modules.
desc('Lists active apache modules');
task('provision:apache:list', function () {
	$output = run('apache2ctl -M');
	writeOutput($output);
});


// Get the apache version.
desc('Gets the apache version');
task('provision:apache:version', function () {
	$output = run("apache2ctl -v");
	writeOutput($output);
});