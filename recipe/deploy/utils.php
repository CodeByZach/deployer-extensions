<?php
namespace Deployer;


// Abort a deployment.
desc('Abort a deployment');
task('deploy:abort', function () {
	writeln('<error>Deployment aborted.</error>');
	exit(1);
});