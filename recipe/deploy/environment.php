<?php
namespace Deployer;


// Returns the environment status.
set('env_status', function () {
	$labels = get('labels');
	if ($labels && isset($labels['env'])) {
		return $labels['env'];
	}
	throw error('The "env" label is not set in the host configuration.');
});


// Gets the appropriate environment-specific configuration file for deployment.
set('env_config', function () {
	$env_status = get('env_status');
	$env_config = "env.{$env_status}.php";
	if (file_exists($env_config)) {
		return $env_config;
	}
	throw error("The source configuration file '{$env_config}' does not exist.");
});


// Check whether the environment-specific configuration file exits.
desc('Check for the existence of the environment-specific configuration file');
task('deploy:check_env_config', function () {
	get('env_config');
});


// Deploy the configuration file.
desc('Upload environment-specific configuration file to deployment');
task('deploy:upload_env_config', function () {
	$source_env_config = get('env_config');
	$target_env_config = '{{release_or_current_path}}/env.php';
	upload($source_env_config, $target_env_config);
});