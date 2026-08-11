<?php

it('does not silently replace any of Deployer\'s own tasks', function () {
    // Regression test for `provision:composer`, which shadowed Deployer's task of the
    // same name from 2024 until the rename to `provision:composer:install`.
    ['baseline' => $baseline, 'tasks' => $tasks] = inspectRecipes(allRecipes());

    expect($baseline)->not->toBeEmpty();

    // realpath() because getSourceLocation() is resolved; comparing against an
    // unresolved path would match nothing and pass vacuously on a symlinked checkout.
    $recipeDir = realpath(repoPath('recipe')) . DIRECTORY_SEPARATOR;

    // A task Deployer declared whose definition now points into this package is one
    // we overrode.
    $overridden = array_values(array_filter(
        $baseline,
        fn(string $name): bool => isset($tasks[$name]) && str_starts_with($tasks[$name], $recipeDir),
    ));

    expect($overridden)->toBeEmpty()
        // The flip side: both composer tasks exist, each owned by its own package.
        ->and($tasks)->toHaveKeys(['provision:composer', 'provision:composer:install'])
        ->and($tasks['provision:composer'])->toContain('vendor/deployer/deployer')
        ->and($tasks['provision:composer:install'])->toContain('recipe/provision/composer.php')
        // Reverting this to an anonymous after() closure would register it as
        // `after:deploy:symlink`, which a consumer could silently replace.
        ->and($tasks)->toHaveKey('deploy:mark_symlink_published');
});
