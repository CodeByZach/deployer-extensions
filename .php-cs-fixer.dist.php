<?php

/*
 * Encodes the style this repository already uses rather than imposing a new one:
 * tabs for indentation, and consecutive assignments aligned into columns.
 *
 * PSR-12 mandates four spaces, so `indentation_type` is disabled and the indent is
 * set to a tab explicitly -- php-cs-fixer applies one indent style to the whole run,
 * which is why src/ and tests/ use tabs too.
 */

$finder = PhpCsFixer\Finder::create()
	->in([__DIR__ . '/src', __DIR__ . '/recipe', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
	->setFinder($finder)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRiskyAllowed(false)
	->setRules([
		'@PSR12' => true,

		// Tabs, not PSR-12's four spaces.
		'indentation_type' => false,

		// Keep the aligned `=` and `=>` columns used throughout recipe/. Without the
		// `=>` entry the default 'single_space' actively strips existing alignment.
		'binary_operator_spaces' => [
			'default'   => 'single_space',
			'operators' => [
				'='  => 'align_single_space_minimal',
				'=>' => 'align_single_space_minimal',
			],
		],

		// Recipes open with `<?php` then `namespace Deployer;` on the very next line.
		// PSR-12 wants a blank line between them, and two separate rules enforce it.
		'blank_line_after_opening_tag' => false,
		'blank_lines_before_namespace' => false,

		// Recipe files are declarations, not classes; brace placement rules for
		// functions would move `{` onto its own line and reflow every helper.
		'braces_position' => false,
	]);
