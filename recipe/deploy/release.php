<?php
namespace Deployer;

use Symfony\Component\Console\Helper\Table;

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

	// Save metainfo about release. JSON_THROW_ON_ERROR keeps json_encode() from
	// silently returning false and writing an empty record.
	$json = quote(json_encode($metainfo, JSON_THROW_ON_ERROR));
	run("echo {$json} >> {{deploy_path}}/.dep/release_commits_log");
});


// Remove failed release record.
desc('Remove release information from the record');
task('deploy:release:remove', function () {
	$release_name = get('release_name');

	// grep -F, not sed: release_name would otherwise be a regex, so a dotted name
	// matches too much and `-o release_name='.*'` empties both logs.
	$needle = quote('"release_name":"' . $release_name . '"');

	// Both logs are optional -- a deploy can fail before releases_log is written, and
	// erroring here would mask the original failure. `|| true` because grep exits 1
	// when it keeps no lines.
	foreach (['{{deploy_path}}/.dep/releases_log', '{{deploy_path}}/.dep/release_commits_log'] as $log_path) {
		if (test("[ -f {$log_path} ]")) {
			run("grep -vF {$needle} {$log_path} > {$log_path}.tmp || true");
			run("mv {$log_path}.tmp {$log_path}");
		}
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
 *
 * Deployer's built-in `releases` task reads each release's REVISION file, so it can
 * only show a commit while the release directory still exists. This task reads
 * `.dep/release_commits_log` instead, which outlives `keep_releases` pruning.
 */
desc('Shows releases list with commits from release_commits_log');
task('releases:list', function () {
	cd('{{deploy_path}}');

	$table               = [];
	$releases_log        = get('releases_log');
	$release_commits_log = get('release_commits_log');
	$current_release     = basename(run('readlink {{current_path}}'));
	$releases_list       = get('releases_list');
	$tz                  = getenv('TIMEZONE') ?: date_default_timezone_get();

	foreach ($releases_log as $metainfo) {
		// A malformed created_at must not take the whole listing down; fall back to
		// showing the raw value.
		$date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $metainfo['created_at']);
		if ($date === false) {
			$created_at = (string) $metainfo['created_at'];
		} else {
			$date->setTimezone(new \DateTimeZone($tz));
			$created_at = $date->format('Y-m-d H:i:s');
		}
		$status = $release = $metainfo['release_name'];
		if (in_array($release, $releases_list, true)) {
			if (test("[ -f releases/{$release}/BAD_RELEASE ]")) {
				$status = "<error>{$release}</error> (bad)";
			} elseif (test("[ -f releases/{$release}/DIRTY_RELEASE ]")) {
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
			$created_at,
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
