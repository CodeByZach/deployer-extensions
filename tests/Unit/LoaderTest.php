<?php
use CodeByZach\DeployerExtensions\Loader;

test('Loader loads the default recipe', function () {
    Loader::load('default');

    // After loading, the Deployer helper `task()` must exist.
    expect(function_exists('task'))->toBeTrue();
});
