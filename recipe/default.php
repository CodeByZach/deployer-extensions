<?php
namespace Deployer;

require(__DIR__.'/provision/apache.php');
require(__DIR__.'/deploy/release_commit.php');
require(__DIR__.'/deploy/environment.php');
require(__DIR__.'/deploy/utils.php');


/**
 * Global options
 */
set('symlink_published', false);




/**
 * Tasks
 */
// Define a custom task to log the deployment
desc('Pre-flight check prior to initiating deployment.');
task('deploy:precheck', function () {
	$env_status = get('env_status');
	$env_status_uppercase = strtoupper($env_status);
	$low_risk_env_statuses = [
		'development'
	];

	if (in_array($env_status, $low_risk_env_statuses) || askConfirmation("Are you sure you want to deploy to **{$env_status_uppercase}**?")) {
		desc('Deploys your project');
		task('deploy', [
			'deploy:prepare',
			'deploy:vendors',
			'deploy:publish'
		]);
		fail('deploy', 'deploy:failed');

		invoke('deploy');
	}
});


// Define a custom task to clean up a failed release if necessary
desc('Clean up a failed release if the deployment failed before or during symlink');
task('deploy:cleanup_failed_release', function () {
	// Check if the symlink step was completed successfully
	if (!get('symlink_published')) {
		$release_name = get('release_name');
		$failed_release_path = '{{deploy_path}}/releases/'.$release_name;

		// Remove the failed release directory
		run("rm -rf $failed_release_path");

		// Define temporary paths for the log files
		$releases_log_path = '{{deploy_path}}/.dep/releases_log';
		$release_commits_log_path = '{{deploy_path}}/.dep/release_commits_log';

		// Remove records of the failed release from the log file copies
		run("sed '/\"release_name\":\"{$release_name}\"/d' {$releases_log_path} > {$releases_log_path}.tmp");
		run("sed '/\"release_name\":\"{$release_name}\"/d' {$release_commits_log_path} > {$release_commits_log_path}.tmp");

		// Overwrite the original log files with the cleaned copies
		run("mv {$releases_log_path}.tmp {$releases_log_path}");
		run("mv {$release_commits_log_path}.tmp {$release_commits_log_path}");

		writeln("<info>Failed release has been cleaned up successfully.</info>");
	} else {
		writeln("<comment>Deployment did not fail before or during 'deploy:symlink'. No cleanup required.</comment>");
	}
});




/**
 * Deployment Task
 */
// desc('Deploys your project');
// task('deploy', [
// 	'deploy:precheck'
// ]);
desc('Deploys your project');
task('deploy', [
	'deploy:prepare',
	'deploy:vendors',
	'deploy:publish'
]);




/**
 * Hooks
 */
fail('deploy', 'deploy:failed');
before('deploy:prepare', 'deploy:check_env_config');
after('deploy:prepare', 'deploy:release_commit');
before('deploy:symlink', 'deploy:copy_env_config');
after('deploy:symlink', function () {
	set('symlink_published', true);
});
after('deploy:failed', 'deploy:cleanup_failed_release');
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'deploy:abort');