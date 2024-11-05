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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to handle users.
 */
class UserController
{
    public function __construct(
        private readonly UserConfig $config,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    /**
     * Returns a list of users in the configuration file.
     */
    #[Route(path: '/users', methods: ['GET'])]
    public function listUsers(): Response
    {
        $users = $this->config->getUsers();

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $username = $this->security->getUser()?->getUserIdentifier();
            $users = array_filter(
                $users,
                static fn (User $user): bool => $user->getUserIdentifier() === $username,
            );
        }

        return $this->getUserResponse($users);
    }

    /**
     * Adds a new user to the configuration file.
     */
    #[Route(path: '/users', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createUser(Request $request): Response
    {
        $user = $this->createUserFromRequest($request);

        if ($this->config->hasUser($user->getUserIdentifier())) {
            throw new BadRequestHttpException(\sprintf('User "%s" already exists.', $user->getUserIdentifier()));
        }

        $this->config->addUser($user);

        return $this->getUserResponse($user, Response::HTTP_CREATED, true);
    }

    /**
     * Returns user data from the configuration file.
     */
    #[Route(path: '/users/{username}', name: 'user_get', methods: ['GET'])]
    public function retrieveUser(string $username): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" was not found.', $username));
        }

        return $this->getUserResponse($user);
    }

    /**
     * Replaces user data in the configuration file.
     */
    #[Route(path: '/users/{username}', methods: ['PUT'])]
    public function replaceUser(string $username, Request $request): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        $user = $this->createUserFromRequest($request);

        if (!$this->config->hasUser($user->getUserIdentifier())) {
            throw new NotFoundHttpException(\sprintf('User "%s" does not exist.', $user->getUserIdentifier()));
        }

        $this->config->updateUser($user);

        return $this->getUserResponse($user, Response::HTTP_OK, true);
    }

    /**
     * Deletes a user from the configuration file.
     */
    #[Route(path: '/users/{username}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(string $username): Response
    {
        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" was not found.', $username));
        }

        $this->config->deleteUser($username);

        return $this->getUserResponse($user);
    }

    /**
     * Returns a list of tokens of a user in the configuration file.
     */
    #[Route(path: '/users/{username}/tokens', methods: ['GET'])]
    public function listTokens(string $username): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        $tokens = array_filter(
            $this->config->getTokens(),
            static fn ($token): bool => $token['username'] === $username,
        );

        return new JsonResponse($tokens);
    }

    /**
     * Adds a new token for a user to the configuration file.
     */
    #[Route(path: '/users/{username}/tokens', methods: ['POST'])]
    public function createToken(string $username, Request $request): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        if (!$this->config->hasUser($username)) {
            throw new BadRequestHttpException(\sprintf('User "%s" does not exists.', $username));
        }

        $clientId = $request->request->get('client_id');
        $scope = $request->request->get('scope');
        $oneTimeToken = 'one-time' === $request->request->get('grant_type');

        if (!$clientId || !$scope) {
            throw new BadRequestHttpException('Invalid payload for OAuth token.');
        }

        $this->denyAccessUnlessGranted('ROLE_'.strtoupper($scope));

        $token = $this->config->createToken($username, $clientId, $scope, $oneTimeToken);

        if ($oneTimeToken) {
            $token['url'] = $request->getUriForPath('/#?token='.$token['token']);
        }

        return new JsonResponse($token, Response::HTTP_CREATED);
    }

    /**
     * Returns token data of a user from the configuration file.
     */
    #[Route(path: '/users/{username}/tokens/{id}', methods: ['GET'])]
    public function retrieveToken(string $username, string $id): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(\sprintf('Token with ID "%s" was not found.', $id));
        }

        return new JsonResponse($payload);
    }

    /**
     * Deletes a token from the configuration file.
     */
    #[Route(path: '/users/{username}/tokens/{id}', methods: ['DELETE'])]
    public function deleteToken(string $username, string $id): Response
    {
        $this->denyAccessUnlessUserOrAdmin($username);

        $payload = $this->config->getToken($id);

        if (null === $payload || $payload['username'] !== $username) {
            throw new NotFoundHttpException(\sprintf('Token "%s" was not found.', $id));
        }

        $this->config->deleteToken($id);

        return new JsonResponse($payload);
    }

    /**
     * Creates a response for given user information.
     *
     * @param User|array<User> $user
     */
    private function getUserResponse(User|array $user, int $status = Response::HTTP_OK, bool $addLocation = false): Response
    {
        $response = new JsonResponse(
            $this->convertToJson($user),
            $status,
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
            return $user->getProfile();
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
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $roles = array_values($request->request->all('roles'));

        if ('' === $username || strlen($password) < 8) {
            throw new BadRequestHttpException('Username and password must be given.');
        }

        if (\count($roles) > 1 || !\in_array($roles[0], ['ROLE_ADMIN', 'ROLE_INSTALL', 'ROLE_UPDATE', 'ROLE_READ'])) {
            throw new BadRequestHttpException('Only one of the following roles is allowed: ROLE_ADMIN, ROLE_INSTALL, ROLE_UPDATE, ROLE_READ.');
        }

        return $this->config->createUser(
            $username,
            $password,
            $roles ?: null,
            array_filter($request->request->all()),
        );
    }

    private function denyAccessUnlessUserOrAdmin(string $username, string $message = 'Access Denied.'): void
    {
        if ($username !== $this->security->getUser()?->getUserIdentifier()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN', null, $message);
        }
    }

    private function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->security->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException($message);
            $exception->setAttributes([$attribute]);
            $exception->setSubject($subject);

            throw $exception;
        }
    }
}
