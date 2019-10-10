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
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProviderFactory
{
    /**
     * Creates an InMemory user provider from users in auth.json.
     */
    public static function createUserProvider(UserConfig $config): UserProviderInterface
    {
        $provider = new InMemoryUserProvider();

        foreach ($config->getUsers() as $user) {
            $provider->createUser($user);
        }

        return $provider;
    }
}
