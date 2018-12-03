<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserConfig extends AbstractConfig implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ApiKernel          $kernel
     * @param Filesystem         $filesystem
     */
    public function __construct(ContainerInterface $container, ApiKernel $kernel, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getConfigDir().DIRECTORY_SEPARATOR.'users.json';

        parent::__construct($configFile, $filesystem);

        $this->container = $container;

        if (!isset($this->data['version']) || $this->data['version'] < 2) {
            $this->migrateSecret();
            $this->hashTokens();
            $this->data['version'] = 2;
            $this->save();
        }
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
        $password = $this->container->get(UserPasswordEncoderInterface::class)->encodePassword(
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
        if (!isset($this->data['tokens']) || !\is_array($this->data['tokens'])) {
            return [];
        }

        $data = [];

        foreach ($this->data['tokens'] as $id => $payload) {
            $data[] = array_merge(
                ['id' => $id],
                $payload
            );
        }

        return $data;
    }

    /**
     * Gets token payload by ID (hashed token value).
     *
     * @param string $id
     *
     * @return array|null
     */
    public function getToken($id)
    {
        if (!isset($this->data['tokens'][$id])) {
            return null;
        }

        return array_merge(
            ['id' => $id],
            $this->data['tokens'][$id]
        );
    }

    /**
     * Finds token payload by unhashed token value.
     *
     * @param string $token
     *
     * @return array|null
     */
    public function findToken($token)
    {
        return $this->getToken(hash('sha256', $token));
    }

    /**
     * Creates a token for given username.
     *
     * @param string $username
     * @param string $clientId
     * @param string $scope
     *
     * @return string
     */
    public function createToken($username, $clientId, $scope = 'admin')
    {
        if (!$this->hasUser($username)) {
            throw new \RuntimeException(sprintf('Username "%s" does not exist.', $username));
        }

        $token = bin2hex(random_bytes(16));
        $id = hash('sha256', $token);

        if (isset($this->data['tokens'][$id])) {
            throw new \RuntimeException(sprintf('Token with ID "%s" already exist.', $id));
        }

        $this->data['tokens'][$id] = [
            'username' => $username,
            'client_id' => $clientId,
            'scope' => $scope,
        ];

        $this->save();

        return array_merge(
            [
                'id' => $id,
                'token' => $token,
            ],
            $this->data['tokens'][$id]
        );
    }

    /**
     * Deletes a token from the configuration file.
     *
     * @param string $id
     */
    public function deleteToken($id)
    {
        unset($this->data['tokens'][$id]);

        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            UserPasswordEncoderInterface::class,
            ManagerConfig::class,
        ];
    }

    /**
     * Migrates the secret from manager config to user config.
     */
    private function migrateSecret()
    {
        if (!isset($this->data['secret'])) {
            $config = $this->container->get(ManagerConfig::class);

            if (!isset($this->data['users'])) {
                $this->data = ['users' => $this->data];
            }

            if ($config->has('secret')) {
                $this->data['secret'] = $config->get('secret');
                $config->remove('secret');
            } else {
                $this->getSecret();
            }
        }
    }

    private function hashTokens()
    {
        if (!isset($this->data['tokens']) || !\is_array($this->data['tokens'])) {
            return;
        }

        foreach ($this->data['tokens'] as $k => $payload) {
            if (!isset($payload['id']) && isset($payload['token'])) {
                $id = hash('sha256', $payload['token']);
                unset($this->data['tokens'][$k], $payload['token']);

                $this->data['tokens'][$id] = $payload;
            }
        }
    }
}
