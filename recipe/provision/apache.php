<?php
namespace Deployer;


// Set default log locations.
set('apache_error_log_files', '/var/log/apache2/logs/error.log');
set('apache_access_log_files', '/var/log/apache2/logs/access.log');


// Tail the apache php error logs.
desc('Shows apache php error logs');
task('logs:apache_error', function () {
	if (!has('apache_error_log_files')) {
		warning("Please, specify \"apache_error_log_files\" option.");
		return;
	}
	run('tail -f {{apache_error_log_files}}');
})->verbose();


// Tail the apache php access logs.
desc('Shows apache php access logs');
task('logs:apache_access', function () {
	if (!has('apache_access_log_files')) {
		warning("Please, specify \"apache_access_log_files\" option.");
		return;
	}
	run('tail -f {{apache_access_log_files}}');
})->verbose();