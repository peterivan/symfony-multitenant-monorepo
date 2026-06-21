<?php

declare(strict_types=1);

use App\Tenant\TenantResolver;

beforeEach(function () {
    $this->resolver = new TenantResolver('example.com');
});

it('returns null for the bare base domain', function () {
    expect($this->resolver->resolveFromHost('example.com'))->toBeNull();
});

it('returns null for an empty host', function () {
    expect($this->resolver->resolveFromHost(''))->toBeNull();
});

it('resolves a single-label subdomain to a tenant slug', function () {
    expect($this->resolver->resolveFromHost('acme.example.com'))->toBe('acme');
});

it('is case-insensitive for host and base domain', function () {
    expect($this->resolver->resolveFromHost('ACME.Example.COM'))->toBe('acme');
});

it('returns null for hosts outside the base domain', function () {
    expect($this->resolver->resolveFromHost('acme.other.com'))->toBeNull();
});

it('returns null when the slug segment is empty', function () {
    expect($this->resolver->resolveFromHost('.example.com'))->toBeNull();
});

it('rejects slugs with invalid characters', function (string $host) {
    expect($this->resolver->resolveFromHost($host))->toBeNull();
})->with([
    'underscore' => 'acme_corp.example.com',
    'leading hyphen' => '-acme.example.com',
    'trailing hyphen' => 'acme-.example.com',
    'multi-label' => 'team.acme.example.com',
]);

it('accepts slugs with internal hyphens and digits', function () {
    expect($this->resolver->resolveFromHost('acme-2.example.com'))->toBe('acme-2');
});
