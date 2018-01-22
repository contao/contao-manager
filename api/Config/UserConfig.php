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

        $this->migrateSecret($kernel);
    }

    /**
     * Gets the application secret.
     *
     * @return string
     */
    public function getSecret()
    {
        if (!isset($this->data['secret'])) {
            $this->setSecret(bin2hex(random_bytes(40)));
        }

        return $this->data['secret'];
    }

    /**
     * Sets the application secret.
     *
     * @param string $secret
     */
    public function setSecret($secret)
    {
        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty.');
        }

        $this->data['secret'] = (string) $secret;

        $this->save();
    }

    /**
     * Counts the users.
     *
     * @return int
     */
    public function countUsers()
    {
        if (!isset($this->data['users'])) {
            return 0;
        }

        return count($this->data['users']);
    }

    /**
     * Gets all users.
     *
     * @return UserInterface[]
     */
    public function getUsers()
    {
        if (0 === $this->countUsers()) {
            return [];
        }

        $users = [];

        foreach ($this->data['users'] as $user) {
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
        return isset($this->data['users'][$username]);
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
        if (!isset($this->data['users'][$username])) {
            return null;
        }

        return new User(
            $this->data['users'][$username]['username'],
            $this->data['users'][$username]['password']
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

        if (isset($this->data['users'][$username])) {
            throw new \RuntimeException(sprintf('Username "%s" already exists.', $username));
        }

        $this->data['users'][$username] = [
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
        unset($this->data['users'][$user->getUsername()]);

        $this->addUser($user);
    }

    /**
     * Deletes a user from the configuration file.
     *
     * @param mixed $username
     */
    public function deleteUser($username)
    {
        unset($this->data['users'][$username]);

        $this->save();
    }

    /**
     * Gets tokens from the configuration file.
     *
     * @return array
     */
    public function getTokens()
    {
        if (!isset($this->data['tokens'])) {
            return [];
        }

        return $this->data['tokens'];
    }

    /**
     * Returns whether a token exists.
     *
     * @param string $token
     *
     * @return bool
     */
    public function hasToken($token)
    {
        return isset($this->data['tokens'][$token]);
    }

    /**
     * @param string $token
     *
     * @return array|null
     */
    public function getToken($token)
    {
        if (!isset($this->data['tokens'][$token])) {
            return null;
        }

        return $this->data['tokens'][$token];
    }

    /**
     * Creates a token for given username.
     *
     * @param string $username
     * @param array  $payload
     *
     * @return string
     */
    public function createToken($username, array $payload = [])
    {
        $token = bin2hex(random_bytes(16));

        $this->addToken($token, $username, $payload);

        return $token;
    }

    /**
     * Adds a token to the configuration file.
     *
     * @param string $token
     * @param string $username
     * @param array  $payload
     *
     * @throws \RuntimeException
     */
    public function addToken($token, $username, array $payload = [])
    {
        if (!$this->hasUser($username)) {
            throw new \RuntimeException(sprintf('Username "%s" does not exist.', $username));
        }

        if ($this->hasToken($token)) {
            throw new \RuntimeException(sprintf('Token "%s" already exist.', $token));
        }

        $payload['token'] = $token;
        $payload['username'] = $username;

        $this->data['tokens'][$token] = $payload;

        $this->save();
    }

    /**
     * Deletes a token from the configuration file.
     *
     * @param string $token
     */
    public function deleteToken($token)
    {
        unset($this->data['tokens'][$token]);

        $this->save();
    }

    /**
     * Migrates the secret from manager config to user config.
     *
     * @param ApiKernel $kernel
     */
    private function migrateSecret(Apikernel $kernel)
    {
        if (!isset($this->data['secret'])) {
            $config = $kernel->getContainer()->get('contao_manager.config.manager');

            if (!isset($this->data['users'])) {
                $this->data = ['users' => $this->data];
            }

            if ($config->has('secret')) {
                $this->data['secret'] = $config->get('secret');
                $config->remove('secret');
                $this->save();
            } else {
                $this->getSecret();
            }
        }
    }
}
