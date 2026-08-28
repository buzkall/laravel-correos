<?php

namespace SmartDato\CorreosShipping\Tests\Doubles;

use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;

/**
 * Exposes the authenticator's protected token helpers to the test suite.
 *
 * A named subclass is used instead of an anonymous one so that static analysis
 * can resolve the exposed methods.
 */
final class TestableCorreosAuthenticator extends CorreosAuthenticator
{
    public function exposedGetToken(): string
    {
        return $this->getToken();
    }

    public function exposedGetTokenTtl(string $token): int
    {
        return $this->getTokenTtl($token);
    }
}
