<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Thrown when a command that {@see RequiresExecutionContext} fails to declare one
 * via {@see DeclaresExecutionContext}. Fails closed: the command never runs.
 */
final class UndeclaredExecutionContextException extends \LogicException
{
    public static function for(string $commandClass): self
    {
        return new self(\sprintf(
            'Command "%s" requires a declared execution context but does not implement %s.',
            $commandClass,
            DeclaresExecutionContext::class,
        ));
    }
}
