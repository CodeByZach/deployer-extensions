<?php

namespace Deployer;

require_once(__DIR__ . '/deploy/env.php');
require_once(__DIR__ . '/deploy/release.php');
require_once(__DIR__ . '/deploy/utils.php');


/**
 * Register this recipe with Deployer.
 */
add('recipes', ['default']);


/**
 * Whether the symlink step completed successfully.
 * Used to determine if cleanup is needed on failed deployments.
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
        'local',
    ];

    if (!in_array($env_status, $low_risk_env_statuses)) {
        if (!askConfirmation("\e[0mAre you sure you want to deploy to [\e[1;93m{$env_status_uppercase}\e[0m]?")) {
            invoke('deploy:abort');
        }
    }

    invoke('deploy:env:check');
});


// Named, not an anonymous after() closure: those register as `after:deploy:symlink`,
// which a consuming deploy.php using the same idiom would silently replace.
task('deploy:mark_symlink_published', function () {
    set('symlink_published', true);
})->hidden();


// Define a custom task to clean up a failed release if necessary.
desc('Clean up a failed release if the deployment failed before or during symlink');
task('deploy:cleanup_failed_release', function () {
    // Check if the symlink step was completed successfully.
    if (!get('symlink_published')) {
        $release_name = get('release_name');

        // Second, independent check before an irreversible delete: never remove whatever
        // `current` points at. Derived from the filesystem rather than from config, so it
        // still holds if symlink_published is wrong.
        if (basename(run('readlink {{current_path}} || true')) === $release_name) {
            writeWarning("Release \"{$release_name}\" is live; skipping cleanup.");
            return;
        }

        // Remove the failed release directory, if it got as far as being created. Only
        // release_name is quoted; quoting deploy_path would break `~/...` paths, since
        // bash does not tilde-expand inside the $'...' quote() emits.
        run('rm -rf {{deploy_path}}/releases/' . quote($release_name));

        // Remove failed release record.
        invoke('deploy:release:remove');

        writeSuccess('Failed deployment cleaned up successfully.');
    } else {
        writeSuccess('Deployment did not fail before, or during "deploy:symlink". No cleanup required.');
    }
});




/**
 * Hooks
 */
before('deploy', 'deploy:precheck');
// `fail('deploy', 'deploy:failed')` is intentionally absent -- Deployer's own
// recipe/common.php already registers it, and fail() replaces rather than appends.
after('deploy:update_code', 'deploy:env:upload');
after('deploy:prepare', 'deploy:release:commit');
after('deploy:symlink', 'deploy:mark_symlink_published');
after('deploy:failed', 'deploy:cleanup_failed_release');
after('deploy:failed', 'deploy:unlock');
