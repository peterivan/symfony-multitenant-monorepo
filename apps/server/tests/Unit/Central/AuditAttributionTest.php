<?php

declare(strict_types=1);

use App\Central\Entity\AuditAttribution;
use App\Central\Entity\AuditCategory;
use App\Central\Entity\AuditOutcome;
use App\Central\Entity\IdentityBoundary;
use App\Central\Entity\PlatformAuditEntry;

it('attributes a platform operator with platform-operation scope', function () {
    $attribution = AuditAttribution::forPlatformOperator('operator:123', 'tenant.suspend', 'acme');

    expect($attribution->identityBoundary)
        ->toBe(IdentityBoundary::PlatformOperator)
        ->and($attribution->identityReference)
        ->toBe('operator:123')
        ->and($attribution->operatorReference)
        ->toBe('operator:123')
        ->and($attribution->platformOperationScope)
        ->toBe('tenant.suspend')
        ->and($attribution->tenantSlug)
        ->toBe('acme');
});

it('attributes a tenant user with selected tenant context and no operator scope', function () {
    $attribution = AuditAttribution::forTenantUser('tenant-user:456', 'acme');

    expect($attribution->identityBoundary)
        ->toBe(IdentityBoundary::TenantUser)
        ->and($attribution->identityReference)
        ->toBe('tenant-user:456')
        ->and($attribution->tenantSlug)
        ->toBe('acme')
        ->and($attribution->operatorReference)
        ->toBeNull()
        ->and($attribution->platformOperationScope)
        ->toBeNull();
});

it('keeps an operator and a tenant user with the same email distinguishable', function () {
    $operator = AuditAttribution::forPlatformOperator('operator:same@x.test');
    $tenantUser = AuditAttribution::forTenantUser('tenant-user:same@x.test', 'acme');

    expect($operator->identityBoundary)->not->toBe($tenantUser->identityBoundary);
});

it('preserves the original positional attribution shape for existing call sites', function () {
    $attribution = new AuditAttribution('operator:legacy', 'acme');

    expect($attribution->operatorReference)
        ->toBe('operator:legacy')
        ->and($attribution->tenantSlug)
        ->toBe('acme')
        ->and($attribution->identityBoundary)
        ->toBeNull();
});

it('persists the boundary-distinguishing facts on the audit entry', function () {
    $entry = new PlatformAuditEntry(
        AuditCategory::TenantLifecycleChange,
        'tenant.suspend',
        AuditOutcome::Succeeded,
        AuditAttribution::forPlatformOperator('operator:123', 'tenant.suspend', 'acme'),
        ['tenant' => 'acme'],
    );

    expect($entry->getIdentityBoundary())
        ->toBe(IdentityBoundary::PlatformOperator)
        ->and($entry->getIdentityReference())
        ->toBe('operator:123')
        ->and($entry->getPlatformOperationScope())
        ->toBe('tenant.suspend')
        ->and($entry->getTenantSlug())
        ->toBe('acme');
});
