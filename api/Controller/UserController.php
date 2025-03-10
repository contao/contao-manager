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
use OTPHP\Factory;
use OTPHP\TOTP;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
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
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    /**
     * Returns a list of users in the configuration file.
     */
    #[Route(path: '/users', methods: ['GET'])]
    public function listUsers(): Response
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $username = $this->security->getUser()?->getUserIdentifier();
            $user = $this->config->getUser($username);

            return $this->getUserResponse([$user]);
        }

        return $this->getUserResponse($this->config->getUsers());
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

        $this->config->replaceUser($user);

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

    #[Route(path: '/users/{username}/password', methods: ['PUT'])]
    public function setPassword(string $username, Request $request): Response
    {
        $this->denyAccessUnlessUser($username);

        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" does not exist.', $username));
        }

        if ($user->getPasskey()) {
            throw new ServiceUnavailableHttpException('Cannot change password of a user with a Passkey.');
        }

        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');

        if (!$currentPassword || !$newPassword) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $isPasswordValid = $this
            ->passwordHasherFactory
            ->getPasswordHasher($user)
            ->verify($user->getPassword(), $currentPassword)
        ;

        if (!$isPasswordValid) {
            throw new UnprocessableEntityHttpException('Current password is not valid.');
        }

        $this->config->updateUser($username, ['password' => $newPassword]);

        return new JsonResponse();
    }

    #[Route(path: '/users/{username}/totp', methods: ['GET'])]
    public function getTOTP(string $username): Response
    {
        $this->denyAccessUnlessUser($username);

        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" does not exist.', $username));
        }

        if ($user->getPasskey()) {
            throw new ServiceUnavailableHttpException('Cannot configure TOTP of a user with a Passkey.');
        }

        if (null !== $user->getTotpSecret()) {
            throw new BadRequestException('TOTP already configured.');
        }

        $totp = TOTP::generate();
        $totp->setLabel($username);

        return new JsonResponse(['provisioning_uri' => $totp->getProvisioningUri()]);
    }

    #[Route(path: '/users/{username}/totp', methods: ['PUT'])]
    public function setupTotp(string $username, Request $request): Response
    {
        $this->denyAccessUnlessUser($username);

        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" does not exist.', $username));
        }

        if ($user->getPasskey()) {
            throw new ServiceUnavailableHttpException('Cannot configure TOTP of a user with a Passkey.');
        }

        if (null !== $user->getTotpSecret()) {
            throw new AccessDeniedException('TOTP already configured.');
        }

        try {
            $totp = Factory::loadFromProvisioningUri($request->request->get('provisioning_uri'));
        } catch (\Exception) {
            throw new BadRequestHttpException('Invalid provisioning_uri');
        }

        if (!$totp instanceof TOTP) {
            throw new BadRequestHttpException('Invalid provisioning_uri');
        }

        if (!$totp->verify($request->request->get('totp'))) {
            throw new UnprocessableEntityHttpException('Invalid TOTP');
        }

        $this->config->updateUser($username, ['totp_secret' => $totp->getSecret()]);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    #[Route(path: '/users/{username}/totp', methods: ['DELETE'])]
    public function deleteTotp(string $username, Request $request): Response
    {
        $this->denyAccessUnlessUser($username);

        $user = $this->config->getUser($username);

        if (null === $user) {
            throw new NotFoundHttpException(\sprintf('User "%s" does not exist.', $username));
        }

        if ($user->getPasskey()) {
            throw new ServiceUnavailableHttpException('Cannot configure TOTP of a user with a Passkey.');
        }

        if (null === $user->getTotpSecret()) {
            throw new NotFoundHttpException('TOTP not configured.');
        }

        try {
            $totp = TOTP::createFromSecret($user->getTotpSecret());
        } catch (\Exception) {
            throw new \RuntimeException('TOTP error.');
        }

        if (!$totp->verify($request->request->getString('totp'))) {
            throw new UnprocessableEntityHttpException('Invalid TOTP');
        }

        $this->config->updateUser($username, ['totp_secret' => null]);

        return new JsonResponse();
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
            static fn ($token): bool => ($token['username'] ?? null) === $username,
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
     * Adds an invitation to the configuration file.
     */
    #[Route(path: '/invitations', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function inviteUser(Request $request): Response
    {
        $token = $this->config->createInvitation($request->request->get('scope'));
        $token['url'] = $request->getUriForPath('/#?invitation='.$token['token']);

        return new JsonResponse($token, Response::HTTP_CREATED);
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
            $json = [
                'username' => $user->getUserIdentifier(),
                'scope' => $user->getScope(),
                'passkey' => $user->getPasskey(),
            ];

            if ($user->getPasskey()) {
                $json['passkey'] = true;
            } else {
                $json['totp_enabled'] = (bool) $user->getTotpSecret();
            }

            return $json;
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
        $scope = $request->request->get('scope');

        if ('' === $username || \strlen($password) < 8) {
            throw new BadRequestHttpException('Username or password invalid.');
        }

        if (!\in_array($scope, User::SCOPES, true)) {
            throw new BadRequestHttpException('Only the following "scope" is required: '.implode(', ', User::SCOPES));
        }

        return $this->config->createUser(
            $username,
            $password,
            $scope,
        );
    }

    private function denyAccessUnlessUser(string $username, string $message = 'Access Denied.'): void
    {
        if ($username !== $this->security->getUser()?->getUserIdentifier()) {
            throw new AccessDeniedException($message);
        }
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
