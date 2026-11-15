<?php

namespace Deployer;

/*
 * The deploy.php of the small git project that tests/Feature/DeployTest.php deploys to
 * a directory on this machine. The test copies it into the project and passes the
 * project and the target directory as environment variables; options are set with -o.
 */

use CodeByZach\DeployerExtensions\Loader;

require 'recipe/common.php';

Loader::load('default');

set('repository', (string) getenv('DEPLOY_TEST_REPOSITORY'));

// With -o fail_after_release=1, the deploy fails right after creating its release.
task('test:fail_after_release', function () {
    if (get('fail_after_release', false)) {
        throw new \RuntimeException('Deploy failed on purpose.');
    }
})->hidden();
after('deploy:release', 'test:fail_after_release');

localhost('local')
    ->set('deploy_path', (string) getenv('DEPLOY_TEST_PATH'))
    ->set('branch', 'main')
    ->set('labels', ['env' => 'local']);
