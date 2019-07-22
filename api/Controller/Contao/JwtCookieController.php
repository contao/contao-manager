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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/jwt-cookie", methods={"GET", "PUT", "DELETE"})
 */
class JwtCookieController
{
    public const COOKIE_NAME = '_contao_preview';

    /**
     * @var ContaoApi
     */
    private $api;

    /**
     * Constructor.
     */
    public function __construct(ContaoApi $api)
    {
        $this->api = $api;
    }

    /**
     * Handles the controller action.
     *
     * @throws \Seld\JsonLint\ParsingException
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        if (!$this->isSupported()) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support the jwt-token API.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getJwtPayload($request);

            case 'PUT':
                return $this->setJwtToken($request);

            case 'DELETE':
                return $this->removeJwtToken();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     */
    private function getJwtPayload(Request $request): Response
    {
        if (!$request->cookies->has(self::COOKIE_NAME)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $payload = $this->api->runCommand(['jwt-cookie:parse', $request->cookies->get(self::COOKIE_NAME)], true);

        return new JsonResponse($payload);
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     */
    private function setJwtToken(Request $request): Response
    {
        $arguments = ['jwt-cookie:generate'];

        if ($request->request->get('debug', false)) {
            $arguments[] = '--debug';
        }

        $cookie = Cookie::fromString($this->api->runCommand($arguments));

        $response = new JsonResponse(
            [
                'debug' => $request->request->get('debug', false),
            ]
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
