<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\UserConfig;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProviderFactory
{
    /**
     * Creates an InMemory user provider from users in auth.json.
     *
     * @param UserConfig $config
     *
     * @return UserProviderInterface
     */
    public static function createUserProvider(UserConfig $config)
    {
        $provider = new InMemoryUserProvider();

        foreach ($config->getUsers() as $user) {
            $provider->createUser($user);
        }

        return $provider;
    }
}
