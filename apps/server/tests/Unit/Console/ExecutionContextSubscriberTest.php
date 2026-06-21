<?php

declare(strict_types=1);

use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\ExecutionContextSubscriber;
use App\Console\RequiresExecutionContext;
use App\Console\UndeclaredExecutionContextException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function dispatch(Command $command): void
{
    $event = new ConsoleCommandEvent($command, new ArrayInput([]), new NullOutput());
    new ExecutionContextSubscriber()->onCommand($event);
}

it('rejects a command that requires but does not declare an execution context', function () {
    $command = new class extends Command implements RequiresExecutionContext {};

    expect(fn() => dispatch($command))->toThrow(UndeclaredExecutionContextException::class);
});

it('allows a command that declares its execution context', function () {
    $command = new class extends Command implements RequiresExecutionContext, DeclaresExecutionContext {
        public function getExecutionContext(): ExecutionContext
        {
            return ExecutionContext::Central;
        }
    };

    dispatch($command);

    expect($command->getExecutionContext())->toBe(ExecutionContext::Central);
});

it('ignores commands that do not require an execution context', function () {
    $command = new class extends Command {};

    dispatch($command);

    expect($command)->toBeInstanceOf(Command::class);
});
