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
use Cose\Algorithms;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

#[Autoconfigure(bind: [
    '$serializer' => '@contao_manager.webauthn.serializer',
    '$authenticatorAttestationResponseValidator' => '@contao_manager.webauthn.authenticator_attestation_response_validator',
    '$authenticatorAssertionResponseValidator' => '@contao_manager.webauthn.authenticator_assertion_response_validator',
])]
class WebauthnAuthenticator extends AbstractBrowserAuthenticator
{
    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AuthenticatorAttestationResponseValidator $authenticatorAttestationResponseValidator,
        private readonly AuthenticatorAssertionResponseValidator $authenticatorAssertionResponseValidator,
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
        return parent::supports($request) && $request->request->has('passkey');
    }

    public function authenticate(Request $request): Passport
    {
        $rpEntity = $this->createRpEntity($request->getHost());

        if ($request->request->has('username') && (!$this->userConfig->hasUsers() || $request->request->has('invitation'))) {
            if ($this->userConfig->hasUser($request->request->get('username'))) {
                throw new UnprocessableEntityHttpException('Username exists.');
            }

            $user = $this->createUser(
                $rpEntity,
                $request->request->get('username'),
                $request->request->get('passkey'),
                $request->request->get('invitation'),
            );

            $userBadge = new UserBadge($user->getUserIdentifier(), $this->userProvider->loadUserByIdentifier(...));

            return new SelfValidatingPassport($userBadge);
        }

        $authenticatorAssertionResponse = $this->serializer->deserialize($request->request->get('passkey'), PublicKeyCredential::class, 'json')->response;

        if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            throw new BadRequestException();
        }

        $username = $authenticatorAssertionResponse->userHandle;
        $userBadge = new UserBadge($username, $this->userProvider->loadUserByIdentifier(...));

        $credentials = new CustomCredentials(
            function (AuthenticatorAssertionResponse $response, User $user) use ($rpEntity): bool {
                $passkey = $user->getPasskey();
                $challenge = bin2hex($response->clientDataJSON->challenge);
                $requestOptions = $this->userConfig->getWebauthnOptions($challenge);

                if (!$passkey || !$requestOptions) {
                    throw new AuthenticationCredentialsNotFoundException();
                }

                $this->userConfig->deleteWebauthnOptions($challenge);

                try {
                    $publicKeyCredentialSource = $this
                        ->serializer
                        ->deserialize($passkey, PublicKeyCredentialSource::class, 'json')
                    ;

                    $publicKeyCredentialRequestOptions = $this
                        ->serializer
                        ->deserialize($requestOptions, PublicKeyCredentialRequestOptions::class, 'json')
                    ;

                    $this->authenticatorAssertionResponseValidator->check(
                        $publicKeyCredentialSource,
                        $response,
                        $publicKeyCredentialRequestOptions,
                        $rpEntity->id,
                        $user->getUserIdentifier(),
                    );

                    return true;
                } catch (\Exception $e) {
                    throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
                }
            },
            $authenticatorAssertionResponse
        );

        return new Passport($userBadge, $credentials);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof InvalidTotpException) {
            return new JsonResponse(
                [
                    'username' => $exception->getUser()?->getUserIdentifier(),
                    'totp_enabled' => true,
                ],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    public function getCredentialOptions(string $host, string|null $username): string
    {
        $challenge = random_bytes(32);
        $rpEntity = $this->createRpEntity($host);

        if (null === $username) {
            $options = PublicKeyCredentialRequestOptions::create(
                $challenge,
                $rpEntity->id,
                userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            );
        } else {
            $options = PublicKeyCredentialCreationOptions::create(
                $rpEntity,
                new PublicKeyCredentialUserEntity($username, $username, $username),
                $challenge,
                [
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K), // More interesting algorithm
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256), //      ||
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256), //      ||
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256), //      \/
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256), // Less interesting algorithm
                ],
                new AuthenticatorSelectionCriteria(userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED),
            );
        }

        $serialized = $this->serializer->serialize(
            $options,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ],
        );

        $this->userConfig->setWebauthnOptions(bin2hex($challenge), $serialized);

        return $serialized;
    }

    private function createUser(PublicKeyCredentialRpEntity $rpEntity, string $username, string $data, string|null $invitation = null): User
    {
        $scope = null;

        if ($invitation) {
            $token = $this->userConfig->findToken($invitation);

            if (null === $token || 'invitation' !== ($token['grant_type'] ?? null)) {
                throw new AuthenticationCredentialsNotFoundException('Invitation not found.');
            }

            $scope = $token['scope'];
        } elseif ($this->userConfig->hasUsers()) {
            throw new AccessDeniedException();
        }

        $authenticatorAttestationResponse = $this->serializer->deserialize($data, PublicKeyCredential::class, 'json')->response;

        if (!$authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            throw new BadCredentialsException();
        }

        $challenge = bin2hex($authenticatorAttestationResponse->clientDataJSON->challenge);
        $creationOptions = $this->userConfig->getWebauthnOptions($challenge);

        if (!$creationOptions) {
            throw new BadCredentialsException();
        }

        $this->userConfig->deleteWebauthnOptions($challenge);

        $publicKeyCredentialCreationOptions = $this->serializer->deserialize(
            $creationOptions,
            PublicKeyCredentialCreationOptions::class,
            'json',
        );

        $publicKeyCredentialSource = $this->authenticatorAttestationResponseValidator->check(
            $authenticatorAttestationResponse,
            $publicKeyCredentialCreationOptions,
            $rpEntity->id,
        );

        $user = new User($username, null, $scope);
        $user->setPasskey($this->serializer->serialize($publicKeyCredentialSource, 'json'));

        $this->userConfig->addUser($user);

        if ($invitation) {
            $this->userConfig->deleteToken($token['id']);
        }

        return $user;
    }

    private function createRpEntity(string $host): PublicKeyCredentialRpEntity
    {
        if ('127.0.0.1' === $host) {
            $host = 'localhost';
        }

        return new PublicKeyCredentialRpEntity(
            'Contao Manager '.ApiKernel::MANAGER_VERSION,
            $host,
            'data:image/svg+xml;base64,PHN2ZyBpZD0iRWJlbmVfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgMTc4LjYgMTU1LjkiPjxzdHlsZT4uc3Qwe2ZpbGw6I2ZmZn0uc3Qxe2ZpbGw6I2Y0N2MwMH08L3N0eWxlPjx0aXRsZT5jb250YW9fb3JpZ2luYWxfcmdiPC90aXRsZT48cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTEuOC0uMUM1LjMtLjEgMCA1LjIgMCAxMS43djEzMi40YzAgNi41IDUuMyAxMS44IDExLjggMTEuOGgxNTVjNi41IDAgMTEuOC01LjIgMTEuOC0xMS43VjExLjdjMC02LjUtNS4zLTExLjgtMTEuOC0xMS44aC0xNTV6Ii8+PHBhdGggY2xhc3M9InN0MSIgZD0iTTE1LjkgOTQuNmM1IDIzLjMgOS4yIDQ1LjQgMjMuNyA2MS40SDExLjhDNS4zIDE1NiAwIDE1MC44IDAgMTQ0LjNWMTEuN0MwIDUuMiA1LjMtLjEgMTEuOC0uMWgyMC4xQzI3IDQuNCAyMi43IDkuNSAxOS4xIDE1LjEgMy4yIDM5LjQgOS44IDY1LjkgMTUuOSA5NC42ek0xNjYuOC0uMWgtMzEuNWM3LjUgNy41IDEzLjggMTcuMSAxOC41IDI5LjFsLTQ3LjkgMTAuMUMxMDAuNiAyOS4xIDkyLjYgMjAuOCA3NyAyNGMtOC42IDEuOC0xNC4zIDYuNi0xNi45IDExLjktMy4xIDYuNS00LjYgMTMuOCAyLjggNDguNnMxMS44IDQwLjggMTcuMyA0NS41YzQuNSAzLjggMTEuNyA1LjkgMjAuMyA0LjEgMTUuNi0zLjMgMTkuNS0xNC4yIDIwLjEtMjUuNWw0Ny45LTEwLjFjMS4xIDI0LjgtNi41IDQ0LTIwLjEgNTcuM2gxOC4yYzYuNSAwIDExLjgtNS4yIDExLjgtMTEuN1YxMS43Yy4yLTYuNS01LjEtMTEuOC0xMS42LTExLjh6Ii8+PC9zdmc+',
        );
    }
}
