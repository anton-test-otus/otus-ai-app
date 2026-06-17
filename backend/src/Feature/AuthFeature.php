<?php

namespace App\Feature;

/**
 * Application auth mode: multi-user (JWT) vs single-user (implicit local owner).
 */
final class AuthFeature
{
    public function __construct(
        private bool $authEnabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->authEnabled;
    }
}
