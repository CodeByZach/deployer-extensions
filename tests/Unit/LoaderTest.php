<?php

use CodeByZach\DeployerExtensions\Loader;

test('Loader rejects a recipe that does not exist', function () {
	// The message echoes the requested recipe, not the absolute path it resolved to.
	expect(fn () => Loader::load('non-existent-recipe'))
		->toThrow(InvalidArgumentException::class, 'Recipe file not found: non-existent-recipe');
});

test('Loader rejects paths escaping the recipe directory', function (string $recipe) {
	Loader::load($recipe);
})->with([
	// These resolve to nothing, so realpath() catches them...
	'parent directory'   => '../composer',
	'outside the repo'   => '../../etc/passwd',
	'traversal mid-path' => 'provision/../../composer',
	// ...this one resolves to a real file, so only the prefix check catches it.
	'real file outside' => '../vendor/deployer/deployer/recipe/laravel',
])->throws(InvalidArgumentException::class);

test('Loader accepts a sibling directory that merely shares the recipe prefix', function () {
	// Guards against the prefix check being written without a trailing separator,
	// which would let a `recipe-anything/` sibling through.
	expect(fn () => Loader::load('../recipe-evil/x'))
		->toThrow(InvalidArgumentException::class);
});
