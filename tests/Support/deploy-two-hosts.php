<?php

namespace Deployer;

/*
 * Like tests/Support/deploy.php, with two hosts, `a` and `b`, deployed together to
 * DEPLOY_TEST_PATH/a and DEPLOY_TEST_PATH/b. Host `a` can be made to fail:
 * DEPLOY_TEST_A_RELEASE_NAME gives it a release name, and DEPLOY_TEST_A_BREAK_LOCK
 * makes its lock fail (its .dep directory read-only).
 */

use CodeByZach\DeployerExtensions\Loader;

require 'recipe/common.php';

Loader::load('default');

set('repository', (string) getenv('DEPLOY_TEST_REPOSITORY'));

foreach (['a', 'b'] as $alias) {
    localhost($alias)
        ->set('deploy_path', getenv('DEPLOY_TEST_PATH') . "/{$alias}")
        ->set('branch', 'main')
        ->set('labels', ['env' => 'local']);
}

if (getenv('DEPLOY_TEST_A_RELEASE_NAME')) {
    host('a')->set('release_name', (string) getenv('DEPLOY_TEST_A_RELEASE_NAME'));
}

task('test:break_lock', function () {
    if (getenv('DEPLOY_TEST_A_BREAK_LOCK') && currentHost()->getAlias() === 'a') {
        run('chmod 555 {{deploy_path}}/.dep');
    }
})->hidden();
before('deploy:lock', 'test:break_lock');
