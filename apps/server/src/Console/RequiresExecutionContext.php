<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Marks a command whose execution context is significant and therefore MUST be
 * declared (ADR-001). Such a command must also implement
 * {@see DeclaresExecutionContext}; one that does not is rejected before it runs.
 */
interface RequiresExecutionContext {}
