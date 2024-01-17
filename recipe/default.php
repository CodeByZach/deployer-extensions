<?php
namespace Deployer;

require(__DIR__.'/deploy/environment.php');
require(__DIR__.'/deploy/release_commit.php');
require(__DIR__.'/deploy/utils.php');


/**
 * Global options
 */
set('symlink_published', false);




/**
 * Tasks
 */
// Pre-flight check prior to initiating deployment.
desc('Pre-flight check prior to initiating deployment');
task('deploy:precheck', function () {
	$env_status            = get('env_status');
	$env_status_uppercase  = strtoupper($env_status);
	$low_risk_env_statuses = [
		'development',
		'local'
	];

	if (!in_array($env_status, $low_risk_env_statuses)) {
		if (!askConfirmation("\e[0mAre you sure you want to deploy to [\e[1m\e[93m{$env_status_uppercase}\e[0m]?")) {
			invoke('deploy:abort');
		}
	}

	invoke('deploy:check_env_config');
});


// Define a custom task to clean up a failed release if necessary.
desc('Clean up a failed release if the deployment failed before or during symlink');
task('deploy:cleanup_failed_release', function () {
	// Check if the symlink step was completed successfully.
	if (!get('symlink_published')) {
		$release_name        = get('release_name');
		$failed_release_path = "{{deploy_path}}/releases/{$release_name}";

		// Remove the failed release directory.
		run("rm -rf {$failed_release_path}");

		// Remove failed release record.
		invoke('deploy:remove_release_record');

		writeSuccess('Failed deployment cleaned up successfully.');
	} else {
		writeSuccess('Deployment did not fail before, or during "deploy:symlink". No cleanup required.');
	}
});




/**
 * Deployment Task
 */
desc('Deploys your project');
task('deploy', [
	'deploy:precheck',
	'deploy:prepare',
	'deploy:publish'
]);




/**
 * Hooks
 */
fail('deploy', 'deploy:failed');
after('deploy:update_code', 'deploy:upload_env_config');
after('deploy:prepare', 'deploy:release_commit');
after('deploy:symlink', function () {
	set('symlink_published', true);
});
after('deploy:failed', 'deploy:cleanup_failed_release');
after('deploy:failed', 'deploy:unlock');