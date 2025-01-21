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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\Exception\InvalidTotpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginAuthenticator extends AbstractBrowserAuthenticator
{
    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly UserConfig $userConfig,
        JwtManager $jwtManager,
        Filesystem $filesystem,
        ApiKernel $kernel,
    ) {
        parent::__construct($jwtManager, $this->userConfig, $filesystem, $kernel);
    }

    public function supports(Request $request): bool
    {
        return parent::supports($request)
            && $request->request->has('username')
            && $request->request->has('password')
        ;
    }

    public function authenticate(Request $request): Passport
    {
        if (!$this->userConfig->hasUsers()) {
            $user = $this->userConfig->createUser($request->request->get('username'), $request->request->get('password'));
            $this->userConfig->addUser($user);
        } elseif ($request->request->has('invitation')) {
            $token = $this->userConfig->findToken($request->request->get('invitation'));

            if (null === $token || 'invitation' !== ($token['grant_type'] ?? null)) {
                throw new AuthenticationCredentialsNotFoundException();
            }

            $user = $this->userConfig->createUser(
                $request->request->get('username'),
                $request->request->get('password'),
                $token['scope'],
            );

            if ($this->userConfig->hasUser($user->getUserIdentifier())) {
                throw new UnprocessableEntityHttpException('Username exists.');
            }

            $this->userConfig->addUser($user);
            $this->userConfig->deleteToken($token['id']);
        }

        $userBadge = new UserBadge($request->request->get('username'), $this->userProvider->loadUserByIdentifier(...));
        $passport = new Passport($userBadge, new PasswordCredentials($request->request->get('password')));

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($request->request->get('password'), $this->userProvider));
        }

        $passport->addBadge(new TotpBadge($request->request->getString('totp')));

        return $passport;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof InvalidTotpException) {
            return new JsonResponse([
                'username' => $exception->getUser()?->getUserIdentifier(),
                'totp_enabled' => true,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
