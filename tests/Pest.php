<?php

use Symfony\Component\Process\Process;

/**
 * Absolute path within the repository.
 */
function repoPath(string $path): string
{
	return dirname(__DIR__) . '/' . $path;
}


/**
 * Every recipe shipped by this package, as Loader::load() paths.
 *
 * Discovered rather than hardcoded, so a new recipe is covered the moment it is added.
 *
 * @return list<string>
 */
function allRecipes(): array
{
	$base    = repoPath('recipe');
	$recipes = [];

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
	);

	foreach ($files as $file) {
		if ($file->getExtension() === 'php') {
			$relative  = substr($file->getPathname(), strlen($base) + 1, -strlen('.php'));
			$recipes[] = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
		}
	}

	sort($recipes);

	return $recipes;
}


/**
 * Load recipes in a fresh PHP process and return the resulting task set.
 *
 * The subprocess is load-bearing: recipes use require_once, so a recipe cannot be
 * loaded twice in one process.
 *
 * @param  list<string> $recipes
 * @return array{baseline: list<string>, tasks: array<string, string>}
 */
function inspectRecipes(array $recipes): array
{
	$process = new Process([PHP_BINARY, repoPath('tests/Support/inspect.php'), ...$recipes]);
	$process->run();

	// Checked here rather than per-test so every caller gets it, and so the child's
	// stderr reaches the failure message instead of a bare "false is not true".
	if (!$process->isSuccessful() || $process->getErrorOutput() !== '') {
		throw new RuntimeException("inspect.php failed:\n" . $process->getErrorOutput());
	}

	return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
}
