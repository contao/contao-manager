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
    public const SCOPES = ['admin', 'install', 'update', 'read'];
    public const ROLES = ['ROLE_ADMIN', 'ROLE_INSTALL', 'ROLE_UPDATE', 'ROLE_READ'];

    private array $data;

    public function __construct(
        string $username,
        string|null $password,
        string|null $scope = null,
        array $data = [],
    ) {
        $scope ??= 'admin';

        if (!\in_array($scope, self::SCOPES, true)) {
            throw new \InvalidArgumentException('Invalid scope');
        }

        $this->data = array_merge($data, [
            'username' => $username,
            'password' => $password,
            'scope' => $scope,
        ]);
    }

    public function getRoles(): array
    {
        return self::rolesFromScope($this->data['scope']) ?? [];
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

    public static function rolesFromScope(string|null $scope): array|null
    {
        if (null === $scope || !\in_array($scope, self::SCOPES, true)) {
            return null;
        }

        return ['ROLE_'.strtoupper($scope)];
    }

    public static function scopeFromRoles(array $roles): string|null
    {
        $roles = array_values(array_intersect(self::ROLES, $roles));

        if ([] === $roles) {
            return null;
        }

        return strtolower(substr($roles[0], 5));
    }
}
