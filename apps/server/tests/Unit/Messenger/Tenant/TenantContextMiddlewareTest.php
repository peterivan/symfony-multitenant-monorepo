<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Messenger\Tenant\TenantContextMiddleware;
use App\Messenger\Tenant\TenantScopedMessage;
use App\Messenger\Tenant\TenantStamp;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Exception\MissingTenantRoutingMetadataException;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

function mw_plain_message(): object
{
    return new \stdClass();
}

function mw_scoped_message(): TenantScopedMessage
{
    return new class implements TenantScopedMessage {};
}

function mw_middleware(TenantContext $context): TenantContextMiddleware
{
    $registry = new class implements TenantRegistry {
        public function findBySlug(string $slug): ?Tenant
        {
            return null;
        }
    };

    $factory = new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new \LogicException('not used');
        }
    };

    return new TenantContextMiddleware($context, new TenantEntityManagerProvider($context, $registry, $factory));
}

/**
 * @param callable(Envelope):void $onHandle
 */
function mw_stack(callable $onHandle): StackInterface
{
    return new class($onHandle) implements StackInterface {
        /** @param callable(Envelope):void $onHandle */
        public function __construct(
            private $onHandle,
        ) {}

        public function next(): MiddlewareInterface
        {
            return new class($this->onHandle) implements MiddlewareInterface {
                /** @param callable(Envelope):void $onHandle */
                public function __construct(
                    private $onHandle,
                ) {}

                public function handle(Envelope $envelope, StackInterface $stack): Envelope
                {
                    ($this->onHandle)($envelope);

                    return $envelope;
                }
            };
        }
    };
}

it('does not touch tenant context on the dispatch side', function () {
    $context = new TenantContext();
    $context->setTenantSlug('dispatcher');

    $during = 'unset';
    mw_middleware($context)->handle(
        new Envelope(mw_plain_message()), // no ReceivedStamp = dispatch side
        mw_stack(function () use ($context, &$during) {
            $during = $context->getTenantSlug();
        }),
    );

    expect($during)->toBe('dispatcher')->and($context->getTenantSlug())->toBe('dispatcher');
});

it('starts a consumed non-tenant-scoped message with no inherited context', function () {
    $context = new TenantContext();
    $context->setTenantSlug('leaked');

    $during = 'unset';
    mw_middleware($context)->handle(
        new Envelope(mw_plain_message(), [new ReceivedStamp('async')]),
        mw_stack(function () use ($context, &$during) {
            $during = $context->getTenantSlug();
        }),
    );

    expect($during)->toBeNull()->and($context->getTenantSlug())->toBeNull();
});

it('re-establishes tenant context only from the explicit stamp on consume', function () {
    $context = new TenantContext();
    $context->setTenantSlug('leaked');

    $during = 'unset';
    mw_middleware($context)->handle(
        new Envelope(mw_scoped_message(), [new ReceivedStamp('async'), new TenantStamp('acme')]),
        mw_stack(function () use ($context, &$during) {
            $during = $context->getTenantSlug();
        }),
    );

    expect($during)->toBe('acme')->and($context->getTenantSlug())->toBeNull(); // disposed after handling
});

it('fails closed when a tenant-scoped message is consumed without routing metadata', function () {
    $context = new TenantContext();

    $handled = false;
    mw_middleware($context)->handle(
        new Envelope(mw_scoped_message(), [new ReceivedStamp('async')]),
        mw_stack(function () use (&$handled) {
            $handled = true;
        }),
    );

    expect($handled)->toBeFalse();
})->throws(MissingTenantRoutingMetadataException::class);

it('disposes tenant context even when the handler throws', function () {
    $context = new TenantContext();

    $failed = false;

    try {
        mw_middleware($context)->handle(
            new Envelope(mw_scoped_message(), [new ReceivedStamp('async'), new TenantStamp('acme')]),
            mw_stack(function () {
                throw new \RuntimeException('handler failed');
            }),
        );
    } catch (\RuntimeException) {
        $failed = true;
    }

    expect($failed)->toBeTrue()->and($context->getTenantSlug())->toBeNull();
});
