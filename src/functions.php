<?php

namespace Deployer;

/*
 * Output helpers, in Deployer's namespace so recipes can call them unqualified.
 * Autoloaded via Composer's `files` entry -- PSR-4 only resolves classes.
 *
 * writeOutput/writePlain take command output, so they bypass writeln(): it runs
 * parse() on its argument, and output containing `{{` would throw. The rest take
 * strings we author, so they keep writeln() and {{config}} placeholders.
 *
 * Style tags are not handled: output crosses two formatters (worker, then
 * Master::gatherOutput), so escaping is undone by the first. `<info>` is swallowed and
 * `<fg=nope>` throws -- as they do for Deployer's own info()/writeln().
 */

/** Raw command output, on its own line below the host prefix. */
function writeOutput(string $message): void
{
    output()->writeln('[' . currentHost() . "]\n" . $message);
}


/** Raw command output, on a single line. */
function writePlain(string $message): void
{
    output()->writeln('[' . currentHost() . '] ' . $message);
}


function writeSuccess(string $message): void
{
    writeln("<fg=green>{$message}</>");
}


function writeInfo(string $message): void
{
    writeln("<fg=cyan>{$message}</>");
}


function writeWarning(string $message): void
{
    writeln("<fg=yellow>{$message}</>");
}


function writeError(string $message): void
{
    writeln("<error>{$message}</error>");
}
