<?php

declare(strict_types=1);

namespace App\BackOffice\Security;

/**
 * Authorization seam for the Back Office (ADR-002).
 *
 * Back Office access is decided EXCLUSIVELY through the platform-operator boundary.
 * This port has no notion of tenant-local users, memberships, roles, or
 * permissions, so tenant-local identity can never grant Back Office access and a
 * tenant-local identity can never auto-inherit platform-operator privileges.
 *
 * The concrete platform-operator identity/authentication mechanism is delivered by
 * the authentication change (ADR-007). Until then the default implementation
 * ({@see DenyByDefaultPlatformOperatorAuthorizer}) denies all access, so the
 * surface fails closed.
 */
interface PlatformOperatorAuthorizer
{
    /**
     * Whether the current actor is an authenticated, authorized platform operator.
     */
    public function isPlatformOperator(): bool;

    /**
     * Stable reference to the current platform operator for audit attribution, or
     * null when there is no authorized operator.
     */
    public function currentOperatorReference(): ?string;
}
