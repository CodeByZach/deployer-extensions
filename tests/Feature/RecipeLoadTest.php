<?php

it('finds every recipe file on disk', function () {
    // allRecipes() drives StandaloneRecipeTest, so a silently empty list would make
    // that whole file pass while testing nothing.
    expect(allRecipes())->toContain('default', 'deploy/env', 'provision/node');
});
