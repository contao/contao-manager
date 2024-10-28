<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to handle users.
 */
class UserController
{
    public function __construct(private readonly UserConfig $config, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Returns a list of users in the configuration file.
     *
     * @Route("/users", methods={"GET"})
     */
    public function listUsers(): Response
    {
        return $this->getUserResponse($this->config->getUsers());
    }

    /**
     * Adds a new user to the configuration file.
     *
     * @Route("/users", methods={"POST"})
     */
    public function createUser(Request $request): Response
    {
        $user = $this->createUserFromRequest($request);

        if ($this->config->hasUser($user->getUserIdentifier())) {
            throw new BadRequestHttpException(sprintf('User "%s" already exists.', $user->getUserIdentifier()));
        }

        $this->config->addUser($user);

        return $this->getUserResponse($user, Response::HTTP_CREATED, true);
    }

    /**
     * Returns user data from the configuration file.
     *
     * @Route("/users/{username}", name="user_get", methods={"GET"})
     */
    public function retrieveUser(string $username): Response
    {
        if ($this->config->hasUser($username)) {
            return $this->getUserResponse($this->config->getUser($username));
        }

        throw new NotFoundHttpException(sprintf('User "%s" was not found.', $username));
    }

    /**
     * Replaces user data in the configuration file.
     *
     * @Route("/users/{username}", methods={"PUT"})
     */
    public function replaceUser(Request $request): Response
    {
        $user = $this->createUserFromRequest($request);

        if (!$this->config->hasUser($user->getUserIdentifier())) {
            throw new NotFoundHttpException(sprintf('User "%s" does not exist.', $user->getUserIdentifier()));
        }

        $this->config->updateUser($user);

        return $this->getUserResponse($user, Response::HTTP_OK, true);
    }

    /**
     * Deletes a user from the configuration file.
     *
     * @Route("/users/{username}", methods={"DELETE"})
     */
    public function deleteUser(string $username): Response
    {
        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('User "%s" was not found.', $username));
        }

        $this->config->deleteUser($username);

        return $this->getUserResponse($user);
    }

    /**
     * Returns a list of tokens of a user in the configuration file.
     *
     * @Route("/users/{username}/tokens", methods={"GET"})
     */
    public function listTokens(string $username): Response
    {
        $tokens = array_filter(
            $this->config->getTokens(),
            static fn($token): bool => $token['user'] === $username
        );

        return new JsonResponse($tokens);
    }

    /**
     * Adds a new token for a user to the configuration file.
     *
     * @Route("/users/{username}/tokens", methods={"POST"})
     */
    public function createToken(string $username, Request $request): Response
    {
        if (!$this->config->hasUser($username)) {
            throw new BadRequestHttpException(sprintf('User "%s" does not exists.', $username));
        }

        $clientId = $request->request->get('client_id');
        $scope = $request->request->get('scope');
        $oneTimeToken = 'one-time' === $request->request->get('grant_type');

        if (!$clientId || 'admin' !== $scope) {
            throw new BadRequestHttpException('Invalid payload for OAuth token.');
        }

        $token = $this->config->createToken($username, $clientId, $scope, $oneTimeToken);

        if ($oneTimeToken) {
            $token['url'] = $request->getUriForPath('/#?token='.$token['token']);
        }

        return new JsonResponse($token, Response::HTTP_CREATED);
    }

    /**
     * Returns token data of a user from the configuration file.
     *
     * @Route("/users/{username}/tokens/{id}", methods={"GET"})
     */
    public function retrieveToken(string $username, string $id): Response
    {
        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(sprintf('Token with ID "%s" was not found.', $id));
        }

        return new JsonResponse($payload);
    }

    /**
     * Deletes a token from the configuration file.
     *
     * @Route("/users/{username}/tokens/{id}", methods={"DELETE"})
     */
    public function deleteToken(string $username, string $id): Response
    {
        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(sprintf('Token "%s" was not found.', $id));
        }

        $this->config->deleteToken($id);

        return new JsonResponse($payload);
    }

    /**
     * Creates a response for given user information.
     *
     * @param User|array<User> $user
     */
    private function getUserResponse($user, int $status = Response::HTTP_OK, bool $addLocation = false): Response
    {
        $response = new JsonResponse(
            $this->convertToJson($user),
            $status
        );

        if ($addLocation && $user instanceof User) {
            $response->headers->set('Location', $this->urlGenerator->generate('user_get', ['username' => $user->getUserIdentifier()]));
        }

        return $response;
    }

    /**
     * Converts a user to JSON representation.
     *
     * @param array<User>|User $user
     */
    private function convertToJson(User|array $user): array
    {
        if ($user instanceof User) {
            return [
                'username' => $user->getUserIdentifier(),
            ];
        }

        foreach ($user as $k => $item) {
            $user[$k] = $this->convertToJson($item);
        }

        return $user;
    }

    /**
     * Creates and returns a new user from request data.
     *
     * @throws BadRequestHttpException
     */
    private function createUserFromRequest(Request $request): User
    {
        if (!$request->request->has('username') || !$request->request->has('password')) {
            throw new BadRequestHttpException('Username and password must be given.');
        }

        return $this->config->createUser(
            $request->request->get('username'),
            $request->request->get('password')
        );
    }
}
