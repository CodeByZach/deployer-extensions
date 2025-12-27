<?php

use CodeByZach\DeployerExtensions\Loader;

test('Loader throws exception for non-existent recipe', function () {
    Loader::load('non-existent-recipe');
})->throws(InvalidArgumentException::class);

test('Loader resolves correct path for recipe', function () {
    // Use reflection to test path resolution without executing the recipe
    $reflection = new ReflectionClass(Loader::class);
    $basePath = dirname($reflection->getFileName()) . '/../recipe/';

    // Verify recipe files exist
    expect(file_exists($basePath . 'default.php'))->toBeTrue();
    expect(file_exists($basePath . 'deploy/env.php'))->toBeTrue();
    expect(file_exists($basePath . 'provision/node.php'))->toBeTrue();
});
