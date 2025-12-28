<?php
namespace Deployer;

use Deployer\Exception\GracefulShutdownException;


/**
 * Writes a plain message.
 */
function writePlain(string $message) {
	// writeln("<fg=options=bold>plain</> {$message}");
	writeln($message);
}


/**
 * Writes a success message.
 */
function writeSuccess(string $message) {
	// writeln("<fg=green;options=bold>success</> <fg=green>{$message}</>");
	writeln("<fg=green>{$message}</>");
}


/**
 * Writes an info message.
 */
function writeInfo(string $message) {
	// writeln("<fg=cyan;options=bold>info</> <fg=cyan>{$message}</>");
	writeln("<fg=cyan>{$message}</>");
}


/**
 * Writes a warning message.
 */
function writeWarning(string $message) {
	// writeln("<fg=yellow;options=bold>warning</> <fg=yellow>{$message}</>");
	writeln("<fg=yellow>{$message}</>");
}


/**
 * Writes an error message.
 */
function writeError(string $message) {
	// writeln("<fg=red;options=bold>danger</> <fg=red>{$message}</>");
	writeln("<error>{$message}</error>");
}


/**
 * Writes output with a prepended line break.
 * Useful for separating command output from task messages.
 */
function writeOutput(string $message): void {
	writeln("\n" . $message);
}


/**
 * Abort a deployment gracefully.
 */
desc('Abort a deployment');
task('deploy:abort', function () {
	throw new GracefulShutdownException('Deployment aborted.');
})->once();