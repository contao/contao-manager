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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserConfig extends AbstractConfig
{
    public const SCOPES = ['admin', 'install', 'update', 'read'];

    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        ApiKernel $kernel,
        Filesystem $filesystem,
    ) {
        parent::__construct(
            $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'users.json',
            $filesystem,
            $kernel->getTranslator(),
        );
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

    public function hasUsers(): bool
    {
        $this->initialize();

        return isset($this->data['users']) && \is_array($this->data['users']) && [] !== $this->data['users'];
    }

    /**
     * Gets all users.
     *
     * @return array<User>
     */
    public function getUsers(): array
    {
        $this->initialize();

        if (!$this->hasUsers()) {
            return [];
        }

        $users = [];

        foreach ($this->data['users'] as $user) {
            $users[] = new User(
                $user['username'],
                $user['password'],
                $user['scope'] ?? null,
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
    public function getUser(string $username, string|null $scope = null): User|null
    {
        $this->initialize();

        if (!isset($this->data['users'][$username])) {
            return null;
        }

        $data = $this->data['users'][$username];

        $user = new User(
            $data['username'],
            $data['password'] ?? null,
            $scope ?? $data['scope'] ?? null,
        );

        if ($data['totp_secret'] ?? null) {
            $user->setTotpSecret($data['totp_secret']);
        }

        if ($data['passkey'] ?? null) {
            $user->setPasskey($data['passkey']);
        }

        return $user;
    }

    /**
     * Creates user from given username and plaintext password but does not add it.
     */
    public function createUser(string $username, string $password, string|null $scope = null): User
    {
        $this->initialize();

        $encodedPassword = $this
            ->passwordHasherFactory
            ->getPasswordHasher(new User($username, null))
            ->hash($password)
        ;

        return new User($username, $encodedPassword, $scope);
    }

    /**
     * Adds a user to the configuration file.
     */
    public function addUser(User $user): void
    {
        $this->initialize();

        $username = $user->getUserIdentifier();

        if (isset($this->data['users'][$username])) {
            throw new \RuntimeException(\sprintf('Username "%s" already exists.', $username));
        }

        $this->data['users'][$username] = $user->jsonSerialize();

        $this->save();
    }

    /**
     * Replaces a user in the configuration file.
     */
    public function replaceUser(User $user): void
    {
        $this->initialize();

        unset($this->data['users'][$user->getUserIdentifier()]);

        $this->addUser($user);
    }

    /**
     * Update properties of a user in the configuration file.
     */
    public function updateUser(string $username, array $data): void
    {
        $this->initialize();

        if (!isset($this->data['users'][$username])) {
            throw new \RuntimeException(\sprintf('Username "%s" does not exist.', $username));
        }

        if (isset($data['password'])) {
            $data['password'] = $this
                ->passwordHasherFactory
                ->getPasswordHasher(new User($username, null))
                ->hash($data['password'])
            ;
        }

        $this->data['users'][$username] = array_merge(
            $this->data['users'][$username],
            $data,
        );
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

    public function getWebauthnOptions(string $key): string|null
    {
        $this->initialize();

        return $this->data['webauthn'][$key] ?? null;
    }

    public function setWebauthnOptions(string $key, string $value): void
    {
        $this->initialize();

        $this->data['webauthn'][$key] = $value;

        $this->save();
    }

    public function deleteWebauthnOptions(string $key): void
    {
        $this->initialize();

        unset($this->data['webauthn'][$key]);

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
                $payload,
            );
        }

        return $data;
    }

    /**
     * Gets token payload by ID (hashed token value).
     */
    public function getToken(string $id): array|null
    {
        $this->initialize();

        if (!isset($this->data['tokens'][$id])) {
            return null;
        }

        return array_merge(
            ['id' => $id],
            $this->data['tokens'][$id],
        );
    }

    /**
     * Finds token payload by unhashed token value.
     */
    public function findToken(string $token): array|null
    {
        $this->initialize();

        return $this->getToken(hash('sha256', $token));
    }

    /**
     * Creates a token for given username.
     */
    public function createToken(string $username, string $clientId, string $scope = 'admin', bool $oneTime = false): array
    {
        $this->initialize();

        if (!$this->hasUser($username)) {
            throw new \RuntimeException(\sprintf('Username "%s" does not exist.', $username));
        }

        if (!$oneTime) {
            foreach ($this->getTokens() as $payload) {
                if ($payload['username'] === $username && $payload['client_id'] === $clientId) {
                    $this->deleteToken($payload['id']);
                }
            }
        }

        $token = bin2hex(random_bytes(16));
        $id = hash('sha256', $token);

        if (isset($this->data['tokens'][$id])) {
            throw new \RuntimeException(\sprintf('Token with ID "%s" already exist.', $id));
        }

        $data = [
            'username' => $username,
            'client_id' => $clientId,
            'scope' => $scope,
        ];

        if ($oneTime) {
            $data['grant_type'] = 'one-time';
            $data['expires'] = strtotime('+30 seconds');
        }

        $this->data['tokens'][$id] = $data;

        $this->save();

        return array_merge(
            [
                'id' => $id,
                'token' => $token,
            ],
            $this->data['tokens'][$id],
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

    public function createInvitation(string $scope = 'admin'): array
    {
        $this->initialize();

        $token = bin2hex(random_bytes(16));
        $id = hash('sha256', $token);

        if (isset($this->data['tokens'][$id])) {
            throw new \RuntimeException(\sprintf('Token with ID "%s" already exist.', $id));
        }

        $data = [
            'scope' => $scope,
            'grant_type' => 'invitation',
            'expires' => strtotime('+1 week'),
        ];

        $this->data['tokens'][$id] = $data;

        $this->save();

        return array_merge(
            [
                'id' => $id,
                'token' => $token,
            ],
            $this->data['tokens'][$id],
        );
    }

    protected function initialize(): void
    {
        parent::initialize();

        if ([] !== $this->data && (!isset($this->data['version']) || (int) $this->data['version'] < 2)) {
            throw new \RuntimeException('Unsupported user.json version');
        }

        if (!isset($this->data['version'])) {
            $this->data['version'] = 2;
        }

        foreach (($this->data['tokens'] ?? []) as $id => $token) {
            if (isset($token['expires']) && $token['expires'] < time()) {
                unset($this->data['tokens'][$id]);
            }
        }

        $this->save();
    }
}
