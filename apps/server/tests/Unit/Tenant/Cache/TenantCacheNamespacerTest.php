<?php

declare(strict_types=1);

use App\Tenant\Cache\TenantCacheNamespacer;
use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\TenantContext;

function namespacer_for(?string $slug): TenantCacheNamespacer
{
    $context = new TenantContext();

    if (null !== $slug) {
        $context->setTenantSlug($slug);
    }

    return new TenantCacheNamespacer($context);
}

it('namespaces by the active tenant identifier', function () {
    $namespacer = namespacer_for('acme');

    expect($namespacer->namespace())
        ->toBe('tenant.acme')
        ->and($namespacer->key('dashboard.summary'))
        ->toBe('tenant.acme.dashboard.summary');
});

it('isolates one tenant namespace from another', function () {
    expect(namespacer_for('acme')->key('x'))->not->toBe(namespacer_for('globex')->key('x'));
});

it('fails closed when no tenant context is resolved', function () {
    namespacer_for(null)->namespace();
})->throws(MissingTenantContextException::class);
