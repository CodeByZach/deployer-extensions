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
        $base = __DIR__ . '/../recipe/';
        $path = $base . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $recipe) . '.php';

        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Recipe file not found: {$path}");
        }

        require_once $path;
    }
}
