<?php
namespace Deployer;

use Symfony\Component\Console\Helper\Table;


// Holds releases log from `.dep/release_commits_log` file.
set('release_commits_log', function () {
	cd('{{deploy_path}}');

	if (!test('[ -f .dep/release_commits_log ]')) {
		return [];
	}

	$releaseCommitsLogs = array_map(function ($line) {
		return json_decode($line, true);
	}, explode("\n", run('tail -n 300 .dep/release_commits_log')));

	return array_filter($releaseCommitsLogs); // Return all non-empty lines.
});


// Clean up unfinished releases and prepare next release
desc('Store commit hash with release_name for the current release');
task('deploy:release_commit', function () {
	$git = get('bin/git');
	$target = get('target');
	$rev = run("cd {{deploy_path}}/.dep/repo && ($git rev-list $target -1)");

	// Metainfo.
	$metainfo = [
		'release_name' => get('release_name'),
		'commit' => $rev
	];

	// Save metainfo about release.
	$json = escapeshellarg(json_encode($metainfo));
	run("echo $json >> {{deploy_path}}/.dep/release_commits_log");
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

	$releasesLog = get('releases_log');
	$releaseCommitsLog = get('release_commits_log');
	$currentRelease = basename(run('readlink {{current_path}}'));
	$releasesList = get('releases_list');

	$table = [];
	$tz = !empty(getenv('TIMEZONE')) ? getenv('TIMEZONE') : date_default_timezone_get();

	foreach ($releasesLog as &$metainfo) {
		$date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $metainfo['created_at']);
		$date->setTimezone(new \DateTimeZone($tz));
		$status = $release = $metainfo['release_name'];
		if (in_array($release, $releasesList, true)) {
			if (test("[ -f releases/$release/BAD_RELEASE ]")) {
				$status = "<error>$release</error> (bad)";
			} else if (test("[ -f releases/$release/DIRTY_RELEASE ]")) {
				$status = "<error>$release</error> (dirty)";
			} else {
				$status = "<info>$release</info>";
			}
		}
		if ($release === $currentRelease) {
			$status .= ' (current)';
		}
		$revision = 'unknown'; // Initialize to 'unknown' by default
		foreach ($releaseCommitsLog as $commitInfo) {
			if (isset($commitInfo['release_name']) && $commitInfo['release_name'] === $release) {
				$revision = $commitInfo['commit'];
				break; // Stop searching once a matching release is found
			}
		}
		$table[] = [
			$date->format("Y-m-d H:i:s"),
			$status,
			$metainfo['user'],
			$metainfo['target'],
			$revision,
		];
	}

	(new Table(output()))
		->setHeaderTitle(currentHost()->getAlias())
		->setHeaders(["Date ($tz)", 'Release', 'Author', 'Target', 'Commit'])
		->setRows($table)
		->render();
});