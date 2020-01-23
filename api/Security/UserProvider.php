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

use Contao\ManagerApi\Config\UserConfig;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @var UserConfig
     */
    private $config;

    public function __construct(UserConfig $config)
    {
        $this->config = $config;
    }

    public function loadUserByUsername($username): UserInterface
    {
        if (0 === $this->config->countUsers()) {
            return new User($username, null);
        }

        return $this->getUser($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return $this->getUser($user->getUsername());
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $this->config->updateUser(
            new User($user->getUsername(), $newEncodedPassword)
        );
    }

    /**
     * @throws UsernameNotFoundException if user whose given username does not exist
     */
    private function getUser(string $username): User
    {
        $user = $this->config->getUser($username);

        if (null === $user) {
            $ex = new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
            $ex->setUsername($username);

            throw $ex;
        }

        return $user;
    }
}
