<?php
namespace Deployer;


// Set the apache webroot directory.
set('apache_webroot_directory', '/var/www');


// Set default log locations.
set('apache_error_log_files',  '/var/log/apache2/error.log');
set('apache_access_log_files', '/var/log/apache2/access.log');


// Tail the apache php error logs.
desc('Shows apache php error logs');
task('logs:apache:error', function () {
	run('tail -f {{apache_error_log_files}}');
})->verbose();


// Tail the apache php access logs.
desc('Shows apache php access logs');
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
desc('Start apache');
task('provision:apache:start', function () {
	$output = run('sudo systemctl apache2 start');
	writePlain($output);
});


// Stop apache.
desc('Stop apache');
task('provision:apache:stop', function () {
	$output = run('sudo systemctl apache2 stop');
	writePlain($output);
});


// Restart apache.
desc('Restart apache');
task('provision:apache:restart', function () {
	$output = run('sudo systemctl apache2 restart');
	writePlain($output);
});


// Get apache status.
desc('Get apache status');
task('provision:apache:status', function () {
	$output = run('sudo systemctl apache2 status');
	writePlain($output);
});