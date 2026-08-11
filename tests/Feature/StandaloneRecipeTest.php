<?php

it('loads each recipe on its own', function (string $recipe) {
    // Top-level code only -- requires, imports, set() calls. Task bodies are closures
    // the loader never invokes, so undefined calls inside them are PHPStan's job.
    inspectRecipes([$recipe]);
})->with(allRecipes())->throwsNoExceptions();
