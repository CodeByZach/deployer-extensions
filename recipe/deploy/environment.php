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


// Gets the environment configuration type.
set('env_config_type', function () {
	$env_config_php    = "env.php.example";
	$env_config_dotenv = ".env.example";

	$env_config_php_exists    = file_exists($env_config_php);
	$env_config_dotenv_exists = file_exists($env_config_dotenv);

	if ($env_config_php_exists && $env_config_dotenv_exists) {
		throw error("Could not detect env configuration type. Both \"{$env_config_php}\" and \"{$env_config_dotenv}\" cannot exist simultaneously.");
	}

	if ($env_config_php_exists) {
		return 'php';
	}

	if ($env_config_dotenv_exists) {
		return 'dotenv';
	}

	throw error("Could not detect env configuration type. Neither \"{$env_config_php}\" or \"{$env_config_dotenv}\" present.");
});


// Gets the appropriate environment-specific configuration file for deployment.
set('env_config', function () {
	$env_status      = get('env_status');
	$env_config_type = get('env_config_type');

	switch ($env_config_type) {
		case 'dotenv':
			$env_config = ".env.{$env_status}";
			break;

		case 'php':
		default:
			$env_config = "env.{$env_status}.php";
			break;
	}

	if (file_exists($env_config)) {
		return $env_config;
	}

	throw error("The source configuration file \"{$env_config}\" does not exist.");
});


// Check whether the environment-specific configuration file exits.
desc('Check for the existence of the environment-specific configuration file');
task('deploy:check_env_config', function () {
	get('env_config');
});


// Deploy the configuration file.
desc('Upload environment-specific configuration file to deployment');
task('deploy:upload_env_config', function () {
	$env_config_type   = get('env_config_type');
	$source_env_config = get('env_config');
	// $shared_files      = get('shared_files');

	switch ($env_config_type) {
		case 'dotenv':
			$env_config_filename = ".env";
			break;

		case 'php':
		default:
			$env_config_filename = "env.php";
			break;
	}

	$target_env_config = "{{release_or_current_path}}/{$env_config_filename}";
	// if ($shared_files && in_array($env_config_filename, $shared_files)) {
	// 	// If the config file is shared, that is where we'll upload it.
	// 	$target_env_config = "{{deploy_path}}/shared/{$env_config_filename}";
	// }

	upload($source_env_config, $target_env_config);
});