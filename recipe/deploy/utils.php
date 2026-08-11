<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

/*
 * The write*() helpers moved to src/functions.php, where they are autoloaded.
 */


/**
 * Abort a deployment gracefully.
 */
desc('Abort a deployment');
task('deploy:abort', function () {
    throw new GracefulShutdownException('Deployment aborted.');
})->once();
