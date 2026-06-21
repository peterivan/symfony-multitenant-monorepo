<?php

declare(strict_types=1);

use App\BackOffice\Http\BackOfficeAuthorizationSubscriber;
use App\BackOffice\Security\DenyByDefaultPlatformOperatorAuthorizer;
use App\BackOffice\Security\PlatformOperatorAuthorizer;
use App\Http\Boundary\RouteBoundary;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

function bo_auth_event(RouteBoundary $boundary, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
{
    $request = Request::create('/bo');
    $request->attributes->set(RouteBoundary::DEFAULT_KEY, $boundary->value);

    $kernel = new class implements HttpKernelInterface {
        public function handle(
            Request $request,
            int $type = self::MAIN_REQUEST,
            bool $catch = true,
        ): \Symfony\Component\HttpFoundation\Response {
            return new \Symfony\Component\HttpFoundation\Response();
        }
    };

    return new RequestEvent($kernel, $request, $type);
}

function bo_granting_authorizer(): PlatformOperatorAuthorizer
{
    return new class implements PlatformOperatorAuthorizer {
        public function isPlatformOperator(): bool
        {
            return true;
        }

        public function currentOperatorReference(): ?string
        {
            return 'operator:root';
        }
    };
}

it('runs after the firewall so it can read real platform authentication state', function () {
    expect(BackOfficeAuthorizationSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1])
        ->toBe(6)
        ->and(6)
        ->toBeLessThan(8);
});

it('denies Back Office access by default (fail closed) until authentication exists', function () {
    $subscriber = new BackOfficeAuthorizationSubscriber(bo_classifier(), new DenyByDefaultPlatformOperatorAuthorizer());

    $subscriber->onKernelRequest(bo_auth_event(RouteBoundary::BackOffice));
})->throws(AccessDeniedHttpException::class);

it('grants access only when the platform-operator authorizer grants it', function () {
    $subscriber = new BackOfficeAuthorizationSubscriber(bo_classifier(), bo_granting_authorizer());

    $subscriber->onKernelRequest(bo_auth_event(RouteBoundary::BackOffice));

    expect(true)->toBeTrue();
});

it('does not authorize non-back-office routes', function () {
    // central route must pass through even with the deny-by-default authorizer
    $subscriber = new BackOfficeAuthorizationSubscriber(bo_classifier(), new DenyByDefaultPlatformOperatorAuthorizer());

    $subscriber->onKernelRequest(bo_auth_event(RouteBoundary::CentralPlatform));

    expect(true)->toBeTrue();
});

it('decides solely on the platform-operator boundary, never on tenant-local signals', function () {
    // The authorizer interface has no tenant input at all: a tenant-local identity
    // cannot be expressed to it, let alone grant access. Deny-by-default still denies
    // regardless of any ambient tenant state.
    $tenantAware = new class implements PlatformOperatorAuthorizer {
        public function isPlatformOperator(): bool
        {
            return false; // a tenant-local actor is never a platform operator
        }

        public function currentOperatorReference(): ?string
        {
            return null;
        }
    };

    $subscriber = new BackOfficeAuthorizationSubscriber(bo_classifier(), $tenantAware);

    $subscriber->onKernelRequest(bo_auth_event(RouteBoundary::BackOffice));
})->throws(AccessDeniedHttpException::class);
