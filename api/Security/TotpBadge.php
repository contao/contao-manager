<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Exception\InvalidTotpException;
use OTPHP\TOTP;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class TotpBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(private readonly string $code)
    {
    }

    public function verify(User $user): bool
    {
        $this->resolved = true;

        if (null === $user->getTotpSecret()) {
            return $this->resolved = true;
        }

        if (!TOTP::createFromSecret($user->getTotpSecret())->verify($this->code)) {
            $exception = new InvalidTotpException();
            $exception->setUser($user);

            throw $exception;
        }

        return $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
