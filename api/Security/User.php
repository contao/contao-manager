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

    private string|null $totp_secret = null;

    private string|null $passkey = null;

    public function __construct(
        private readonly string $username,
        private string|null $password,
        private string|null $scope = null,
    ) {
        $this->scope ??= 'admin';

        if (!\in_array($this->scope, self::SCOPES, true)) {
            throw new \InvalidArgumentException('Invalid scope');
        }
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        $this->password = null;
    }

    public function getPasskey(): string|null
    {
        return $this->passkey;
    }

    public function setPasskey(string|null $passkey): void
    {
        $this->passkey = $passkey;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getRoles(): array
    {
        return self::rolesFromScope($this->scope) ?? [];
    }

    public function getTotpSecret(): string|null
    {
        return $this->totp_secret;
    }

    public function setTotpSecret(string|null $secret): void
    {
        $this->totp_secret = $secret;
    }

    public function jsonSerialize(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
            'passkey' => $this->passkey,
            'scope' => $this->scope,
            'totp_secret' => $this->totp_secret,
        ];
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
        $scopes = array_map(static fn (string $role) => strtolower(substr($role, 5)), $roles);
        $scopes = array_values(array_intersect(self::SCOPES, $scopes));

        if ([] === $scopes) {
            return null;
        }

        return $scopes[0];
    }
}
