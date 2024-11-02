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

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly string $username,
        private string|null $password,
        private array|null $roles = null
    ) {
        $this->roles ??= ['ROLE_ADMIN'];
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function getSalt(): string|null
    {
        return null;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
        $this->password = null;
    }
}
