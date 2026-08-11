<?php

namespace CodeByZach\DeployerExtensions;

class Loader
{
	/**
	 * Load a recipe file by relative path.
	 *
	 * Examples:
	 *     Loader::load('default');
	 *     Loader::load('deploy/env');
	 *     Loader::load('provision/node');
	 */
	public static function load(string $recipe): void
	{
		$base = realpath(__DIR__ . '/../recipe') . DIRECTORY_SEPARATOR;
		$path = realpath($base . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $recipe) . '.php');

		// realpath() resolves `..` segments, so a single prefix check rejects both
		// missing files and any attempt to escape the recipe directory.
		if ($path === false || !str_starts_with($path, $base)) {
			throw new \InvalidArgumentException("Recipe file not found: {$recipe}");
		}

		require_once $path;
	}
}
