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

/**
 * Writes raw command output on its own line, below the host prefix.
 * Not parsed for `{{config}}` placeholders.
 */
function writeOutput(string $message): void
{
    output()->writeln('[' . currentHost() . "]\n" . $message);
}


/**
 * Writes raw command output on a single line.
 * Not parsed for `{{config}}` placeholders.
 */
function writePlain(string $message): void
{
    // output()->writeln('[' . currentHost() . '] <fg=options=bold>plain</> ' . $message);
    output()->writeln('[' . currentHost() . '] ' . $message);
}


/**
 * Writes a success message. Parsed for `{{config}}` placeholders.
 */
function writeSuccess(string $message): void
{
    // writeln("<fg=green;options=bold>success</> <fg=green>{$message}</>");
    writeln("<fg=green>{$message}</>");
}


/**
 * Writes an info message. Parsed for `{{config}}` placeholders.
 */
function writeInfo(string $message): void
{
    // writeln("<fg=cyan;options=bold>info</> <fg=cyan>{$message}</>");
    writeln("<fg=cyan>{$message}</>");
}


/**
 * Writes a warning message. Parsed for `{{config}}` placeholders.
 */
function writeWarning(string $message): void
{
    // writeln("<fg=yellow;options=bold>warning</> <fg=yellow>{$message}</>");
    writeln("<fg=yellow>{$message}</>");
}


/**
 * Writes an error message. Parsed for `{{config}}` placeholders.
 */
function writeError(string $message): void
{
    // writeln("<fg=red;options=bold>danger</> <fg=red>{$message}</>");
    writeln("<error>{$message}</error>");
}
