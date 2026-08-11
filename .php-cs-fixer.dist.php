<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/recipe')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,

        // Recipes open with `<?php` then `namespace Deployer;` on the next line.
        // Deployer's own config carries this same exception.
        'blank_line_after_opening_tag' => false,
    ])
    ->setFinder($finder);
