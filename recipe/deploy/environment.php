<?php
namespace Deployer;


set('env_status', function () {
	$labels = get('labels');
	if ($labels && isset($labels['env'])) {
		return $labels['env'];
	} else {
		$message = "The 'env' label is not set in the host configuration.";
		error($message);
	}
});


set('env_config', function () {
	$env_status = get('env_status');
	$local_env_config = "env.{$env_status}.php";

	// Check if the local configuration file exists
	if (file_exists($local_env_config)) {
		return $local_env_config;
	} else {
		$message = "The local configuration file '{$local_env_config}' does not exist.";
		error($message);
	}
});


desc('Check for the existence of the environment-specific configuration file');
task('deploy:check_env_config', function () {
	$local_env_config = get('env_config');
});


desc('Copy environment-specific configuration file to deployment');
task('deploy:copy_env_config', function () {
	$local_env_config = get('env_config');
	$target_env_config = '{{release_path}}/env.php';

	upload($local_env_config, $target_env_config);
});