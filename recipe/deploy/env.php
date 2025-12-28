<?php
namespace Deployer;

use Deployer\Exception\ConfigurationException;


/**
 * Local root directory of the project, detected via git.
 */
set('local_path', function () {
	$local_root = runLocally('git rev-parse --show-toplevel');
	return rtrim($local_root, "\r\n");
});


/**
 * Environment status from host labels (e.g., 'production', 'staging', 'development').
 * Requires the `env` label to be set in the host configuration.
 * ```php
 * host('example.com')
 *     ->setLabels(['env' => 'production']);
 * ```
 */
set('env_status', function () {
	$labels = get('labels');
	if ($labels && isset($labels['env'])) {
		return $labels['env'];
	}
	throw new ConfigurationException('The "env" label is not set in the host configuration.');
});


/**
 * Detected environment configuration type ('php' or 'dotenv').
 * Auto-detected based on presence of `env.php.example` or `.env.example` in project root.
 */
set('env_config_type', function () {
	$local_path        = get('local_path');
	$env_config_php    = "{$local_path}/env.php.example";
	$env_config_dotenv = "{$local_path}/.env.example";

	$env_config_php_exists    = file_exists($env_config_php);
	$env_config_dotenv_exists = file_exists($env_config_dotenv);

	if ($env_config_php_exists && $env_config_dotenv_exists) {
		throw new ConfigurationException("Could not detect env configuration type. Both \"{$env_config_php}\" and \"{$env_config_dotenv}\" cannot exist simultaneously.");
	}

	if ($env_config_php_exists) {
		return 'php';
	}

	if ($env_config_dotenv_exists) {
		return 'dotenv';
	}

	throw new ConfigurationException("Could not detect env configuration type. Neither \"{$env_config_php}\" or \"{$env_config_dotenv}\" present.");
});


/**
 * Path to the environment-specific configuration file for deployment.
 * Resolved based on `env_status` and `env_config_type`.
 * Examples: `.env.production`, `env.staging.php`
 */
set('env_config', function () {
	$local_path      = get('local_path');
	$env_status      = get('env_status');
	$env_config_type = get('env_config_type');

	switch ($env_config_type) {
		case 'dotenv':
			$env_config = "{$local_path}/.env.{$env_status}";
			break;

		case 'php':
		default:
			$env_config = "{$local_path}/env.{$env_status}.php";
			break;
	}

	if (file_exists($env_config)) {
		return $env_config;
	}

	throw new ConfigurationException("The source configuration file \"{$env_config}\" does not exist.");
});


/**
 * Validate environment configuration file exists before deployment.
 */
desc('Check for the existence of the environment-specific configuration file');
task('deploy:env:check', function () {
	get('env_config');
});


/**
 * Upload environment configuration file to the release directory.
 */
desc('Upload environment-specific configuration file to deployment');
task('deploy:env:upload', function () {
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