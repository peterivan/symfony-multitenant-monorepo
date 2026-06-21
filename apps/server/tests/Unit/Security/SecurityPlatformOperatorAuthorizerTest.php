<?php

declare(strict_types=1);

use App\BackOffice\Security\SecurityPlatformOperatorAuthorizer;
use App\Central\Entity\PlatformOperator;
use App\Security\Platform\PlatformOperatorUser;
use App\Security\Tenant\TenantUserIdentity;
use App\Tenant\Entity\TenantUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

function authorizerWithUser(?UserInterface $user): SecurityPlatformOperatorAuthorizer
{
    $storage = test()->createMock(TokenStorageInterface::class);

    if (null === $user) {
        $storage->method('getToken')->willReturn(null);
    } else {
        $token = test()->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage->method('getToken')->willReturn($token);
    }

    return new SecurityPlatformOperatorAuthorizer($storage);
}

it('grants only when the token holds a platform operator', function () {
    $operator = new PlatformOperator('ops@platform.test', 'Ops');
    $authorizer = authorizerWithUser(new PlatformOperatorUser($operator));

    expect($authorizer->isPlatformOperator())
        ->toBeTrue()
        ->and($authorizer->currentOperatorReference())
        ->toBe($operator->auditReference());
});

it('never lets tenant authentication state grant Back Office access', function () {
    $authorizer = authorizerWithUser(new TenantUserIdentity(new TenantUser('u@acme.test', 'U'), 'acme'));

    expect($authorizer->isPlatformOperator())->toBeFalse()->and($authorizer->currentOperatorReference())->toBeNull();
});

it('fails closed when there is no authentication state', function () {
    $authorizer = authorizerWithUser(null);

    expect($authorizer->isPlatformOperator())->toBeFalse()->and($authorizer->currentOperatorReference())->toBeNull();
});
