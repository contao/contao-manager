<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Contao;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ContaoApi;
use Crell\ApiProblem\ApiProblem;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/contao/jwt-cookie', methods: ['GET', 'PUT', 'DELETE'])]
#[IsGranted('ROLE_UPDATE')]
class JwtCookieController
{
    public const COOKIE_NAME = 'contao_settings';

    public function __construct(private readonly ContaoApi $api)
    {
    }

    /**
     * Handles the controller action.
     *
     * @throws ParsingException
     */
    public function __invoke(Request $request): ApiProblemResponse|Response
    {
        if (!$this->isSupported()) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support the jwt-token API.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED),
            );
        }

        return match ($request->getMethod()) {
            'GET' => $this->getJwtPayload($request),
            'PUT' => $this->setJwtToken($request),
            'DELETE' => $this->removeJwtToken(),
            default => new Response(null, Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    /**
     * @throws ParsingException
     */
    private function getJwtPayload(Request $request): Response
    {
        if (!$request->cookies->has(self::COOKIE_NAME)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $payload = $this->api->runJsonCommand(['jwt-cookie:parse', $request->cookies->get(self::COOKIE_NAME)]);

        return new JsonResponse($payload);
    }

    /**
     * @throws ParsingException
     */
    private function setJwtToken(Request $request): Response
    {
        $arguments = ['jwt-cookie:generate'];

        if ($request->request->getBoolean('debug')) {
            $arguments[] = '--debug';
        }

        $cookie = Cookie::fromString($this->api->runCommand($arguments));

        $response = new JsonResponse(
            [
                'debug' => $request->request->getBoolean('debug'),
            ],
        );

        $response->headers->setCookie($cookie);

        return $response;
    }

    private function removeJwtToken(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);

        $response->headers->clearCookie(self::COOKIE_NAME);

        return $response;
    }

    private function isSupported(): bool
    {
        $features = $this->api->getFeatures();

        return isset($features['contao/manager-bundle']['jwt-cookie'])
            && \in_array('debug', $features['contao/manager-bundle']['jwt-cookie'], true);
    }
}
