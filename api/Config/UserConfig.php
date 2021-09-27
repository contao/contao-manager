<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Security\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class UserConfig extends AbstractConfig implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, ApiKernel $kernel, Filesystem $filesystem = null)
    {
        parent::__construct('users.json', $kernel, $filesystem);

        $this->container = $container;
    }

    /**
     * Gets the application secret.
     */
    public function getSecret(): string
    {
        $this->initialize();

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
    public function setSecret($secret): void
    {
        $this->initialize();

        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty.');
        }

        $this->data['secret'] = (string) $secret;

        $this->save();
    }

    /**
     * Counts the users.
     */
    public function countUsers(): int
    {
        $this->initialize();

        if (!isset($this->data['users'])) {
            return 0;
        }

        return \count($this->data['users']);
    }

    /**
     * Gets all users.
     *
     * @return User[]
     */
    public function getUsers(): array
    {
        $this->initialize();

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
     */
    public function hasUser(string $username): bool
    {
        $this->initialize();

        return isset($this->data['users'][$username]);
    }

    /**
     * Gets the user by username or null if it does not exist.
     */
    public function getUser(string $username): ?User
    {
        $this->initialize();

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
     */
    public function createUser(string $username, string $password): User
    {
        $this->initialize();

        $encodedPassword = $this->container->get(UserPasswordEncoderInterface::class)->encodePassword(
            new User($username, null),
            $password
        );

        return new User($username, $encodedPassword);
    }

    /**
     * Adds a user to the configuration file.
     */
    public function addUser(UserInterface $user): void
    {
        $this->initialize();

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
     */
    public function updateUser(UserInterface $user): void
    {
        $this->initialize();

        unset($this->data['users'][$user->getUsername()]);

        $this->addUser($user);
    }

    /**
     * Deletes a user from the configuration file.
     */
    public function deleteUser(string $username): void
    {
        $this->initialize();

        unset($this->data['users'][$username]);

        $this->save();
    }

    /**
     * Gets tokens from the configuration file.
     */
    public function getTokens(): array
    {
        $this->initialize();

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
     */
    public function getToken(string $id): ?array
    {
        $this->initialize();

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
     */
    public function findToken(string $token): ?array
    {
        $this->initialize();

        return $this->getToken(hash('sha256', $token));
    }

    /**
     * Creates a token for given username.
     */
    public function createToken(string $username, string $clientId, string $scope = 'admin'): array
    {
        $this->initialize();

        if (!$this->hasUser($username)) {
            throw new \RuntimeException(sprintf('Username "%s" does not exist.', $username));
        }

        foreach ($this->getTokens() as $payload) {
            if ($payload['username'] === $username && $payload['client_id'] === $clientId) {
                $this->deleteToken($payload['id']);
            }
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
     */
    public function deleteToken(string $id): void
    {
        $this->initialize();

        unset($this->data['tokens'][$id]);

        $this->save();
    }

    public static function getSubscribedServices(): array
    {
        return [
            UserPasswordEncoderInterface::class,
            ManagerConfig::class,
        ];
    }

    protected function initialize(): void
    {
        parent::initialize();

        if (!isset($this->data['version']) || $this->data['version'] < 2) {
            $this->migrateSecret();
            $this->hashTokens();

            if (!empty($this->data)) {
                $this->data['version'] = 2;
                $this->save();
            }
        }
    }

    /**
     * Migrates the secret from manager config to user config.
     */
    private function migrateSecret(): void
    {
        if (!isset($this->data['secret'])) {
            $config = $this->container->get(ManagerConfig::class);

            if (!empty($this->data) && !isset($this->data['users'])) {
                $this->data = ['users' => $this->data];
            }

            if ($config->has('secret')) {
                $this->data['secret'] = $config->get('secret');
                $config->remove('secret');
            }
        }
    }

    private function hashTokens(): void
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
