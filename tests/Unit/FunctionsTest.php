<?php

test('the output helpers are autoloaded', function (string $helper) {
	// Guards the `files` entry in composer.json. Drop it and every write*() call in
	// recipe/provision/ fatals at runtime, which PHPStan cannot catch.
	expect(function_exists("Deployer\\{$helper}"))->toBeTrue();
})->with(['writeOutput', 'writePlain', 'writeSuccess', 'writeInfo', 'writeWarning', 'writeError']);
