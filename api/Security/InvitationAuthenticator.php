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
use Couchbase\AuthenticationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class InvitationAuthenticator extends AbstractBrowserAuthenticator
{
    private string $tokenId;

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
        $isValid = parent::supports($request)
            && $request->request->has('username')
            && $request->request->has('password')
            && $request->request->has('invitation')
        ;

        if (!$isValid) {
            return false;
        }

        $token = $this->userConfig->findToken($request->request->get('invitation'));

        return $token && 'invitation' === ($token['grant_type'] ?? null);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->userConfig->findToken($request->request->get('invitation'));

        if (null === $token || 'invitation' !== ($token['grant_type'] ?? null)) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        $user = $this->userConfig->createUser(
            $request->request->get('username'),
            $request->request->get('password'),
            ['ROLE_'.strtoupper($token['scope'])],
        );

        if ($this->userConfig->hasUser($user->getUserIdentifier())) {
            throw new UnprocessableEntityHttpException('Username exists.');
        }

        $this->userConfig->addUser($user);
        $this->userConfig->deleteToken($token['id']);
        $this->userConfig->save();

        $userBadge = new UserBadge($request->request->get('username'), $this->userProvider->loadUserByIdentifier(...));
        $passport = new Passport($userBadge, new PasswordCredentials($request->request->get('password')));

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($request->request->get('password'), $this->userProvider));
        }

        return $passport;
    }
}
