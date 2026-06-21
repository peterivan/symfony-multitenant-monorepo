<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Implemented by commands that declare the {@see ExecutionContext} they run in
 * (ADR-001).
 */
interface DeclaresExecutionContext
{
    public function getExecutionContext(): ExecutionContext;
}
