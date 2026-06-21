<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Enforces ADR-001's execution-context declaration for migration-related (and any
 * other {@see RequiresExecutionContext}) commands.
 *
 * A command that requires a context but does not declare one is rejected before it
 * runs (fails closed). Commands that do declare a context have it surfaced for
 * operator visibility.
 */
final class ExecutionContextSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof RequiresExecutionContext) {
            return;
        }

        if (!$command instanceof DeclaresExecutionContext) {
            throw UndeclaredExecutionContextException::for($command::class);
        }

        $event->getOutput()->writeln(
            \sprintf('<comment>Execution context: %s</comment>', $command->getExecutionContext()->value),
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE,
        );
    }
}
