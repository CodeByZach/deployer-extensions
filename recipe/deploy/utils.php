<?php
namespace Deployer;


// Writes an plain message.
function writePlain(string $message) {
	// writeln("<fg=options=bold>plain</> {$message}");
	writeln($message);
}


// Writes an success message.
function writeSuccess(string $message) {
	// writeln("<fg=green;options=bold>success</> <fg=green>{$message}</>");
	writeln("<fg=green>{$message}</>");
}


// Writes an info message.
function writeInfo(string $message) {
	// writeln("<fg=cyan;options=bold>info</> <fg=cyan>{$message}</>");
	writeln("<fg=cyan>{$message}</>");
}


// Writes an warning message.
function writeWarning(string $message) {
	// writeln("<fg=yellow;options=bold>warning</> <fg=yellow>{$message}</>");
	writeln("<fg=yellow>{$message}</>");
}


// Writes an error message.
function writeError(string $message) {
	// writeln("<fg=red;options=bold>danger</> <fg=red>{$message}</>");
	writeln("<error>{$message}</error>");
}


// Abort a deployment.
desc('Abort a deployment');
task('deploy:abort', function () {
	writeError('Deployment aborted.');
	exit(1);
})->once();