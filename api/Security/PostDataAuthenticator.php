<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Tenside\Core\Util\JsonArray;

/**
 * This class validates simple post data.
 */
class PostDataAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * The password encoder in use.
     *
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * Create a new instance.
     *
     * @param UserPasswordEncoderInterface $passwordEncoder the password encoder to use for validating the password
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->createResponseFromException($authException);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        try {
            $inputData = new JsonArray($request->getContent());
        } catch (\Exception $e) {
            return null;
        }

        if (!($inputData->has('username') && $inputData->has('password'))) {
            // post data? Return null and no other methods will be called
            return null;
        }

        // What you return here will be passed to getUser() as $credentials
        return (object) [
            'username' => $inputData->get('username'),
            'password' => $inputData->get('password'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // Get the user for the injected UserProvider
        return $userProvider->loadUserByUsername($credentials->username);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials->password);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->createResponseFromException($exception);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
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
     * Create a proper json response containing the error.
     *
     * @param AuthenticationException $authException the exception that started the authentication process
     *
     * @return JsonResponse
     */
    private function createResponseFromException(AuthenticationException $authException = null)
    {
        $data = [
            'status' => 'unauthorized',
        ];

        if ($authException) {
            $data['message'] = $authException->getMessageKey();
        }

        return new JsonResponse(
            $data,
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }
}
