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

class User implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    private array $data;

    public function __construct(
        string $username,
        string|null $password,
        array|null $roles = null,
        array $data = [],
    ) {
        $this->data = array_merge($data, [
            'username' => $username,
            'password' => $password,
            'roles' => $roles ?? ['ROLE_ADMIN'],
        ]);
    }

    public function getRoles(): array
    {
        return $this->data['roles'];
    }

    public function getPassword(): string|null
    {
        return $this->data['password'] ?? null;
    }

    public function getUserIdentifier(): string
    {
        return $this->data['username'];
    }

    public function eraseCredentials(): void
    {
        unset($this->data['password']);
    }

    public function getProfile(): array
    {
        $profile = $this->data;

        unset($profile['password']);

        return $profile;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
