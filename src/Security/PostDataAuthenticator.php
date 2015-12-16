<?php

/**
 * This file is part of contao/package-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/package-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/package-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/package-manager
 * @filesource
 */

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Tenside\Util\JsonArray;

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
     * @param UserPasswordEncoderInterface $passwordEncoder The password encoder to use for validating the password.
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->createResponseFromException($authException);
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials(Request $request)
    {
        $inputData = new JsonArray($request->getContent());

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
     * {@inheritDoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // Get the user for the injected UserProvider
        return $userProvider->loadUserByUsername($credentials->username);
    }

    /**
     * {@inheritDoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials->password);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->createResponseFromException($exception);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Create a proper json response containing the error.
     *
     * @param AuthenticationException $authException The exception that started the authentication process.
     *
     * @return JsonResponse
     */
    private function createResponseFromException(AuthenticationException $authException = null)
    {
        $data = [
            'status'  => 'unauthorized',
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
