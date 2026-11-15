<?php

use Symfony\Component\Process\Process;

/*
 * Real deploys of a small git project to a temporary directory on this machine, with
 * tests/Support/deploy.php (or deploy-two-hosts.php) as the project's deploy.php.
 */

/**
 * Runs git in $cwd and returns its output.
 */
function git(string $cwd, string ...$args): string
{
    $process = new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', '-c', 'commit.gpgsign=false', ...$args], $cwd);
    $process->mustRun();

    return trim($process->getOutput());
}


/**
 * Runs `dep deploy` for the test project and returns the finished process.
 *
 * @param array<string, string> $env
 */
function deploy(string $project, string $target, array $options = [], string $deployFile = 'deploy.php', string $hosts = 'local', array $env = []): Process
{
    $process = new Process(
        [repoPath('vendor/bin/dep'), '-f', "{$project}/{$deployFile}", 'deploy', $hosts, '--no-interaction', ...$options],
        $project,
        // No telemetry or banner requests to deployer.org.
        ['DEPLOY_TEST_REPOSITORY' => $project, 'DEPLOY_TEST_PATH' => $target, 'DO_NOT_TRACK' => 'true', 'DO_NOT_SHOW_BANNER' => 'true', ...$env],
    );
    $process->setTimeout(120);
    $process->run();

    return $process;
}


/**
 * Deploys and fails the test, with Deployer's output, if the deploy failed.
 */
function deployOrFail(string $project, string $target, string ...$options): void
{
    $process = deploy($project, $target, $options);
    expect($process->isSuccessful())->toBeTrue($process->getOutput() . $process->getErrorOutput());
}


/**
 * Expects a deploy to have failed, with $reason in its output.
 */
function expectFailure(Process $process, string $reason): void
{
    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getOutput() . $process->getErrorOutput())->toContain($reason);
}


beforeEach(function () {
    $this->dir     = sys_get_temp_dir() . '/deployer-extensions-test-' . bin2hex(random_bytes(4));
    $this->project = "{$this->dir}/project";
    $this->target  = "{$this->dir}/target";

    mkdir($this->project, 0777, true);
    file_put_contents("{$this->project}/.env.example", "APP_NAME=example\n");
    file_put_contents("{$this->project}/.env.local", "APP_NAME=local\n");
    file_put_contents("{$this->project}/index.php", "<?php\n");
    // In the project root, like a real project's: Deployer runs local commands there.
    copy(repoPath('tests/Support/deploy.php'), "{$this->project}/deploy.php");
    copy(repoPath('tests/Support/deploy-two-hosts.php'), "{$this->project}/deploy-two-hosts.php");
    git($this->project, 'init', '--quiet', '--initial-branch=main');
    git($this->project, 'add', '.');
    git($this->project, 'commit', '--quiet', '--message=Initial commit');
});

afterEach(function () {
    // chmod first: a test can leave a directory read-only.
    (new Process(['chmod', '-R', 'u+w', $this->dir]))->run();
    (new Process(['rm', '-rf', $this->dir]))->run();
});


it('records the commit of each release', function (string $strategy) {
    deployOrFail($this->project, $this->target, '-o', "update_code_strategy={$strategy}");

    $commit = git($this->project, 'rev-parse', 'HEAD');
    expect(file_get_contents("{$this->target}/.dep/release_commits_log"))
        ->toContain('"release_name":"1","commit":"' . $commit . '"');
})->with(['archive', 'clone', 'local_archive']);

it('removes a release that failed before going live, and unlocks', function () {
    deployOrFail($this->project, $this->target);

    $failed = deploy($this->project, $this->target, ['-o', 'fail_after_release=1']);

    expectFailure($failed, 'Deploy failed on purpose.');
    expect("{$this->target}/releases/2")->not->toBeDirectory()
        ->and("{$this->target}/releases/1")->toBeDirectory()
        ->and(basename((string) readlink("{$this->target}/current")))->toBe('1')
        ->and("{$this->target}/.dep/deploy.lock")->not->toBeFile();
});

it('keeps an existing release when a deploy reuses its name', function () {
    deployOrFail($this->project, $this->target);
    deployOrFail($this->project, $this->target);

    $failed = deploy($this->project, $this->target, ['-o', 'release_name=1']);

    expectFailure($failed, 'Release name "1" already exists');
    expect("{$this->target}/releases/1")->toBeDirectory()
        ->and(file_get_contents("{$this->target}/.dep/releases_log"))->toContain('"release_name":"1"')
        ->and(file_get_contents("{$this->target}/.dep/release_commits_log"))->toContain('"release_name":"1"');
});

it("keeps another deploy's lock when failing before taking the lock", function () {
    deployOrFail($this->project, $this->target);
    file_put_contents("{$this->target}/.dep/deploy.lock", 'another deploy');
    // No .env.local: the pre-flight check fails before this deploy locks.
    unlink("{$this->project}/.env.local");

    $failed = deploy($this->project, $this->target);

    expectFailure($failed, 'does not exist');
    expect($failed->getOutput())->not->toContain('Deploy locked by')
        ->and(file_get_contents("{$this->target}/.dep/deploy.lock"))->toBe('another deploy');
});

it("deploys the environment's configuration file", function () {
    deployOrFail($this->project, $this->target);

    expect(file_get_contents("{$this->target}/current/.env"))->toBe("APP_NAME=local\n");
});

it('deploys a project that has no environment configuration file', function () {
    // Committed, so the release has no .env.example for Deployer's own deploy:env to copy.
    git($this->project, 'rm', '--quiet', '.env.example');
    unlink("{$this->project}/.env.local");
    git($this->project, 'commit', '--quiet', '--all', '--message=Remove the environment files');

    deployOrFail($this->project, $this->target);

    expect("{$this->target}/current/index.php")->toBeFile()
        ->and("{$this->target}/current/.env")->not->toBeFile()
        ->and("{$this->target}/current/env.php")->not->toBeFile();
});

it('refuses a project with both kinds of example file', function () {
    file_put_contents("{$this->project}/env.php.example", "<?php\n");

    expectFailure(deploy($this->project, $this->target), 'cannot exist simultaneously');
});

it('unlocks the hosts that locked when another host fails to lock', function () {
    $failed = deploy($this->project, $this->target, deployFile: 'deploy-two-hosts.php', hosts: 'all', env: ['DEPLOY_TEST_A_BREAK_LOCK' => '1']);

    expectFailure($failed, 'Permission denied');
    expect("{$this->target}/b/.dep/deploy.lock")->not->toBeFile();
});

it('removes the releases other hosts created when one host fails to create its release', function () {
    $twoHosts = fn(array $env = []) => deploy($this->project, $this->target, deployFile: 'deploy-two-hosts.php', hosts: 'all', env: $env);
    expect($twoHosts()->isSuccessful())->toBeTrue();

    $failed = $twoHosts(['DEPLOY_TEST_A_RELEASE_NAME' => '1']);

    expectFailure($failed, 'Release name "1" already exists');
    expect("{$this->target}/a/releases/1")->toBeDirectory()
        ->and("{$this->target}/b/releases/2")->not->toBeDirectory()
        ->and("{$this->target}/b/releases/1")->toBeDirectory()
        ->and(file_get_contents("{$this->target}/b/.dep/releases_log"))->not->toContain('"release_name":"2"');
});
