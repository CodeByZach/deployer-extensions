<?php
namespace Deployer;


// Abort a deployment.
desc('Abort a deployment');
task('deploy:abort', function () {
	error('Deployment aborted.');
	exit(1);
});