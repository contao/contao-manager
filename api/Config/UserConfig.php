<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserConfig extends AbstractConfig
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * Constructor.
     *
     * @param ApiKernel                    $kernel
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param Filesystem                   $filesystem
     */
    public function __construct(ApiKernel $kernel, UserPasswordEncoderInterface $passwordEncoder, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'users.json';

        parent::__construct($configFile, $filesystem);

        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Gets all users configured in the auth.json file.
     *
     * @return UserInterface[]
     */
    public function getUsers()
    {
        if ($this->isEmpty()) {
            return [];
        }

        $users = [];

        foreach ($this->data as $user) {
            $users[] = new User(
                $user['username'],
                $user['password']
            );
        }

        return $users;
    }

    /**
     * Returns whether a user with the given username exists.
     *
     * @param string $username
     *
     * @return bool
     */
    public function hasUser($username)
    {
        return isset($this->data[$username]);
    }

    /**
     * Gets the user by username or null if it does not exist.
     *
     * @param string $username
     *
     * @return User|null
     */
    public function getUser($username)
    {
        if (!isset($this->data[$username])) {
            return null;
        }

        return new User(
            $this->data[$username]['username'],
            $this->data[$username]['password']
        );
    }

    /**
     * Creates user from given username and plaintext password but does not add it.
     *
     * @param string $username
     * @param string $password
     *
     * @return UserInterface
     */
    public function createUser($username, $password)
    {
        $password = $this->passwordEncoder->encodePassword(
            new User($username, null),
            $password
        );

        return new User($username, $password);
    }

    /**
     * Adds a user to the configuration file.
     *
     * @param UserInterface $user
     */
    public function addUser(UserInterface $user)
    {
        $username = $user->getUsername();

        if (isset($this->data[$username])) {
            throw new \RuntimeException(sprintf('User "%s" already exists.', $username));
        }

        $this->data[$username] = [
            'username' => $username,
            'password' => $user->getPassword(),
        ];

        $this->save();
    }

    /**
     * Replaces a user in the configuration file.
     *
     * @param UserInterface $user
     */
    public function updateUser(UserInterface $user)
    {
        unset($this->data[$user->getUsername()]);

        $this->addUser($user);
    }

    /**
     * Deletes a user from the configuration file.
     *
     * @param mixed $username
     */
    public function deleteUser($username)
    {
        unset($this->data[$username]);

        $this->save();
    }
}
