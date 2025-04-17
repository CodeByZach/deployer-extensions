<?php
namespace Deployer;

use Symfony\Component\Console\Helper\Table;

use function Deployer\Support\escape_shell_argument;


// Get releases from the `.dep/release_commits_log` log file.
set('release_commits_log', function () {
	cd('{{deploy_path}}');

	if (!test('[ -f .dep/release_commits_log ]')) {
		return [];
	}

	$release_commits_logs = array_map(function ($line) {
		return json_decode($line, true);
	}, explode("\n", run('tail -n 300 .dep/release_commits_log')));

	return array_filter($release_commits_logs); // Return all non-empty lines.
});


// Clean up unfinished releases and prepare next release.
desc('Store commit hash with release_name for the current release');
task('deploy:release:commit', function () {
	$git    = get('bin/git');
	$target = get('target');
	$rev    = run("cd {{deploy_path}}/.dep/repo && ({$git} rev-list {$target} -1)");

	// Metainfo.
	$metainfo = [
		'release_name' => get('release_name'),
		'commit'       => $rev
	];

	// Save metainfo about release.
	$json = escape_shell_argument(json_encode($metainfo));
	run("echo {$json} >> {{deploy_path}}/.dep/release_commits_log");
});


// Remove failed release record.
desc('Remove release information from the record');
task('deploy:release:remove', function () {
	$release_name = get('release_name');

	// Define temporary paths for the log files.
	$releases_log_path        = '{{deploy_path}}/.dep/releases_log';
	$release_commits_log_path = '{{deploy_path}}/.dep/release_commits_log';

	// Remove records of the failed release from the log file copies.
	run("sed '/\"release_name\":\"{$release_name}\"/d' {$releases_log_path} > {$releases_log_path}.tmp");

	// Overwrite the original log files with the cleaned copies.
	run("mv {$releases_log_path}.tmp {$releases_log_path}");

	// Remove and overwrite for the "release_commits_log".
	if (test("[ -f {$release_commits_log_path} ]")) {
		run("sed '/\"release_name\":\"{$release_name}\"/d' {$release_commits_log_path} > {$release_commits_log_path}.tmp");
		run("mv {$release_commits_log_path}.tmp {$release_commits_log_path}");
	}
});


/*
 * Example output:
 * ```
 * +---------------------+------example.org ------------+--------+-----------+
 * | Date (UTC)          | Release     | Author         | Target | Commit    |
 * +---------------------+-------------+----------------+--------+-----------+
 * | 2021-11-06 20:51:45 | 1           | Anton Medvedev | HEAD   | 34d24192e |
 * | 2021-11-06 21:00:50 | 2 (bad)     | Anton Medvedev | HEAD   | 392948a40 |
 * | 2021-11-06 23:19:20 | 3           | Anton Medvedev | HEAD   | a4057a36c |
 * | 2021-11-06 23:24:30 | 4 (current) | Anton Medvedev | HEAD   | s3wa45ca6 |
 * +---------------------+-------------+----------------+--------+-----------+
 * ```
 */
desc('Shows releases list with commits from release_commits_log');
task('releases:list', function () {
	cd('{{deploy_path}}');

	$table               = [];
	$releases_log        = get('releases_log');
	$release_commits_log = get('release_commits_log');
	$current_release     = basename(run('readlink {{current_path}}'));
	$releases_list       = get('releases_list');
	$tz                  = !empty(getenv('TIMEZONE')) ? getenv('TIMEZONE') : date_default_timezone_get();

	foreach ($releases_log as &$metainfo) {
		$date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $metainfo['created_at']);
		$date->setTimezone(new \DateTimeZone($tz));
		$status = $release = $metainfo['release_name'];
		if (in_array($release, $releases_list, true)) {
			if (test("[ -f releases/{$release}/BAD_RELEASE ]")) {
				$status = "<error>{$release}</error> (bad)";
			} else if (test("[ -f releases/{$release}/DIRTY_RELEASE ]")) {
				$status = "<error>{$release}</error> (dirty)";
			} else {
				$status = "<info>{$release}</info>";
			}
		}
		if ($release === $current_release) {
			$status .= ' (current)';
		}
		$revision = 'unknown'; // Initialize to 'unknown' by default
		foreach ($release_commits_log as $commit_info) {
			if (isset($commit_info['release_name']) && $commit_info['release_name'] === $release) {
				$revision = $commit_info['commit'];
				break; // Stop searching once a matching release is found
			}
		}
		$table[] = [
			$date->format('Y-m-d H:i:s'),
			$status,
			$metainfo['user'],
			$metainfo['target'],
			$revision,
		];
	}

	(new Table(output()))
		->setHeaderTitle(currentHost()->getAlias())
		->setHeaders(["Date ({$tz})", 'Release', 'Author', 'Target', 'Commit'])
		->setRows($table)
		->render();
});