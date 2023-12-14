<?php
namespace Deployer;


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