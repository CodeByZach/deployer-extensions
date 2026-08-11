<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

/**
 * Abort a deployment gracefully.
 */
desc('Abort a deployment');
task('deploy:abort', function () {
    throw new GracefulShutdownException('Deployment aborted.');
})->once();
