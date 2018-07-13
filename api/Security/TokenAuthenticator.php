<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var UserConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param UserConfig $config
     */
    public function __construct(UserConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        if ($request->headers->has('Contao-Manager-Auth')) {
            return $request->headers->get('Contao-Manager-Auth');
        }

        $authentication = $this->getAuthenticationHeader($request);

        if (is_string($authentication) && 0 === strpos(strtolower($authentication), 'bearer ')) {
            return substr($authentication, 7);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$this->config->hasToken($credentials)) {
            return null;
        }

        $token = $this->config->getToken($credentials);

        return $userProvider->loadUserByUsername($token['username']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $token->setAttribute('authenticator', get_called_class());

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Gets the authentication header from request or HTTP headers.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getAuthenticationHeader(Request $request)
    {
        if ($request->server->has('HTTP_AUTHORIZATION')) {
            return $request->server->get('HTTP_AUTHORIZATION');
        }

        if ($request->server->has('REDIRECT_HTTP_AUTHORIZATION')) {
            return $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return null;
    }
}
